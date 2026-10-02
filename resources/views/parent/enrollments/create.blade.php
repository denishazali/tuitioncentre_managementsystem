<x-layout title="Enrollment Selection">
    <section class="section">
        <div class="container">
            <h1 class="mb-6 text-3xl font-bold">Enrollment Selection</h1>

            @include('partials.form-messages')

            <a href="{{ route('parent.enrollments.index') }}" class="button button-secondary mb-6">
                Back to subjects and packages
            </a>

            <form method="POST" action="{{ route('parent.enrollments.preview') }}" class="max-w-2xl space-y-6 rounded-xl border bg-white p-6">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">

                @if ($package)
                    <input type="hidden" name="package_id" value="{{ $package->id }}">
                @endif

                <div>
                    <label for="student_id" class="mb-2 block font-semibold">Select your child</label>
                    <select id="student_id" name="student_id" required class="w-full rounded border p-3">
                        <option value="">Choose a child</option>
                        @foreach ($children as $child)
                            <option
                                value="{{ $child->id }}"
                                @selected(old('student_id') == $child->id)
                            >
                                {{ $child->name }} — Form {{ $child->form_level }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @php
                    $selectedIds = array_map(
                        'strval',
                        (array) old('subject_ids', [])
                    );
                @endphp

                @if ($package)
                    <div>
                        <h2 class="text-xl font-bold">{{ $package->name }}</h2>
                        <p>
                            Fixed package price:
                            RM {{ number_format($package->price, 2) }}
                        </p>
                    </div>

                    <fieldset>
                        <legend class="mb-2 font-semibold">Compulsory subjects — automatically included</legend>
                        <ul class="list-disc pl-5">
                            @forelse (
                                $subjects->where('pivot.is_compulsory', true)
                                as $subject
                            )
                                <li>{{ $subject->name }}</li>
                            @empty
                                <li>No compulsory subjects.</li>
                            @endforelse
                        </ul>
                    </fieldset>

                    @if ($package->elective_subject_limit > 0)
                        <fieldset>
                            <legend class="mb-3 font-semibold">
                                Choose up to
                                {{ $package->elective_subject_limit }}
                                elective subjects
                            </legend>

                            @forelse (
                                $subjects->where('pivot.is_compulsory', false)
                                as $subject
                            )
                                <label class="mb-3 flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        name="subject_ids[]"
                                        value="{{ $subject->id }}"
                                        @checked(in_array(
                                            (string) $subject->id,
                                            $selectedIds,
                                            true
                                        ))
                                    >
                                    <span>{{ $subject->name }}</span>
                                </label>
                            @empty
                                <p>No elective subjects are available.</p>
                            @endforelse
                        </fieldset>
                    @else
                        <p>This package includes compulsory subjects only.</p>
                    @endif
                @else
                    <fieldset>
                        <legend class="mb-3 font-semibold">Select one or more subjects</legend>

                        @forelse ($subjects as $subject)
                            <label class="mb-3 flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    name="subject_ids[]"
                                    value="{{ $subject->id }}"
                                    @checked(in_array(
                                        (string) $subject->id,
                                        $selectedIds,
                                        true
                                    ))
                                >
                                <span>
                                    {{ $subject->name }}
                                    — RM {{ number_format($subject->price, 2) }}
                                </span>
                            </label>
                        @empty
                            <p>No subjects are currently available.</p>
                        @endforelse
                    </fieldset>
                @endif

                <p>Review your choices on the next page. No enrollment will be saved yet.</p>

                <button type="submit" class="button button-primary">Review selection</button>
            </form>
        </div>
    </section>
</x-layout>