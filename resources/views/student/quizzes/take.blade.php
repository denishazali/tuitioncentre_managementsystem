<x-student-layout :title="$quizTitle">
    <p class="mb-3 text-xl font-bold">
        Time remaining: <span id="quiz-timer" role="timer">Loading...</span>
    </p>

    <p class="mb-3">
        Answers save automatically while time remains.
        Submit before the deadline.
        After expiry, only answers already saved on the server are graded.
    </p>

    <p id="save-status" role="status" class="mb-6">No new changes to save.</p>

    <noscript>
        <p class="mb-6">Automatic saving requires JavaScript. Submit this form before the deadline.</p>
    </noscript>

    <form id="quiz-attempt-form" method="POST" action="{{ route('student.attempts.submit', $attemptId) }}" class="space-y-6">

        @csrf

        @foreach ($questions as $question)
            <fieldset class="space-y-3 rounded-xl border bg-white p-6">
                <legend class="font-semibold">{{ $loop->iteration }}. {{ $question['question'] }}</legend>

                @foreach (['A', 'B', 'C', 'D'] as $letter)
                    <label class="flex items-center gap-3">
                        <input type="radio" name="answers[{{ $question['id'] }}]" value="{{ $letter }}" @checked(($answers[$question['id']] ?? null) === $letter)>
                        <span>
                            {{ $letter }}.
                            {{ $question['option_' . strtolower($letter)] }}
                        </span>
                    </label>
                @endforeach
            </fieldset>
        @endforeach

        <button type="submit" class="button button-primary">
            Submit quiz
        </button>
    </form>

    <script>
        (() => {
            const form = document.getElementById('quiz-attempt-form');
            const status = document.getElementById('save-status');
            const timer = document.getElementById('quiz-timer');

            const expiresAt = Date.parse(@json($expiresAt));
            const saveUrl = @json(route('student.attempts.save', $attemptId));
            const attemptUrl = @json(route('student.attempts.show', $attemptId));

            let queue = Promise.resolve();
            let closing = false;

            form.addEventListener('change', () => {
                const answers = {};

                for (const [name, value] of new FormData(form).entries()) {
                    const match = name.match(/^answers\[(\d+)\]$/);

                    if (match) {
                        answers[match[1]] = value;
                    }
                }

                status.textContent = 'Saving answers…';

                queue = queue.then(async () => {
                    if (closing) return;

                    const response = await fetch(saveUrl, {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                        body: JSON.stringify({ answers }),
                    });

                    if (!response.ok) {
                        throw new Error('Save failed');
                    }

                    const data = await response.json();

                    if (data.submitted) {
                        closing = true;
                        window.location.assign(data.redirect);
                        return;
                    }

                    status.textContent = 'Answers saved.';
                }).catch(() => {
                    status.textContent =
                        'Could not save automatically. Submit before time expires.';
                });
            });

            form.addEventListener('submit', () => {
                closing = true;
            });

            function tick() {
                if (closing) return;

                const seconds = Math.max(
                    0,
                    Math.ceil((expiresAt - Date.now()) / 1000)
                );

                timer.textContent =
                    String(Math.floor(seconds / 60)).padStart(2, '0')
                    + ':'
                    + String(seconds % 60).padStart(2, '0');

                if (seconds === 0) {
                    closing = true;
                    window.location.assign(attemptUrl);
                }
            }

            tick();
            setInterval(tick, 1000);
        })();
    </script>
</x-student-layout>
