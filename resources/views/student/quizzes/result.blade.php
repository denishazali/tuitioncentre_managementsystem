<x-student-layout title="Quiz result">
    <article class="max-w-2xl space-y-4 rounded-xl border bg-white p-6">
        <h2>{{ $attempt->quiz?->title ?? 'Quiz' }}</h2>

        <p class="text-3xl font-bold">{{ $attempt->score }}%</p>

        <p>
            Correct answers:
            {{ $attempt->correct_answers }} / {{ $attempt->total_questions }}
        </p>

        <p>This attempt has been submitted and cannot be changed.</p>

        <a href="{{ route('student.quizzes.index') }}" class="button button-secondary">
            Back to quizzes
        </a>
    </article>
</x-student-layout>
