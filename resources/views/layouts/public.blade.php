<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Little Steps') | Little Steps</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/interface.css') }}">
    @stack('styles')
</head>
<body class="public-page @yield('body_class')">
    <header class="navbar public-navbar">
        <div class="container nav-inner">
            <a class="logo" href="{{ url('/') }}">Little <span>Steps</span></a>
            <button class="menu-btn" type="button" aria-label="Open menu" aria-expanded="false" data-menu-toggle>Menu</button>
            <nav class="nav-links" data-main-nav aria-label="Main navigation">
                <a href="{{ url('/') }}">Home</a>
                <a href="{{ url('/about.html') }}">About</a>
                <a href="{{ route('activities.index') }}" @if(request()->routeIs('activities.*')) aria-current="page" @endif>Activities</a>
                <a href="{{ route('doctors.index') }}" @if(request()->routeIs('doctors.*')) aria-current="page" @endif>Professional Consultancy</a>
                <a href="{{ route('contact.create') }}" @if(request()->routeIs('contact.*')) aria-current="page" @endif>Contact</a>
            </nav>
            <div class="nav-account">
                @auth
                    @if (auth()->user()->hasRole(\App\Models\User::ROLE_PARENT))
                        <a href="{{ route('dashboard') }}">My dashboard</a>
                    @elseif (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}">Admin dashboard</a>
                    @else
                        <a href="{{ route('doctor.dashboard') }}">Doctor dashboard</a>
                    @endif
                @else
                    <a href="{{ route('login') }}">Log in</a>
                    <a href="{{ route('register') }}">Register</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="public-main">
        @if (session('status'))
            <div class="container">
                <div class="alert alert-success public-alert" role="status">{{ session('status') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="container">
                <div class="alert alert-error public-alert" role="alert">
                    <strong>Please check the form.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="site-footer public-footer">
        <div class="container">
            <div class="site-footer-grid">
                <section class="site-footer-about">
                    <a class="site-footer-brand" href="{{ url('/') }}">Little <span>Steps</span></a>
                    <p>A welcoming place for families to find learning activities and professional support.</p>
                    <p class="site-footer-note">Information on this site is general guidance, not medical advice.</p>
                </section>
                <nav class="site-footer-links" aria-label="Footer links">
                    <h2>Explore</h2>
                    <a href="{{ url('/') }}">Home</a>
                    <a href="{{ url('/about.html') }}">About us</a>
                    <a href="{{ route('activities.index') }}">Activities</a>
                    <a href="{{ route('doctors.index') }}">Find a doctor</a>
                    <a href="{{ route('contact.create') }}">Contact us</a>
                </nav>
                <section class="site-footer-contact">
                    <h2>Contact</h2>
                    <p><span>Address</span> Placeholder, Karachi, Pakistan</p>
                    <p><span>Email</span> <a href="mailto:hello@example.com">hello@example.com</a></p>
                    <p><span>Phone</span> <a href="tel:+923000000000">+92 300 000 0000</a></p>
                    <small>Sample contact details &mdash; replace before launch.</small>
                </section>
                <section class="site-footer-social">
                    <h2>Follow</h2>
                    <p>Placeholder links &mdash; replace with official accounts.</p>
                    <div class="site-social-links">
                        <a href="https://example.com/replace-with-instagram" target="_blank" rel="noopener noreferrer" aria-label="Instagram (placeholder link)" title="Instagram placeholder">
                            <svg aria-hidden="true" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8" class="site-social-fill"/></svg>
                        </a>
                        <a href="https://example.com/replace-with-whatsapp" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp (placeholder link)" title="WhatsApp placeholder">
                            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M20.2 11.8a8.2 8.2 0 0 1-12.1 7.1L3.5 20l1.2-4.4a8.2 8.2 0 1 1 15.5-3.8Z"/><path d="M9 8.2c.3-.6.6-.6.9-.6l.6.1.8 1.8-.1.6-.7.8c.7 1.4 1.8 2.4 3.3 3l.7-.8.5-.3 1.9.9.1.5c-.1.7-.8 1.5-1.6 1.6-1.3.2-3.4-.7-5-2.2-1.5-1.4-2.6-3.5-2.4-4.8.1-.3.4-.5.8-.6Z"/></svg>
                        </a>
                        <a href="https://example.com/replace-with-linkedin" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn (placeholder link)" title="LinkedIn placeholder">
                            <svg aria-hidden="true" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M7.5 10v7M7.5 7.3v.1M11 17v-7h3v1.1a2.5 2.5 0 0 1 2.5-1.2c1.8 0 2.5 1.2 2.5 3.2V17M11 13c0-1.8 1-3.1 2.7-3.1"/></svg>
                        </a>
                    </div>
                </section>
            </div>
            <div class="site-footer-bottom">
                <span>&copy; {{ date('Y') }} Little Steps</span>
                <span>Built with care for families and their communities.</span>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            var menuToggle = document.querySelector('[data-menu-toggle]');
            var mainNav = document.querySelector('[data-main-nav]');
            if (menuToggle && mainNav) {
                menuToggle.addEventListener('click', function () {
                    var open = mainNav.classList.toggle('open');
                    menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                });
            }
        })();
    </script>
    @stack('scripts')
</body>
</html>
