<x-student-layout title="My Subjects">
    @if (! $student)
        <p role="status">
            Your student profile has not been set up. Please contact the centre.
        </p>
    @else 
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($subjects as $subject)
                <article class="space-y-3 rounded-xl border bg-white p-6">
                    <h2 class="text-xl font-bold">{{ $subject->name }}</h2>

                    <p>Approved enrollment</p>

                    @unless ($subject->is_active)
                        <p>
                            This subject is currently inactive.
                            Please contact the centre.
                        </p>
                    @endunless
                </article>
            @empty
                <p>
                    You have no approved subjects yet.
                    Ask your parent to check your enrollment status.
                </p>
            @endforelse
        </div>
    @endif
</x-student-layout>