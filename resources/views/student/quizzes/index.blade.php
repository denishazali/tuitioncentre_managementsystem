<x-student-layout title="Quizzes">
    @if (! $student)
        <p>Your student profile has not been set up. Please contact the centre</p>
    @else 
        <h2 class="mb-4 text-2xl font-bold">Available quizzes</h2>

        <div class="mb-10 grid gap-5 md:grid-cols-2">
            @forelse ($quizzes as $quiz)
                @php
                    $existing = $quiz->attempts->first();
                @endphp

                <article class="space-y-3 rounded-xl border bg-white p-6">
                    <h3 class="text-xl font-bold">{{ $quiz->title }}</h3>

                    <p>{{ $quiz->subject->name }} — Form {{ $quiz->form_level }}</p>
                    <p>{{ $quiz->description }}</p>
                    <p>{{ $quiz->questions_count }} questions · {{ $quiz->time_limit_minutes }} minutes</p>

                    @if ($existing)
                        <a href="{{ route('student.attempts.show', $existing->id) }}" class="button button-secondary">
                            {{ $existing->submitted_at ? 'View result' : 'Open attempt' }}
                        </a>
                    @else 
                        <form method="POST" action="{{ route('student.quizzes.start', $quiz->id) }}">
                            @csrf

                            <button type="submit" class="button button-primary">Start quiz</button>
                        </form>

                        <p class="text-sm">Starting begins the timer, one attempt is allowed.</p>
                    @endif
                </article>
            @empty
                <p>No published quizzes are available for your approved subjects and form level.</p>
            @endforelse
        </div>

        <h2 class="mb-4 text-2xl font-bold">My attempts</h2>

        @forelse ($history as $attempt)
            <article class="mb-4 rounded-xl border bg-white p-5">
                <h3 class="font-bold">{{ $attempt->quiz?->title ?? 'Quiz' }}</h3>

                <p>{{ $attempt->submitted_at ? 'Score: ' . $attempt->score . '%' : 'Attempt started'}}</p>

                <a href="{{ route('student.attempts.show', $attempt->id) }}" class="button button-secondary mt-3">
                    Open attempt
                </a>
            </article>
        @empty
            <p>You have not started any quizzes.</p>
        @endforelse

        {{ $history->links() }}
    @endif
</x-student-layout>
