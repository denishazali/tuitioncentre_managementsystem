<x-admin-layout :title="$record->exists ? 'Edit quiz' : 'Create quiz'">
    @if ($locked)
        <p class="mb-6 rounded-lg bg-yellow-100 p-4">
            Students have started this quiz.
            Its content is read-only. You can still unpublish it.
        </p>
    @endif

    @if ($record->exists)
        <form method="POST" action="{{ route('admin.quizzes.publication', $record) }}" class="mb-6">
            @csrf
            @method('PATCH')

            <input type="hidden" name="is_published" value="{{ $record->is_published ? 0 : 1 }}">

            <button class="button button-secondary" type="submit">{{ $record->is_published ? 'Unpublish quiz' : 'Publish quiz' }}</button>
        </form>
    @endif

    @php
        $rawQuestions = old(
            'questions',
            $record->exists ? $record->questions->toArray() : [[]]
        );

        $questionRows = is_array($rawQuestions)
            ? array_values(array_filter($rawQuestions, 'is_array'))
            : [];
    @endphp

    <form method="POST" action="{{ $record->exists ? route('admin.quizzes.update', $record) : route('admin.quizzes.store') }}" class="space-y-6 rounded-xl bg-white p-6">
        @csrf

        @if ($record->exists)
            @method('PUT')
        @endif

        <fieldset @disabled($locked) class="space-y-5">
            <x-admin-field name="title" label="Quiz title" :value="$record->title" maxlength="255" required />
            <x-admin-field name="description" label="Description" type="textarea" :value="$record->description" maxlength="5000" />
            <x-admin-field name="subject_id" label="Subject" type="select" :options="$subjectOptions" :value="$record->subject_id" required />
            <x-admin-field name="form_level" label="Form level" type="select" :options="array_combine(range(1, 5), range(1, 5))" :value="$record->form_level" required />
            <x-admin-field name="time_limit_minutes" label="Time limit in minutes" type="number" :value="$record->time_limit_minutes" min="1" max="180" step="1" required />

            <h2 class="text-xl font-bold">Questions</h2>

            <p>
                A draft can have no questions.
                Remove unused blank rows before saving.
                Publishing requires at least one complete question
            </p>

            <input type="hidden" name="questions" value="">

            <div id="quiz-questions" class="space-y-5">
                @foreach ($questionRows as $i => $question)
                    @include('admin.quizzes.question-fields', [
                        'i' => $i,
                        'question' => $question,
                    ])
                @endforeach
            </div>

            <button type="button" id="add-question" class="button button-secondary">
                Add question
            </button>

            <button type="submit" class="button button-primary">
                Save quiz
            </button>
        </fieldset>
    </form>

    <template id="quiz-question-template">
        @include('admin.quizzes.question-fields', [
            'i' => '__INDEX__',
            'question' => [],
        ])
    </template>

    @if ($attempts)
        <h2 class="mb-4 mt-10 text-2xl font-bold">Student attempts</h2>

        <div class="overflow-x-auto rounded-xl bg-white">
            <table class="w-full text-left">
                <thead>
                    <tr>
                        <th class="p-3" scope="col">Student</th>
                        <th class="p-3" scope="col">Status</th>
                        <th class="p-3" scope="col">Score</th>
                        <th class="p-3" scope="col">Correct</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($attempts as $attempt)
                        <tr class="border-t">
                            <td class="p-3">
                                {{ $attempt->student?->name ?? 'Student' }}
                            </td>
                            <td class="p-3">
                                @if ($attempt->submitted_at)
                                    Submitted
                                @elseif ($attempt->expires_at?->isPast())
                                    Time expired
                                @else
                                    In progress
                                @endif
                            </td>
                            <td class="p-3">
                                {{ $attempt->submitted_at ? $attempt->score . '%' : '—' }}
                            </td>
                            <td class="p-3">
                                {{ $attempt->submitted_at ? $attempt->correct_answers . '/' . $attempt->total_questions : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-4">No attempts yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $attempts->links() }}
    @endif 

    @unless ($locked)
        <script>
            (() => {
                const container = document.getElementById('quiz-questions');
                const template = document.getElementById('quiz-question-template');
                let nextIndex = @json(count($questionRows));

                document.getElementById('add-question')
                    .addEventListener('click', () => {
                        container.insertAdjacentHTML(
                            'beforeend',
                            template.innerHTML.replaceAll(
                                '__INDEX__',
                                String(nextIndex++)
                            )
                        );
                    });

                container.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-remove-question]');

                    if (button) {
                        button.closest('[data-question]').remove();
                    }
                });
            })();
        </script>
    @endunless
</x-admin-layout>
