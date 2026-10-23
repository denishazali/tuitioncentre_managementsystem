<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $guarded = [];

    protected $hidden = ['question_snapshot'];

    protected function casts(): array
    {
        return [
            'score'=>'decimal:2',
            'total_questions' => 'integer',
            'correct_answers' => 'integer',
            'started_at'=> 'datetime',
            'submitted_at'=>'datetime',
            'expires_at' => 'datetime',
            'question_snapshot' => 'array',
            'answers' => 'array',
        ];
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
