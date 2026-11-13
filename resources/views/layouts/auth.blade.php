<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Little Steps') | Little Steps</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/interface.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/auth.css') }}">
</head>
<body class="auth-page">
    <header class="navbar auth-navbar">
        <div class="container nav-inner">
            <a class="logo" href="{{ url('/') }}">Little <span>Steps</span></a>
            <button class="menu-btn" type="button" aria-label="Open menu" data-menu-toggle>Menu</button>
            <nav class="nav-links" data-main-nav aria-label="Main navigation">
                <a href="{{ url('/') }}">Home</a>
                <a href="{{ url('/about.html') }}">About</a>
                <a href="{{ route('activities.index') }}">Activities</a>
                <a href="{{ route('doctors.index') }}">Professional Consultancy</a>
                <a href="{{ route('contact.create') }}">Contact</a>
            </nav>
            <div class="nav-account">
                <a class="auth-help-link" href="{{ route('contact.create') }}">Need help?</a>
            </div>
        </div>
    </header>

    <main class="auth-main">
        <div class="container auth-shell @yield('shell_class')">
            <aside class="auth-story" aria-label="About Little Steps">
                <div class="auth-story-content">
                    <span class="auth-eyebrow">@yield('story_eyebrow', 'Little Steps community')</span>
                    <h1>@yield('story_heading', 'Every child. Every step. Together.')</h1>
                    <p>@yield('story_text', 'A calm, welcoming place for families to find learning activities, trusted professionals, and practical support.')</p>

                    <ul class="auth-points">
                        @hasSection('story_points')
                            @yield('story_points')
                        @else
                            <li>
                                <span class="auth-point-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                <span><strong>Practical activities</strong><small>Simple ideas designed for real family routines.</small></span>
                            </li>
                            <li>
                                <span class="auth-point-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                <span><strong>Trusted professionals</strong><small>Verified doctors and therapists in one place.</small></span>
                            </li>
                            <li>
                                <span class="auth-point-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                <span><strong>Private family space</strong><small>Your child's progress stays secure and personal.</small></span>
                            </li>
                        @endif
                    </ul>

                    <div class="auth-story-stats">
                        <div><strong>50+</strong><span>Home activities</span></div>
                        <div><strong>Verified</strong><span>Healthcare team</span></div>
                        <div><strong>Free</strong><span>For all families</span></div>
                    </div>

                    <figure class="auth-story-photo">
                        <img src="{{ asset('assets/images/hero.jpeg') }}" alt="A family spending time together">
                        <figcaption>Support that meets you where you are.</figcaption>
                    </figure>
                </div>
            </aside>

            <section class="auth-panel" aria-label="Account access">
                <div class="auth-card @yield('card_class')">
                    <div class="auth-card-head">
                        <span class="auth-role">@yield('role_label', 'Family &amp; caregiver')</span>
                        <h2>@yield('form_heading', 'Welcome back')</h2>
                        <p>@yield('form_subheading', 'Sign in to continue to your Little Steps account.')</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success auth-alert" role="status">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-error auth-alert" role="alert">
                            <strong>Please check the form.</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>

                @hasSection('switch_text')
                    <p class="auth-switch">@yield('switch_text')</p>
                @endif
            </section>
        </div>
    </main>

    <footer class="auth-footer">
        <div class="container auth-footer-inner">
            <span>&copy; {{ date('Y') }} Little Steps</span>
            <span class="auth-footer-links">
                <a href="{{ url('/') }}">Home</a>
                <a href="{{ route('activities.index') }}">Activities</a>
                <a href="{{ route('doctors.index') }}">Doctors</a>
                <a href="{{ route('contact.create') }}">Contact</a>
            </span>
        </div>
    </footer>

    <script>
        document.querySelectorAll('[data-toggle-password]').forEach(function (toggle) {
            var field = document.getElementById(toggle.dataset.togglePassword);
            if (!field) return;

            toggle.addEventListener('click', function () {
                var visible = field.type === 'password';
                field.type = visible ? 'text' : 'password';
                toggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
                toggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
            });
        });

        var menuToggle = document.querySelector('[data-menu-toggle]');
        var mainNav = document.querySelector('[data-main-nav]');
        if (menuToggle && mainNav) {
            menuToggle.addEventListener('click', function () {
                var open = mainNav.classList.toggle('open');
                menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }

        document.addEventListener('click', function (event) {
            document.querySelectorAll('.profile-menu[open]').forEach(function (menu) {
                if (!menu.contains(event.target)) menu.removeAttribute('open');
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.profile-menu[open]').forEach(function (menu) {
                    menu.removeAttribute('open');
                });
            }
        });
    </script>
</body>
</html>
