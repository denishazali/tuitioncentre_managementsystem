<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\Student;
use App\Services\QuizAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function __construct(private QuizAttemptService $attempts) {}

    public function index(Request $request): View
    {
        $student = $request->user()->studentProfile;

        return view('student.quizzes.index', [
            'student' => $student,
            'quizzes' => $this->attempts->available($student)->with('subject')->withCount('questions')
                ->with(['attempts' => fn ($query) => $query->where('student_id', $student?->id ?? 0)
                    ->select('id', 'quiz_id', 'student_id', 'submitted_at', 'expires_at')])
                ->orderBy('title')->get(),
            'history' => QuizAttempt::where('student_id', $student?->id ?? 0)
                ->select('id', 'quiz_id', 'student_id', 'score', 'total_questions', 'correct_answers',
                    'started_at', 'submitted_at', 'expires_at')
                ->with('quiz:id,title')->latest()->paginate(15),
        ]);
    }

    public function start(Request $request, int $quiz): RedirectResponse
    {
        $attempt = $this->attempts->start($this->student($request), $quiz);

        return to_route('student.attempts.show', $attempt->id);
    }

    public function show(Request $request, int $attempt): View
    {
        $record = $this->attempts->read($this->student($request), $attempt);

        if ($record->submitted_at) {
            return view('student.quizzes.result', ['attempt' => $record]);
        }

        return view('student.quizzes.take', [
            'attemptId' => $record->id,
            'quizTitle' => $record->quiz?->title ?? 'Quiz',
            'expiresAt' => $record->expires_at->toIso8601String(),
            'questions' => array_map(fn ($question) => Arr::except($question, ['correct_answer']), $record->question_snapshot),
            'answers' => $record->answers ?? [],
        ]);
    }

    public function save(Request $request, int $attempt): JsonResponse
    {
        $record = $this->attempts->save($this->student($request), $attempt, $this->answers($request));

        return response()->json([
            'submitted' => $record->submitted_at !== null,
            'redirect' => route('student.attempts.show', $record->id),
        ]);
    }

    public function submit(Request $request, int $attempt): RedirectResponse
    {
        $record = $this->attempts->save($this->student($request), $attempt, $this->answers($request), true);

        return to_route('student.attempts.show', $record->id);
    }

    private function student(Request $request): Student
    {
        return $request->user()->studentProfile ?? abort(403, 'A student profile is required.');
    }

    private function answers(Request $request): array
    {
        return $request->validate([
            'answers' => ['sometimes', 'array', 'max:100'],
            'answers.*' => ['nullable', Rule::in(['A', 'B', 'C', 'D'])],
        ])['answers'] ?? [];
    }
}
