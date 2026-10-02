<x-layout title="Home" description="Bijak Tuition Centre provides structured tuition and academic support for Form 1 through SPM students.">
    <section class="hero-section">
        <div class="container hero-grid">
            <div class="hero-content reveal">
                <span class="eyebrow">Learn with confidence</span>
                <h1>Helping students build stronger academic foundations.</h1>
                <p>
                    Personalised guidance, experienced teachers and structured
                    learning programmes for Form 1 through SPM students.
                </p>

                <div class="hero-actions">
                    <a href="{{ route('register') }}" class="button button-primary button-large">
                        Register now
                    </a>
                    <a href="{{ route('public.teachers') }}" class="button button-secondary button-large">
                        Meet our teachers
                    </a>
                </div>
            </div>

            <div class="hero-visual reveal">
                <img src="{{ asset('mainpageimages/bijakbanner2.png') }}" alt="Students learning at Bijak Tuition Centre">
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-heading reveal">
                <span class="eyebrow">Who we are</span>
                <h2>Education that goes beyond textbooks</h2>
                <p>
                    We nurture curiosity, confidence, critical thinking and
                    problem-solving skills in a supportive learning environment.
                </p>
            </div>

            <div class="feature-grid">
                <article class="feature-card reveal">
                    <div class="feature-icon">🎯</div>
                    <h3>Our mission</h3>
                    <p>
                        To provide high-quality education and personalised
                        learning experiences that help every student achieve
                        their full potential.
                    </p>
                </article>

                <article class="feature-card reveal">
                    <div class="feature-icon">🌟</div>
                    <h3>Our vision</h3>
                    <p>
                        To build independent, resilient and forward-thinking
                        learners who are prepared for future challenges.
                    </p>
                </article>

                <article class="feature-card reveal">
                    <div class="feature-icon">🤝</div>
                    <h3>Our support</h3>
                    <p>
                        Students receive structured lessons, continuous
                        feedback and guidance from dedicated teachers.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <section class="section section-muted">
        <div class="container">
            <div class="section-heading reveal">
                <span class="eyebrow">Programmes</span>
                <h2>Supporting every secondary-school stage</h2>
            </div>

            <div class="level-grid">
                @foreach ([
                    ['level' => 'Form 1', 'icon' => '📘', 'text' => 'Build a strong foundation and adapt confidently to secondary education.'],
                    ['level' => 'Form 2', 'icon' => '✏️', 'text' => 'Strengthen understanding through structured lessons and guided practice.'],
                    ['level' => 'Form 3', 'icon' => '🧠', 'text' => 'Develop critical thinking and prepare for important assessments.'],
                    ['level' => 'Form 4', 'icon' => '🎯', 'text' => 'Begin focused upper-secondary preparation with consistent academic support.'],
                    ['level' => 'SPM', 'icon' => '🏆', 'text' => 'Receive comprehensive exam preparation and targeted revision support.'],
                ] as $programme)
                    <article class="level-card reveal">
                        <span aria-hidden="true">{{ $programme['icon'] }}</span>
                        <h3>{{ $programme['level'] }}</h3>
                        <p>{{ $programme['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @if ($latestAnnouncements->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="section-heading section-heading-row reveal">
                    <div>
                        <span class="eyebrow">Latest updates</span>
                        <h2>Announcements</h2>
                    </div>
                    <a href="{{ route('public.announcements') }}" class="text-link">
                        View all announcements
                    </a>
                </div>

                <div class="card-grid">
                    @foreach ($latestAnnouncements as $announcement)
                        <article class="content-card reveal">
                            @if ($announcement->image_path)
                                <img src="{{ asset('uploads/' . ltrim($announcement->image_path, '/')) }}" alt="" class="content-card-image" loading="lazy">
                            @endif
                            <div class="content-card-body">
                                <time datetime="{{ $announcement->published_at->toDateString() }}">
                                    {{ $announcement->published_at->format('d M Y') }}
                                </time>
                                <h3>
                                    {{ $announcement->title ?: 'Announcement' }}
                                </h3>
                                <p>
                                    {{ \Illuminate\Support\Str::limit(
                                        $announcement->content,
                                        150
                                    ) }}
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="section section-muted">
        <div class="container location-grid">
            <div class="reveal">
                <span class="eyebrow">Visit us</span>
                <h2>Find Bijak Tuition Centre</h2>
                <p>
                    79, Jalan 11/62A, Bandar Menjalara,
                    52200 Kuala Lumpur.
                </p>
                <a href="https://wa.me/60173879230" class="button button-primary" target="_blank" rel="noopener noreferrer">
                    Contact us on WhatsApp
                </a>
            </div>
            <iframe
                class="location-map reveal"
                title="Bijak Tuition Centre location"
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3983.625515963874!2d101.62818717480937!3d3.1927207967825204!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cc461d97a5c8dd%3A0x5bd49ac87d7104b5!2s79%2C%20Jalan%2011%2F62a%2C%20Bandar%20Menjalara%2C%2052200%20Kuala%20Lumpur!5e0!3m2!1sen!2smy"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen
            ></iframe>
        </div>
    </section>
</x-layout>