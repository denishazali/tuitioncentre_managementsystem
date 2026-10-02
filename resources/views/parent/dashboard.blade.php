<x-layout title="Parent dashboard">
    <section class="section">
        <div class="container">
            <h1 class="mb-6 text-3xl font-bold">Parent dashboard</h1>
            @include('partials.form-messages')
            <p class="mb-6">Welcome, {{ auth()->user()->name }}.</p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('public.teachers') }}" class="button button-secondary">Meet our teachers</a>
                <a href="{{ route('public.announcements') }}" class="button button-primary">Announcements</a>
                <a href="{{ route('public.feedback') }}" class="button button-secondary">Send feedback</a>
                <a href="{{ route('parent.children.index') }}" class="button button-primary">My Children</a>
                <a href="{{ route('parent.enrollments.index') }}" class="button button-primary">Subjects and Packages</a>
                <a href="{{ route('parent.enrollments.history') }}" class="button button-primary">My Enrollments</a>
            </div>
        </div>
    </section>
</x-layout>
