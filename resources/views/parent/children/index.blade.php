<x-layout title="My Children">
    <section class="section">
        <div class="container">
            <h1 class="mb-6 text-3xl font-bold">My Children</h1>

            @include('partials.form-messages')

            <div class="mb-10 grid gap-4 md:grid-cols-2">
                @forelse ($children as $child)
                    <article class="rounded-xl border bg-white p-5">
                        <h2 class="text-xl font-bold">{{ $child->name }}</h2>
                        <p>Form {{ $child->form_level }}</p>
                        <p>{{ $child->school_name }}</p>
                        <p>Student login: {{ $child->user->email }}</p>
                    </article>
                @empty
                    <p>No children are linked to your account yet.</p>
                @endforelse
            </div>

            <div class="grid gap-8 lg:grid-cols-2">
                <section class="rounded-xl border bg-white p-6">
                    <h2 class="mb-4 text-2xl font-bold">Register a new child</h2>
                    <p class="mb-4">
                        This creates a student profile and login account.
                        Use a separate email address for the student.
                    </p>

                    <form method="POST" action="{{ route('parent.children.store') }}" class="space-y-4">
                        @csrf

                        <label class="block">
                            Full name
                            <input name="name" value="{{ old('name') }}" maxlength="255" required class="block w-full rounded border p-2">
                        </label>

                        <label class="block">
                            IC / passport number
                            <input name="ic" value="{{ old('ic') }}" maxlength="50" required class="block w-full rounded border p-2">
                        </label>

                        <label class="block">
                            Date of birth
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required class="block w-full rounded border p-2">
                        </label>

                        <label class="block">
                            Form
                            <select name="form_level" required class="block w-full rounded border p-2">
                                @foreach (range(1, 5) as $level)
                                    <option
                                        value="{{ $level }}"
                                        @selected(old('form_level', 1) == $level)
                                    >
                                        Form {{ $level }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            School name
                            <input name="school_name" value="{{ old('school_name') }}" maxlength="255" required class="block w-full rounded border p-2">
                        </label>

                        <label class="block">
                            Student email
                            <input type="email" name="email" value="{{ old('email') }}" maxlength="255" required class="block w-full rounded border p-2">
                        </label>

                        <label class="block">
                            Student password
                            <input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required class="block w-full rounded border p-2">
                        </label>

                        <label class="block">
                            Confirm password
                            <input type="password" name="password_confirmation" minlength="8" maxlength="72" autocomplete="new-password" required class="block w-full rounded border p-2">
                        </label>

                        <button class="button button-primary" type="submit">
                            Register child
                        </button>
                    </form>
                </section>

                <section class="rounded-xl border bg-white p-6">
                    <h2 class="mb-4 text-2xl font-bold">Link an existing student</h2>
                    <p class="mb-4">
                        Already registered with the centre?
                        Submit a request for administrator verification.
                    </p>

                    <form method="POST" action="{{ route('parent.student-link-requests.store') }}" class="space-y-4">
                        @csrf

                        <label class="block">
                            Student full name
                            <input name="student_name" value="{{ old('student_name') }}" maxlength="255" required class="block w-full rounded border p-2">
                        </label>

                        <label class="block">
                            Student IC / passport number
                            <input name="student_ic" value="{{ old('student_ic') }}" maxlength="50" required class="block w-full rounded border p-2">
                        </label>

                        <label class="block">
                            Additional information
                            <textarea name="message" rows="4" maxlength="1000" class="block w-full rounded border p-2">{{ old('message') }}</textarea>
                        </label>

                        <button class="button button-primary" type="submit">
                            Submit link request
                        </button>
                    </form>
                </section>
            </div>

            <section class="mt-10">
                <h2 class="mb-4 text-2xl font-bold">My link requests</h2>

                @forelse ($linkRequests as $linkRequest)
                    <article class="mb-3 rounded-xl border bg-white p-4">
                        <h3 class="font-bold">
                            {{ $linkRequest->student_name }}
                        </h3>
                        <p>Status: {{ ucfirst($linkRequest->status) }}</p>
                        <p>
                            Submitted:
                            {{ $linkRequest->created_at->format('d M Y') }}
                        </p>
                    </article>
                @empty
                    <p>You have not submitted any link requests.</p>
                @endforelse
            </section>
        </div>
    </section>
</x-layout>