<x-layout title="Subjects and Packages">
    <section class="section">
        <div class="container">
            <h1 class="mb-6 text-3xl font-bold">Subjects and Packages</h1>

            @include('partials.form-messages')

            <a href="{{ route('parent.enrollments.history') }}" class="button button-primary">
                My Enrollments
            </a>

            <section class="mb-10">
                <h2 class="mb-4 text-2xl font-bold">Individual subjects</h2>

                <p class="mb-4">Choose your subjects. The total is the sum of their prices.</p>

                <div class="mb-5 grid gap-4 md:grid-cols-3">
                    @forelse ($subjects as $subject)
                        <article class="rounded-xl border bg-white p-5">
                            <h3 class="text-xl font-bold">{{ $subject->name }}</h3>
                            <p>RM {{ number_format($subject->price, 2) }}</p>
                        </article>
                    @empty
                        <p>No subjects are currently available.</p>
                    @endforelse
                </div>

                @if ($subjects->isNotEmpty())
                    <a
                        href="{{ route('parent.enrollments.create', [
                            'type' => 'individual',
                        ]) }}"
                        class="button button-primary"
                    >
                        Choose individual subjects
                    </a>
                @endif
            </section>

            <section>
                <h2 class="mb-4 text-2xl font-bold">Packages</h2>

                <div class="grid gap-6 md:grid-cols-2">
                    @forelse ($packages as $package)
                        <article class="rounded-xl border bg-white p-6">
                            <h3 class="text-xl font-bold">{{ $package->name }}</h3>
                            
                            <p class="mb-4">Package price: RM {{ number_format($package->price, 2) }}</p>

                            <h4 class="font-semibold">Compulsory subjects</h4>

                            <ul class="mb-4 list-disc pl-5">
                                @forelse (
                                    $package->subjects->where('pivot.is_compulsory', true)
                                    as $subject
                                )
                                    <li>{{ $subject->name }}</li>
                                @empty
                                    <li>No compulsory subjects.</li>
                                @endforelse
                            </ul>

                            <h4 class="font-semibold">Electives — choose up to {{ $package->elective_subject_limit }}</h4>

                            <ul class="mb-5 list-disc pl-5">
                                @forelse (
                                    $package->subjects->where('pivot.is_compulsory', false)
                                    as $subject
                                )
                                    <li>{{ $subject->name }}</li>
                                @empty
                                    <li>No elective subjects.</li>
                                @endforelse
                            </ul>

                            <a
                                href="{{ route('parent.enrollments.create', [
                                    'type' => 'bundle',
                                    'package_id' => $package->id,
                                ]) }}"
                                class="button button-primary"
                            >
                                Choose this package
                            </a>
                        </article>
                    @empty
                        <p>No packages are currently available.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </section>
</x-layout>