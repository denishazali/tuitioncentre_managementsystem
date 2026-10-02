<x-layout title="Teachers" description="Meet the experienced educators at Bijak Tuition Centre.">
    <section class="page-hero">
        <div class="container">
            <span class="eyebrow">Our educators</span>
            <h1>Meet our teachers</h1>
            <p>
                Dedicated educators committed to helping students understand, practise and succeed.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            @if ($teachers->isEmpty())
                <div class="empty-state">
                    <h2>No teachers available</h2>
                    <p>Teacher information will be published here soon.</p>
                </div>
                
            @else
                <div class="teacher-grid">
                    @foreach ($teachers as $teacher)
                        <article class="teacher-card reveal">
                            <img
                                src="{{ $teacher->photo_path ? asset('uploads/' . ltrim($teacher->photo_path, '/')) : asset('favicon/graduation-logo.png') }}"
                                alt="Portrait of {{ $teacher->name }}"
                                class="teacher-photo"
                                loading="lazy"
                            >

                            <div class="teacher-content">
                                <h2>{{ $teacher->name }}</h2>
                                @if ($teacher->subjects->isNotEmpty())
                                    <div class="tag-list">
                                        @foreach ($teacher->subjects as $subject)
                                            <span class="tag">{{ $subject->name }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($teacher->background)
                                    <p>{{ $teacher->background }}</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="pagination-wrapper">
                    {{ $teachers->links() }}
                </div>
            @endif
        </div>
    </section>
</x-layout>