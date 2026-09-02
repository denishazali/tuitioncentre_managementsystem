<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyLegacyImport extends Command
{
    protected $signature = 'legacy:verify';
    protected $description = 'Compare legacy and Laravel record totals after import';

    public function handle(): int
    {
        $comparisons = [
            ['user','users'], ['student','students'], ['subject','subjects'],
            ['teacher','teachers'], ['bundlepack','packages'], ['payment','payments'],
            ['payment','enrollments'], ['timetable','timetables'],
            ['announcement','announcements'], ['feedback','feedback'],
            ['quiz','quizzes'], ['quiz_question','quiz_questions'],
            ['quiz_attempt','quiz_attempts'], ['student_exam','exam_results'],
        ];
        $rows = []; $ok = true;
        foreach ($comparisons as [$legacy, $target]) {
            $old = DB::connection('legacy')->table($legacy)->count();
            $new = DB::table($target)->count();
            $match = $old === $new; $ok = $ok && $match;
            $rows[] = [$legacy, $old, $target, $new, $match ? 'OK' : 'MISMATCH'];
        }
        $this->table(['Legacy table','Legacy','Laravel table','Laravel','Result'], $rows);
        $legacySelections = DB::connection('legacy')->table('student_subject')->count();
        $targetSelections = DB::table('enrollment_subject')->count();
        $this->line("Subject selections: legacy={$legacySelections}, Laravel={$targetSelections} (may differ when a student has no payment)");
        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
