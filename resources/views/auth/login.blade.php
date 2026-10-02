<x-layout title="Log in">
    <section class="section">
        <div class="container">
            <div class="form-card mx-auto max-w-xl">
                <h1 class="mb-6 text-3xl font-bold">Log in</h1>
                @include('partials.form-messages')

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf
                    <x-admin-field name="email" label="Email" type="email" autocomplete="username" required />
                    <x-admin-field name="password" label="Password" type="password" autocomplete="current-password" required />
                    <button type="submit" class="button button-primary">Log in</button>
                </form>

                <p class="mt-6">New here? <a href="{{ route('register') }}" class="text-link">Create a parent account</a>.</p>
            </div>
        </div>
    </section>
</x-layout>
