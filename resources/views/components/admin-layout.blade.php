@props(['title' => 'Administration'])

<x-layout :title="$title">
    <section class="section">
        <div class="container">
            <nav aria-label="Administration" class="mb-8 flex flex-wrap gap-3">
                <a href="{{ route('admin.dashboard') }}" class="button button-secondary">
                    Dashboard
                </a>

                @foreach ([
                    'subjects',
                    'packages',
                    'teachers',
                    'announcements',
                    'users',
                    'timetables',
                    'enrollments',
                    'quizzes'
                ] as $module)
                    <a 
                        href="{{ route('admin.' . $module . '.index') }}" 
                        class="button button-secondary" 
                        @if (request()->routeIs('admin.' . $module . '.*'))
                            aria-current="page"
                        @endif
                    >
                        {{ ucfirst($module) }}
                    </a>
                @endforeach

                <a 
                    href="{{ route('admin.student-link-requests.index') }}"
                    class="button button-secondary"
                >
                    Student Link Requests
                </a>
            </nav>

            <h1 class="mb-6 text-3xl font-bold">{{ $title }}</h1>

            @if (session('success'))
                <div role="status" class="mb-6 rounded-lg bg-green-100 p-4 text-green-900">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mb-6 rounded-lg bg-red-100 p-4 text-red-900">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </div>
    </section>
</x-layout>

                        
                    