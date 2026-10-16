<x-child-learning-layout :child="$child" title="Subjects">
    <p class="mb-6">
        Subjects from this child's approved enrollments.
    </p>

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
                This child has no approved subjects yet.
                Check My Enrollments for the enrollment status.
            </p>
        @endforelse
    </div>

    <a href="{{ route('parent.enrollments.history') }}"
        class="button button-secondary mt-6">
        My Enrollments
    </a>
</x-child-learning-layout>