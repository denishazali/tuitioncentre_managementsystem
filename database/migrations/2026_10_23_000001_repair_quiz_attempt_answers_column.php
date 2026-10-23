<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Repair installations that already ran the migration with the singular name.
        if (Schema::hasColumn('quiz_attempts', 'answer') && ! Schema::hasColumn('quiz_attempts', 'answers')) {
            Schema::table('quiz_attempts', function (Blueprint $table) {
                $table->renameColumn('answer', 'answers');
            });
        }
    }

    public function down(): void
    {
        // Keep the canonical name so the corrected original migration can roll back.
    }
};
