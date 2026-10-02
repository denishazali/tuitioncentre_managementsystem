<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_link_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Parent-supplied information, verified by an administrator.
            $table->string('student_name');
            $table->string('student_ic', 50);
            $table->text('message')->nullable();

            $table->string('status')->default('pending')->index();

            // Filled only after an administrator approves the request.
            $table->foreignId('student_id')
                ->nullable()
                ->constrained('students')
                ->restrictOnDelete();

            $table->foreignId('previous_parent_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_link_requests');
    }
};