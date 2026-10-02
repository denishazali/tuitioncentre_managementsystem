<x-student-layout title="Student dashboard">
    <p class="mb-6">Welcome, {{ auth()->user()->name }}.</p>

    @if (! $student)
        <p role="status" class="mb-6 rounded-lg bg-yellow-100 p-4">
            Your student profile has not been set up. Please contact the centre.
        </p>

    @else 
        <p class="mb-6">
            {{ $student->name }} — Form {{ $student->form_level }}
        </p>

        <div class="mb-8 grid gap-5 sm:grid-cols-2">
            <a href="{{ route('student.subjects.index') }}"
               class="rounded-xl border bg-white p-6">
                <h2 class="text-lg font-semibold">Approved subjects</h2>
                <p class="mt-3 text-4xl font-bold">{{ $subjectCount }}</p>
            </a>

            <a href="{{ route('student.timetable.index') }}"
               class="rounded-xl border bg-white p-6">
                <h2 class="text-lg font-semibold">Weekly classes</h2>
                <p class="mt-3 text-4xl font-bold">{{ $classCount }}</p>
            </a>
        </div>

        @if ($subjectCount === 0)
            <p class="mb-6">
                You have no approved subjects yet.
                Ask your parent to check your enrollment status.
            </p>
        @elseif ($classCount === 0)
            <p class="mb-6">
                No timetable slots are available for your subjects and form level.
                Please contact the centre for your schedule.
            </p>
        @endif
    @endif

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('public.teachers') }}"
           class="button button-secondary">
           Meet our teachers
        </a>

        <a href="{{ route('public.announcements') }}"
           class="button button-secondary">
           Announcements
        </a>

        <a href="{{ route('public.feedback') }}"
           class="button button-secondary">
           Send feedback
        </a>
    </div>
</x-student-layout>
