<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizAttemptService
{
    public function __construct(private StudentLearningService $learning) {}

    public function available(?Student $student): Builder
    {
        return Quiz::query()
            ->where('is_published', true)
            ->where('time_limit_minutes', '>', 0)
            ->where('form_level', $student?->form_level ?? 0)
            ->whereIn('subject_id', $this->learning->approvedSubjects($student)->select('subjects.id'))
            ->whereHas('subject', fn (Builder $query) => $query->where('is_active', true))
            ->whereHas('questions');
    }

    public function start(Student $student, int $quizId): QuizAttempt
    {
        return DB::transaction(function () use ($student, $quizId) {
            $quiz = $this->available($student)->lockForUpdate()->findOrFail($quizId);
            $existing = $quiz->attempts()->where('student_id', $student->id)->lockForUpdate()->first();

            if ($existing) {
                return $existing;
            }

            $questions = $quiz->questions()->orderBy('id')->get([
                'id', 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer',
            ])->toArray();
            $started = now();

            return $quiz->attempts()->create([
                'student_id' => $student->id,
                'started_at' => $started,
                'expires_at' => $started->copy()->addMinutes((int) $quiz->time_limit_minutes),
                'question_snapshot' => $questions,
                'answers' => [],
                'total_questions' => count($questions),
                'correct_answers' => 0,
                'score' => '0.00',
            ]);
        }, 3);
    }

    public function read(Student $student, int $attemptId): QuizAttempt
    {
        return $this->change($student, $attemptId, [], false, false);
    }

    public function save(Student $student, int $attemptId, array $answers, bool $submit = false): QuizAttempt
    {
        return $this->change($student, $attemptId, $answers, true, $submit);
    }

    private function change(Student $student, int $attemptId, array $answers, bool $write, bool $submit): QuizAttempt
    {
        return DB::transaction(function () use ($student, $attemptId, $answers, $write, $submit) {
            $attempt = $student->quizAttempts()->with('quiz')->lockForUpdate()->findOrFail($attemptId);

            if ($attempt->submitted_at) {
                return $attempt;
            }

            abort_unless($attempt->started_at && $attempt->expires_at && ! empty($attempt->question_snapshot),
                409, 'This imported attempt cannot be resumed. Please contact the centre.');

            // Late requests can only grade answers saved before the deadline.
            if (now()->greaterThanOrEqualTo($attempt->expires_at)) {
                return $this->finish($attempt, $attempt->answers ?? []);
            }

            if ($write) {
                $allowed = array_map('strval', array_column($attempt->question_snapshot, 'id'));

                if (array_diff(array_map('strval', array_keys($answers)), $allowed)) {
                    throw ValidationException::withMessages([
                        'answers' => 'An answer refers to an unknown question.',
                    ]);
                }

                $attempt->answers = array_filter($answers, fn ($answer) => $answer !== null);

                if ($submit) {
                    return $this->finish($attempt, $attempt->answers);
                }

                $attempt->save();
            }

            return $attempt;
        }, 3);
    }

    private function finish(QuizAttempt $attempt, array $answers): QuizAttempt
    {
        $questions = $attempt->question_snapshot;
        $correct = 0;

        foreach ($questions as $question) {
            if (($answers[$question['id']] ?? null) === $question['correct_answer']) {
                $correct++;
            }
        }

        $attempt->update([
            'answers' => $answers,
            'total_questions' => count($questions),
            'correct_answers' => $correct,
            'score' => number_format($correct * 100 / count($questions), 2, '.', ''),
            'submitted_at' => now(),
        ]);

        return $attempt;
    }
}
