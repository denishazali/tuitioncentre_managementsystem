<header class="site-header">
    <div class="container navigation-container">
        <a href="{{ route('home') }}" class="brand" aria-label="Bijak Tuition Centre homepage">
            <img src ="{{ asset('favicon/graduation-logo.png') }}" alt="" class="brand-logo">
            <span>Bijak Tuition Centre</span>
        </a>

        <button type="button" class="navigation-toggle" aria-controls="public-navigation" aria-expanded="false" data-navigation-toggle>
            <span class="sr-only">Open navigation menu</span>
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav id="public-navigation" class="public-navigation" aria-label="Main navigation" data-navigation>
            <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')])>Home</a>
            <a href="{{ route('public.teachers') }}" @class(['active' => request()->routeIs('public.teachers')])>Teachers</a>
            <a href="{{ route('public.announcements') }}" @class(['active' => request()->routeIs('public.announcements')])>Announcements</a>
            <a href="{{ route('public.feedback') }}" @class(['active' => request()->routeIs('public.feedback')])>Feedback</a>
        
            @guest
                <a href="{{ route('login') }}" class="button button-secondary">Log in</a>
                <a href="{{ route('register') }}" class="button button-primary">Register</a>
            @else
                @php
                    $dashboardRoute = match (auth()->user()->role) {
                        'admin' => 'admin.dashboard',
                        'student' => 'student.dashboard',
                        default => 'parent.dashboard',
                    };
                @endphp

                <a href="{{ route($dashboardRoute) }}" class="button button-secondary">Dashboard</a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="button button-primary">Logout</button>
                </form>
            @endguest
        </nav>
    </div>
</header>
            
