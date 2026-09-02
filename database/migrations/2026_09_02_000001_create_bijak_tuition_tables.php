<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id(); $table->string('name', 100)->unique();
            $table->decimal('price', 10, 2)->unsigned();
            $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('teachers', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email')->unique();
            $table->string('photo_path')->nullable(); $table->text('background')->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('subject_teacher', function (Blueprint $table) {
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->timestamps(); $table->primary(['subject_id', 'teacher_id']);
        });
        Schema::create('packages', function (Blueprint $table) {
            $table->id(); $table->string('name', 100)->unique();
            $table->unsignedTinyInteger('elective_subject_limit');
            $table->decimal('price', 10, 2)->unsigned();
            $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('package_subject', function (Blueprint $table) {
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_compulsory')->default(false); $table->timestamps();
            $table->primary(['package_id', 'subject_id']);
        });
        Schema::create('students', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('users')->restrictOnDelete();
            $table->string('name'); $table->string('ic', 50)->index();
            $table->date('date_of_birth'); $table->unsignedTinyInteger('form_level')->index();
            $table->string('school_name'); $table->timestamps();
        });
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id(); $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['individual', 'bundle']);
            $table->enum('status', ['draft', 'pending_payment', 'pending_approval', 'approved', 'rejected', 'cancelled'])->default('draft')->index();
            $table->decimal('total_amount', 10, 2)->unsigned()->default(0);
            $table->text('rejection_reason')->nullable(); $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable(); $table->timestamps();
        });
        Schema::create('enrollment_subject', function (Blueprint $table) {
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->decimal('price_at_enrollment', 10, 2)->unsigned(); $table->timestamps();
            $table->primary(['enrollment_id', 'subject_id']);
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('enrollment_id')->unique()->constrained()->restrictOnDelete();
            $table->enum('payment_method', ['card', 'counter']);
            $table->enum('payment_status', ['pending', 'paid', 'rejected', 'failed'])->default('pending')->index();
            $table->decimal('amount', 10, 2)->unsigned(); $table->string('receipt_path')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable(); $table->timestamp('approved_at')->nullable(); $table->timestamps();
        });
        Schema::create('timetables', function (Blueprint $table) {
            $table->id(); $table->enum('day', ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']);
            $table->time('start_time'); $table->unsignedSmallInteger('duration_minutes');
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('form_level'); $table->timestamps();
            $table->index(['teacher_id', 'day', 'start_time']);
            $table->index(['form_level', 'day', 'start_time']);
        });
        Schema::create('announcements', function (Blueprint $table) {
            $table->id(); $table->string('title')->nullable(); $table->text('content');
            $table->string('image_path')->nullable(); $table->timestamp('published_at')->nullable()->index(); $table->timestamps();
        });
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id(); $table->string('title'); $table->text('description')->nullable();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('form_level'); $table->unsignedSmallInteger('time_limit_minutes')->default(10);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_published')->default(false); $table->timestamps();
            $table->index(['subject_id', 'form_level']);
        });
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id(); $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->text('question'); $table->string('option_a'); $table->string('option_b');
            $table->string('option_c'); $table->string('option_d');
            $table->enum('correct_answer', ['A','B','C','D']); $table->timestamps();
        });
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id(); $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2)->unsigned()->default(0);
            $table->unsignedSmallInteger('total_questions')->default(0);
            $table->unsignedSmallInteger('correct_answers')->default(0);
            $table->timestamp('started_at')->nullable(); $table->timestamp('submitted_at')->nullable();
            $table->timestamps(); $table->unique(['quiz_id', 'student_id']);
        });
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id(); $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('exam_type', 100); $table->decimal('score', 5, 2)->unsigned()->nullable();
            $table->string('grade', 10)->nullable(); $table->text('teacher_remark')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps(); $table->index(['student_id', 'subject_id']);
        });
        Schema::create('feedback', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email');
            $table->string('phone', 30)->nullable(); $table->text('message');
            $table->enum('status', ['new','reviewed','resolved'])->default('new')->index(); $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['feedback','exam_results','quiz_attempts','quiz_questions','quizzes','announcements','timetables','payments','enrollment_subject','enrollments','students','package_subject','packages','subject_teacher','teachers','subjects'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
