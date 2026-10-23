<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\QuizAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuizFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Student $student;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $this->student = $this->makeStudent();

        $this->subject = Subject::create([
            'name' => 'Mathematics',
            'price' => '80.25',
            'is_active' => true,
        ]);
    }

    public function test_admin_creates_drafts_and_publishes_complete_quizzes(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('admin.quizzes.create'))->assertOk();

        $this->post(route('admin.quizzes.store'), $this->payload([
            'questions' => [],
            'is_published' => true,
            'created_by' => 999999,
        ]))->assertSessionHasNoErrors();

        $quiz = Quiz::sole();

        $this->assertFalse($quiz->is_published);
        $this->assertSame($this->admin->id, $quiz->created_by);

        $this->patch(route('admin.quizzes.publication', $quiz), [
            'is_published' => 1,
        ])->assertSessionHasErrors('quiz');

        $this->put(route('admin.quizzes.update', $quiz), $this->payload())
            ->assertSessionHasNoErrors();

        $this->patch(route('admin.quizzes.publication', $quiz), [
            'is_published' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertTrue($quiz->fresh()->is_published);

        $this->get(route('admin.quizzes.edit', $quiz))->assertOk();

        $this->delete(route('admin.quizzes.destroy', $quiz))
            ->assertRedirect(route('admin.quizzes.index'));

        $this->assertDatabaseCount('quizzes', 0);
        $this->assertDatabaseCount('quiz_questions', 0);
    }

    public function test_admin_editor_has_nested_option_names_and_a_save_button(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.quizzes.create'))
            ->assertOk()
            ->assertSee('name="questions[0][option_a]"', false)
            ->assertSee('name="questions[0][correct_answer]"', false)
            ->assertSee('id="quiz-question-template"', false)
            ->assertSee('Add question')
            ->assertSee('type="submit" class="button button-primary"', false)
            ->assertSee('Save quiz');
    }

    public function test_catalogue_resume_results_and_admin_report_render(): void
    {
        $quiz = $this->makeQuiz();
        $this->enroll($this->student);
        $this->actingAs($this->student->user);

        $this->get(route('student.quizzes.index'))
            ->assertOk()->assertSee('Math quiz')->assertSee('Start quiz');

        $this->post(route('student.quizzes.start', $quiz->id))->assertRedirect();
        $attempt = QuizAttempt::sole();

        $this->get(route('student.quizzes.index'))
            ->assertOk()->assertSee('Open attempt')->assertDontSee('Start quiz');

        $this->post(route('student.attempts.submit', $attempt->id), [
            'answers' => [$quiz->questions[0]->id => 'A', $quiz->questions[1]->id => 'C'],
        ])->assertRedirect();

        $this->get(route('student.attempts.show', $attempt->id))
            ->assertOk()->assertSee('100.00%')->assertSee('2 / 2')
            ->assertSee(route('student.quizzes.index'));
        $this->get(route('student.quizzes.index'))->assertOk()->assertSee('View result');

        $this->actingAs($this->admin)
            ->get(route('admin.quizzes.edit', $quiz))
            ->assertOk()->assertSee('100.00%')->assertSee('2/2')->assertSee('read-only');
        $this->get(route('admin.quizzes.index'))->assertOk()->assertSee('Math quiz');
    }

    public function test_repair_migration_preserves_answers_from_the_singular_column(): void
    {
        $quiz = $this->makeQuiz();
        $this->enroll($this->student);
        $service = $this->app->make(QuizAttemptService::class);
        $attempt = $service->start($this->student, $quiz->id);
        $answers = [$quiz->questions[0]->id => 'A'];
        $service->save($this->student, $attempt->id, $answers);

        Schema::table('quiz_attempts', fn (Blueprint $table) => $table->renameColumn('answers', 'answer'));
        $before = DB::table('quiz_attempts')->where('id', $attempt->id)->value('answer');

        $migration = require database_path('migrations/2026_10_23_000001_repair_quiz_attempt_answers_column.php');
        $migration->up();
        $migration->up(); // Fresh installations already have the correct column.

        $this->assertFalse(Schema::hasColumn('quiz_attempts', 'answer'));
        $this->assertSame($before, DB::table('quiz_attempts')->where('id', $attempt->id)->value('answers'));
        $this->assertSame($answers, $attempt->fresh()->answers);
    }

    public function test_start_requires_approval_publication_and_matching_form(): void
    {
        $quiz = $this->makeQuiz();

        $this->actingAs($this->student->user);

        $url = route('student.quizzes.start', $quiz->id);

        $this->post($url)->assertNotFound();

        $enrollment = $this->enroll($this->student, 'pending_approval');

        $enrollment->payment()->create([
            'payment_method' => 'counter',
            'payment_status' => 'paid',
            'amount' => $this->subject->price,
        ]);

        $this->post($url)->assertNotFound();

        $enrollment->update(['status' => 'approved']);
        $quiz->update(['form_level' => 3]);

        $this->post($url)->assertNotFound();

        $quiz->update(['form_level' => 2, 'is_published' => false]);

        $this->post($url)->assertNotFound();

        $quiz->update(['is_published' => true]);
        $this->subject->update(['is_active' => false]);

        $this->post($url)->assertNotFound();

        $this->subject->update(['is_active' => true]);

        $this->post($url)->assertRedirect();

        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    public function test_attempts_use_snapshots_and_ignore_client_scores(): void
    {
        $quiz = $this->makeQuiz();
        $this->enroll($this->student);

        $this->actingAs($this->student->user)
            ->post(route('student.quizzes.start', $quiz->id))
            ->assertRedirect();

        $attempt = QuizAttempt::sole();
        $deadline = $attempt->expires_at->timestamp;
        $questions = $quiz->questions;

        $this->get(route('student.attempts.show', $attempt->id))
            ->assertOk()
            ->assertDontSee('correct_answer')
            ->assertViewHas('questions', fn ($rows) =>
                collect($rows)->every(
                    fn ($row) => ! array_key_exists('correct_answer', $row)
                )
            );

        $this->post(route('student.quizzes.start', $quiz->id))
            ->assertRedirect(route('student.attempts.show', $attempt->id));

        $this->assertDatabaseCount('quiz_attempts', 1);
        $this->assertSame($deadline, $attempt->fresh()->expires_at->timestamp);

        // Simulate a change outside the admin interface.
        $questions[0]->update(['correct_answer' => 'D']);

        $this->post(route('student.attempts.submit', $attempt->id), [
            'answers' => [
                $questions[0]->id => 'A',
                $questions[1]->id => 'B',
            ],
            'score' => '100.00',
            'correct_answers' => 999,
            'total_questions' => 1,
        ])->assertRedirect();

        $this->assertSame('50.00', $attempt->fresh()->score);
        $this->assertSame(1, $attempt->fresh()->correct_answers);
        $this->assertSame(2, $attempt->fresh()->total_questions);

        $this->post(route('student.attempts.submit', $attempt->id), [
            'answers' => [
                $questions[0]->id => 'A',
                $questions[1]->id => 'C',
            ],
        ])->assertRedirect();

        $this->assertSame('50.00', $attempt->fresh()->score);
    }

    public function test_expiry_grades_saved_answers_and_ignores_late_changes(): void
    {
        $quiz = $this->makeQuiz();
        $this->enroll($this->student);

        $this->actingAs($this->student->user)
            ->post(route('student.quizzes.start', $quiz->id));

        $attempt = QuizAttempt::sole();
        $questions = $quiz->questions;

        $this->putJson(route('student.attempts.save', $attempt->id), [
            'answers' => [$questions[0]->id => 'A'],
        ])
            ->assertOk()
            ->assertJson(['submitted' => false]);

        $this->travel(61)->seconds();

        $this->post(route('student.attempts.submit', $attempt->id), [
            'answers' => [
                $questions[0]->id => 'A',
                $questions[1]->id => 'C',
            ],
        ])->assertRedirect();

        $this->assertSame('50.00', $attempt->fresh()->score);
        $this->assertNotNull($attempt->fresh()->submitted_at);

        $this->assertArrayNotHasKey(
            $questions[1]->id,
            $attempt->fresh()->answers
        );

        $this->travelBack();
    }

    public function test_attempt_ownership_and_unknown_question_ids_are_checked(): void
    {
        $quiz = $this->makeQuiz();
        $this->enroll($this->student);

        $this->actingAs($this->student->user)
            ->post(route('student.quizzes.start', $quiz->id));

        $attempt = QuizAttempt::sole();

        $this->post(route('student.attempts.submit', $attempt->id), [
            'answers' => [999999 => 'A'],
        ])->assertSessionHasErrors('answers');

        $this->assertNull($attempt->fresh()->submitted_at);

        $other = $this->makeStudent();
        $this->actingAs($other->user);

        $this->get(route('student.attempts.show', $attempt->id))
            ->assertNotFound();

        $this->putJson(route('student.attempts.save', $attempt->id), [
            'answers' => [],
        ])->assertNotFound();
    }

    public function test_started_quizzes_are_locked_but_can_be_unpublished(): void
    {
        $quiz = $this->makeQuiz();
        $this->enroll($this->student);

        $this->actingAs($this->student->user)
            ->post(route('student.quizzes.start', $quiz->id));

        $attempt = QuizAttempt::sole();

        $this->actingAs($this->admin);

        $this->put(route('admin.quizzes.update', $quiz), $this->payload([
            'title' => 'Changed title',
        ]))->assertSessionHasErrors('quiz');

        $this->delete(route('admin.quizzes.destroy', $quiz))
            ->assertSessionHasErrors('quiz');

        $this->patch(route('admin.quizzes.publication', $quiz), [
            'is_published' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertFalse($quiz->fresh()->is_published);
        $this->assertSame('Math quiz', $quiz->fresh()->title);

        $this->actingAs($this->student->user);

        $this->get(route('student.attempts.show', $attempt->id))
            ->assertOk();

        $this->post(route('student.attempts.submit', $attempt->id), [
            'answers' => [
                $quiz->questions[0]->id => 'A',
                $quiz->questions[1]->id => 'C',
            ],
        ])->assertRedirect();

        $this->assertSame('100.00', $attempt->fresh()->score);
    }

    public function test_student_routes_require_an_active_student(): void
    {
        $quiz = $this->makeQuiz();
        $this->enroll($this->student);

        $attempt = $this->app->make(QuizAttemptService::class)
            ->start($this->student, $quiz->id);

        $requests = [
            ['GET', route('student.quizzes.index')],
            ['POST', route('student.quizzes.start', $quiz->id)],
            ['GET', route('student.attempts.show', $attempt->id)],
            ['PUT', route('student.attempts.save', $attempt->id)],
            ['POST', route('student.attempts.submit', $attempt->id)],
        ];

        foreach ($requests as [$method, $url]) {
            $this->call($method, $url)->assertRedirect(route('login'));
        }

        foreach (['parent', 'admin'] as $role) {
            $this->actingAs(User::factory()->create([
                'role' => $role,
                'account_status' => 'active',
            ]));

            foreach ($requests as [$method, $url]) {
                $this->call($method, $url)->assertForbidden();
            }
        }

        $this->student->user->update(['account_status' => 'inactive']);
        $this->actingAs($this->student->user->fresh());

        foreach ($requests as [$method, $url]) {
            $this->call($method, $url)->assertForbidden();
        }
    }

    private function makeStudent(): Student
    {
        $parent = User::factory()->create([
            'role' => 'parent',
            'account_status' => 'active',
        ]);

        $user = User::factory()->create([
            'role' => 'student',
            'account_status' => 'active',
        ]);

        return Student::create([
            'user_id' => $user->id,
            'parent_id' => $parent->id,
            'name' => 'Student '.$user->id,
            'ic' => 'QUIZ-'.$user->id,
            'date_of_birth' => '2012-01-01',
            'form_level' => 2,
            'school_name' => 'Test School',
        ]);
    }

    private function enroll(
        Student $student,
        string $status = 'approved'
    ): Enrollment {
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'parent_id' => $student->parent_id,
            'type' => 'individual',
            'status' => $status,
            'total_amount' => $this->subject->price,
        ]);

        $enrollment->subjects()->attach($this->subject->id, [
            'price_at_enrollment' => $this->subject->price,
        ]);

        return $enrollment;
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'Math quiz',
            'description' => 'Practice questions',
            'subject_id' => $this->subject->id,
            'form_level' => 2,
            'time_limit_minutes' => 1,
            'questions' => [
                [
                    'question' => 'What is 2 + 2?',
                    'option_a' => '4',
                    'option_b' => '3',
                    'option_c' => '2',
                    'option_d' => '1',
                    'correct_answer' => 'A',
                ],
                [
                    'question' => 'What is 3 + 3?',
                    'option_a' => '4',
                    'option_b' => '5',
                    'option_c' => '6',
                    'option_d' => '7',
                    'correct_answer' => 'C',
                ],
            ],
        ], $overrides);
    }

    private function makeQuiz(): Quiz
    {
        $data = $this->payload();
        $questions = $data['questions'];

        unset($data['questions']);

        $quiz = Quiz::create([
            ...$data,
            'created_by' => $this->admin->id,
            'is_published' => true,
        ]);

        $quiz->questions()->createMany($questions);

        return $quiz->load('questions');
    }
}
