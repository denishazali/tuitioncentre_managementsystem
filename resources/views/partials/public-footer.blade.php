<footer class="site-footer">
    <div class="container footer-grid">
        <section>
            <a href="{{ route('home') }}" class="footer-brand">
                <img src="{{ asset('favicon/graduation-logo.png') }}" alt="">
                <span>Bijak Tuition Centre</span>
            </a>

            <p>Structured lessons, experienced educators and continuous
               academic support to help every student progress confidently.</p>
        </section>

        <section>
            <h2>Explore</h2>

            <ul>
                <li><a href="{{ route('home') }}">Home</a></li>
                <li><a href="{{ route('public.teachers') }}">Teachers</a></li>
                <li><a href="{{ route('public.announcements') }}">Announcements</a></li>
                <li><a href="{{ route('public.feedback') }}">Feedback</a></li>
            </ul>
        </section>

        <section>
            <h2>Contact</h2>

            <address>79, Jalan 11/62A,<br>
                Bandar Menjalara,<br>
                52200 Kuala Lumpur
            </address>

            <p>
                <a href="tel:+60173879230">+6017-387 9230</a>
            </p>
        </section>
    </div>

    <div class="container footer-bottom">
        <p>
            &copy; {{ now()->year }} Bijak Tuition Centre.
            All rights reserved.
        </p>
    </div>
</footer>
