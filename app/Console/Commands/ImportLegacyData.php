<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyData extends Command
{
    protected $signature = 'legacy:import {--force : Import even when target users already exist}';
    protected $description = 'Import and transform data from the legacy Bijak Tuition database';

    public function handle(): int
    {
        try {
            DB::connection('legacy')->getPdo();
        } catch (\Throwable $e) {
            $this->error('Cannot connect to the legacy database: '.$e->getMessage());
            return self::FAILURE;
        }

        if (DB::table('users')->exists() && ! $this->option('force')) {
            $this->error('Target data already exists. Run migrate:fresh first, or use --force for an idempotent re-import.');
            return self::FAILURE;
        }

        DB::transaction(function () {
            $this->importUsers();
            $this->importSubjects();
            $this->importTeachers();
            $this->importPackages();
            $this->importStudents();
            $this->importEnrollmentsAndPayments();
            $this->importSimpleTables();
            $this->importQuizzes();
            $this->importExamResults();
        });

        $this->newLine();
        $this->info('Legacy import completed. Run: php artisan legacy:verify');
        $this->warn('Legacy `grades` rows were not imported because they have no subject_id or numeric score.');
        return self::SUCCESS;
    }

    private function rows(string $table)
    {
        return DB::connection('legacy')->table($table)->orderBy('id')->get();
    }

    private function importUsers(): void
    {
        foreach ($this->rows('user') as $row) {
            DB::table('users')->updateOrInsert(['id' => $row->id], [
                'name' => $row->name, 'email' => $row->email, 'password' => $row->password,
                'role' => $row->role,
                'account_status' => $row->account_status === 'active' ? 'active' : 'inactive',
                'created_at' => $row->created_at, 'updated_at' => $row->created_at,
            ]);
        }
        $this->line('Imported users: '.DB::table('users')->count());
    }

    private function importSubjects(): void
    {
        foreach ($this->rows('subject') as $row) {
            DB::table('subjects')->updateOrInsert(['id' => $row->id], [
                'name' => $row->subject_name, 'price' => $row->price,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function importTeachers(): void
    {
        $subjectIds = DB::table('subjects')->get()->mapWithKeys(
            fn ($s) => [mb_strtolower(trim($s->name)) => $s->id]
        );
        foreach ($this->rows('teacher') as $row) {
            DB::table('teachers')->updateOrInsert(['id' => $row->id], [
                'name' => $row->name, 'email' => $row->email, 'photo_path' => $row->photo,
                'background' => $row->background, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $subjectId = $subjectIds[mb_strtolower(trim($row->subject))] ?? null;
            if ($subjectId) {
                DB::table('subject_teacher')->updateOrInsert(
                    ['subject_id' => $subjectId, 'teacher_id' => $row->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            } else {
                $this->warn("Teacher {$row->id}: subject '{$row->subject}' could not be matched.");
            }
        }
    }

    private function importPackages(): void
    {
        foreach ($this->rows('bundlepack') as $row) {
            DB::table('packages')->updateOrInsert(['id' => $row->id], [
                'name' => $row->name, 'elective_subject_limit' => $row->total_subjects,
                'price' => $row->price, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function importStudents(): void
    {
        foreach ($this->rows('student') as $row) {
            DB::table('students')->updateOrInsert(['id' => $row->id], [
                'user_id' => $row->user_id, 'parent_id' => $row->parent_id,
                'name' => $row->name ?: DB::table('users')->where('id', $row->user_id)->value('name'),
                'ic' => $row->ic ?: 'legacy-'.$row->id,
                'date_of_birth' => $row->dob ?: '2000-01-01',
                'form_level' => $row->form_level, 'school_name' => $row->school_name ?: 'Not provided',
                'created_at' => $row->created_at, 'updated_at' => $row->created_at,
            ]);
        }
    }

    private function importEnrollmentsAndPayments(): void
    {
        foreach ($this->rows('payment') as $row) {
            $packageId = null;
            if ($row->enrollment_type === 'bundle') {
                $packageId = DB::connection('legacy')->table('bundle_selection')
                    ->where('user_id', $row->parent_id)->where('created_at', '<=', $row->paid_at)
                    ->orderByDesc('created_at')->value('bundle_id');
            }
            $isPaid = $row->payment_status === 'paid';
            DB::table('enrollments')->updateOrInsert(['id' => $row->id], [
                'student_id' => $row->student_id, 'parent_id' => $row->parent_id,
                'package_id' => $packageId, 'type' => $row->enrollment_type,
                'status' => $isPaid ? 'approved' : 'pending_approval',
                'total_amount' => $row->total_amount, 'submitted_at' => $row->paid_at,
                'approved_at' => $isPaid ? $row->paid_at : null,
                'created_at' => $row->paid_at, 'updated_at' => $row->paid_at,
            ]);
            $subjects = DB::connection('legacy')->table('student_subject')
                ->where('student_id', $row->student_id)->get();
            foreach ($subjects as $subject) {
                DB::table('enrollment_subject')->updateOrInsert(
                    ['enrollment_id' => $row->id, 'subject_id' => $subject->subject_id],
                    ['price_at_enrollment' => DB::table('subjects')->where('id', $subject->subject_id)->value('price'),
                     'created_at' => $subject->created_at, 'updated_at' => $subject->created_at]
                );
            }
            $method = $row->payment_method === 'Card Payment' ? 'card' : 'counter';
            DB::table('payments')->updateOrInsert(['id' => $row->id], [
                'enrollment_id' => $row->id, 'payment_method' => $method,
                'payment_status' => $isPaid ? 'paid' : ($row->payment_status === 'failed' ? 'failed' : 'pending'),
                'amount' => $row->total_amount, 'paid_at' => $row->paid_at,
                'approved_at' => $isPaid ? $row->paid_at : null,
                'created_at' => $row->paid_at, 'updated_at' => $row->paid_at,
            ]);
        }
    }

    private function importSimpleTables(): void
    {
        foreach ($this->rows('announcement') as $r) DB::table('announcements')->updateOrInsert(['id'=>$r->id], ['content'=>$r->info,'image_path'=>$r->photo,'published_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        foreach ($this->rows('feedback') as $r) DB::table('feedback')->updateOrInsert(['id'=>$r->id], ['name'=>$r->name,'email'=>$r->email,'phone'=>$r->phone,'message'=>$r->feedback,'status'=>'new','created_at'=>now(),'updated_at'=>now()]);
        foreach ($this->rows('timetable') as $r) DB::table('timetables')->updateOrInsert(['id'=>$r->id], ['day'=>$r->hari,'start_time'=>$r->class_time,'duration_minutes'=>$r->duration,'subject_id'=>$r->subject_id,'teacher_id'=>$r->teacher_id,'form_level'=>$r->study_level,'created_at'=>now(),'updated_at'=>now()]);
    }

    private function importQuizzes(): void
    {
        foreach ($this->rows('quiz') as $r) DB::table('quizzes')->updateOrInsert(['id'=>$r->id], ['title'=>$r->title,'description'=>$r->description,'subject_id'=>$r->subject_id,'form_level'=>$r->form_level,'time_limit_minutes'=>$r->time_limit,'created_by'=>$r->created_by,'is_published'=>true,'created_at'=>$r->created_at,'updated_at'=>$r->created_at]);
        foreach ($this->rows('quiz_question') as $r) DB::table('quiz_questions')->updateOrInsert(['id'=>$r->id], ['quiz_id'=>$r->quiz_id,'question'=>$r->question,'option_a'=>$r->option_a ?: '', 'option_b'=>$r->option_b ?: '', 'option_c'=>$r->option_c ?: '', 'option_d'=>$r->option_d ?: '', 'correct_answer'=>strtoupper($r->correct_answer),'created_at'=>now(),'updated_at'=>now()]);
        foreach ($this->rows('quiz_attempt') as $r) DB::table('quiz_attempts')->updateOrInsert(['id'=>$r->id], ['quiz_id'=>$r->quiz_id,'student_id'=>$r->student_id,'score'=>$r->score,'total_questions'=>$r->total_questions,'correct_answers'=>$r->correct_answers,'submitted_at'=>$r->submitted_at,'created_at'=>$r->submitted_at,'updated_at'=>$r->submitted_at]);
    }

    private function importExamResults(): void
    {
        foreach ($this->rows('student_exam') as $r) DB::table('exam_results')->updateOrInsert(['id'=>$r->id], ['student_id'=>$r->student_id,'subject_id'=>$r->subject_id,'exam_type'=>$r->exam_type,'score'=>$r->score,'teacher_remark'=>$r->teacher_remark,'created_by'=>$r->created_by,'created_at'=>$r->created_at,'updated_at'=>$r->created_at]);
    }
}
