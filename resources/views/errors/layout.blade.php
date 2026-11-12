<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Something went wrong') | Little Steps</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/interface.css') }}">
</head>
<body>
    <header class="navbar"><div class="container nav-inner"><a class="logo" href="/">Little <span>Steps</span></a><a class="btn btn-outline btn-small" href="/">Home</a></div></header>
    <main class="container section"><section class="form-box form-narrow" role="status"><p class="eyebrow">Little Steps</p><h1>@yield('heading')</h1><p>@yield('message')</p><div class="btn-row"><a class="btn btn-blue" href="/">Go to homepage</a><a class="btn btn-outline" href="/contact">Contact us</a></div></section></main>
    <footer class="footer"><div class="container"><p>This website provides general guidance and is not medical advice.</p></div></footer>
</body>
</html>
