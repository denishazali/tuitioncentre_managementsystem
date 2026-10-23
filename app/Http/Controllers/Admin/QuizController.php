<?php

namespace App\Http\Controllers\Admin;

use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuizController extends AdminController
{
    protected string $module = 'quizzes';

    protected array $columns = [
        'title' => 'Title', 'subject.name' => 'Subject', 'form_level' => 'Form',
        'time_limit_minutes' => 'Minutes', 'questions_count' => 'Questions',
        'attempts_count' => 'Attempts', 'is_published' => 'Published',
    ];

    public function index(): View
    {
        return $this->listing(Quiz::with('subject')->withCount(['questions', 'attempts'])->orderBy('title'));
    }

    public function create(): View
    {
        return $this->quizForm(new Quiz(['form_level' => 1, 'time_limit_minutes' => 10]));
    }

    public function edit(Quiz $quiz): View
    {
        return $this->quizForm($quiz);
    }

    public function store(Request $request): RedirectResponse
    {
        [$attributes, $questions] = $this->validated($request);
        $quiz = DB::transaction(function () use ($attributes, $questions, $request) {
            $quiz = Quiz::create([...$attributes, 'created_by' => $request->user()->id, 'is_published' => false]);
            $quiz->questions()->createMany($questions);

            return $quiz;
        });

        return to_route('admin.quizzes.edit', $quiz)->with('success', 'Quiz draft created.');
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        [$attributes, $questions] = $this->validated($request);
        DB::transaction(function () use ($quiz, $attributes, $questions) {
            $record = Quiz::lockForUpdate()->findOrFail($quiz->id);
            $this->ensure(! $record->attempts()->exists(), 'This quiz has attempts. Create a new quiz to change its content.');
            $record->update($attributes);
            $record->questions()->delete();
            $record->questions()->createMany($questions);

            if ($record->is_published) {
                $this->publishable($record);
            }
        });

        return to_route('admin.quizzes.edit', $quiz)->with('success', 'Quiz updated.');
    }

    public function publication(Request $request, Quiz $quiz): RedirectResponse
    {
        $data = $request->validate(['is_published' => ['required', 'boolean']]);
        DB::transaction(function () use ($quiz, $data) {
            $record = Quiz::lockForUpdate()->findOrFail($quiz->id);

            if ($data['is_published']) {
                $this->publishable($record);
            }

            $record->update(['is_published' => (bool) $data['is_published']]);
        });

        return to_route('admin.quizzes.edit', $quiz)->with('success', 'Publication updated.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        DB::transaction(function () use ($quiz) {
            $record = Quiz::lockForUpdate()->findOrFail($quiz->id);
            $this->ensure(! $record->attempts()->exists(), 'This quiz has attempts. Unpublish it instead.');
            $record->delete();
        });

        return $this->saved('Quiz deleted.');
    }

    private function quizForm(Quiz $record): View
    {
        return view('admin.quizzes.form', [
            'record' => $record,
            'locked' => $record->exists && $record->attempts()->exists(),
            'subjectOptions' => Subject::orderBy('name')->get()->mapWithKeys(fn ($subject) => [
                $subject->id => $subject->name.($subject->is_active ? '' : ' (inactive)'),
            ])->all(),
            'attempts' => $record->exists ? $record->attempts()
                ->select('id', 'quiz_id', 'student_id', 'score', 'total_questions', 'correct_answers',
                    'started_at', 'submitted_at', 'expires_at')
                ->with('student')->latest()->paginate(15) : null,
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'form_level' => ['required', 'integer', 'between:1,5'],
            'time_limit_minutes' => ['required', 'integer', 'between:1,180'],
            'questions' => ['nullable', 'array', 'max:100'],
            'questions.*' => ['array:question,option_a,option_b,option_c,option_d,correct_answer'],
            'questions.*.question' => ['required', 'string', 'max:3000'],
            'questions.*.option_a' => ['required', 'string', 'max:255'],
            'questions.*.option_b' => ['required', 'string', 'max:255'],
            'questions.*.option_c' => ['required', 'string', 'max:255'],
            'questions.*.option_d' => ['required', 'string', 'max:255'],
            'questions.*.correct_answer' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
        ]);
        $questions = array_values($data['questions'] ?? []);
        unset($data['questions']);

        return [$data, $questions];
    }

    private function publishable(Quiz $quiz): void
    {
        $this->ensure($quiz->questions()->exists(), 'Add at least one complete question before publishing.');
        $this->ensure((bool) $quiz->subject?->is_active, 'Choose an active subject before publishing.');
        $this->ensure($quiz->time_limit_minutes > 0, 'A positive time limit is required.');
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['quiz' => $message]);
        }
    }
}
