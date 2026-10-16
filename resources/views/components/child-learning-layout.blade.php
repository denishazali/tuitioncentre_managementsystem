@props([
    'child',
    'title' => 'Learning',
])

<x-layout :title="$child->name . ' — ' . $title">
    <section class="section">
        <div class="container">
            <nav aria-label="Child learning navigation"
                class="mb-8 flex flex-wrap gap-3">
                <a href="{{ route('parent.children.index') }}" class="button button-secondary">
                    My Children
                </a>

                <a href="{{ route('parent.children.subjects', ['child' => $child->id,]) }}" class="button button-secondary"
                    @if (request()->routeIs('parent.children.subjects'))
                        aria-current="page"
                    @endif
                >
                    Approved subjects
                </a>

                <a href="{{ route('parent.children.timetable', ['child' => $child->id,]) }}" class="button button-secondary"
                    @if (request()->routeIs('parent.children.timetable'))
                        aria-current="page"
                    @endif
                >
                    Timetable
                </a>
            </nav>

            <h1 class="mb-3 text-3xl font-bold">
                {{ $child->name }} — {{ $title }}
            </h1>

            <p class="mb-6">
                Form {{ $child->form_level }} · {{ $child->school_name }}
            </p>

            @include('partials.form-messages')

            {{ $slot }}
        </div>
    </section>
</x-layout>