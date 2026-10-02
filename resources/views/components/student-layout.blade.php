@props(['title' => 'Student portal'])

<x-layout :title="$title">
    <section class="section">
        <div class="container">
            <nav aria-label="Student navigation"
                 class="mb-8 flex flex-wrap gap-3">
                @foreach ([
                    'student.dashboard' => 'Dashboard',
                    'student.subjects.index' => 'My Subjects',
                    'student.timetable.index' => 'My Timetable',
                ] as $routeName => $label)
                    <a
                        href="{{ route($routeName) }}"
                        class="button button-secondary"
                        @if (request()->routeIs($routeName))
                            aria-current="page"
                        @endif
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            <h1 class="mb-6 text-3xl font-bold">{{ $title }}</h1>

            @include('partials.form-messages')

            {{ $slot }}
        </div>
    </section>
</x-layout>