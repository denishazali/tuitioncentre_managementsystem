<x-layout title="Announcement" description="Read the latest updates and announcements from Bijak Tuition Centre.">
    <section class="page-hero">
        <div class="container">
            <span class="eyebrow">Latest information</span>
            <h1>Announcements</h1>
            <p>Important updates, notices and events for students and parents.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            @if ($announcements->isEmpty())
                <div class="empty-state">
                    <h2>No announcements available</h2>
                    <p>Please check again later for new updates</p>
                </div>
            @else
                <div class="announcement-list">
                    @foreach ($announcements as $announcement)
                        <article class="announcement-card reveal">
                            @if ($announcement->image_path)
                                <div class="announcement-image">
                                    <img
                                        src="{{ asset('uploads/' . ltrim($announcement->image_path, '/')) }}"
                                        alt=""
                                        loading="lazy"
                                    >
                                </div>
                            @endif

                            <div class="announcement-content">
                                <time datetime="{{ $announcement->published_at->toDateString() }}">
                                    {{ $announcement->published_at->format('d F Y')}}
                                </time>
                                <h2>{{ $announcement->title ?: 'Announcement' }}</h2>
                                <div class="prose">{!! nl2br(e($announcement->content)) !!}</div>
                            </div>
                        </article>    
                    @endforeach
                </div>

                <div class="pagination-wrapper">
                    {{ $announcements->links() }}
                </div>
            @endif
        </div>
    </section>
</x-layout>
                        
