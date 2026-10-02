<x-layout title="Register">
    <section class="section">
        <div class="container">
            <div class="form-card mx-auto max-w-xl">
                <h1 class="mb-6 text-3xl font-bold">Create a parent account</h1>
                @include('partials.form-messages')

                <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
                    @csrf
                    <x-admin-field name="name" label="Name" autocomplete="name" maxlength="255" required />
                    <x-admin-field name="email" label="Email" type="email" autocomplete="username" maxlength="255" required />
                    <x-admin-field name="password" label="Password" type="password" autocomplete="new-password" minlength="8" required />
                    <x-admin-field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required />
                    <button type="submit" class="button button-primary">Register</button>
                </form>

                <p class="mt-6">Already registered? <a href="{{ route('login') }}" class="text-link">Log in</a>.</p>
            </div>
        </div>
    </section>
</x-layout>
