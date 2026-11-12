<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IntendedRedirect
{
    public static function parent(Request $request, string $fallback = '/'): RedirectResponse
    {
        return self::toAllowedPath($request, $fallback, '~^/(?:$|about\.html$|activities(?:/[^?#]*)?$|doctors(?:/\d+(?:/book)?)?$|appointments(?:/\d+(?:/(?:payment-return|payment-cancel|slots))?)?$|my-appointments$|appointment-history$|doctors-consulted$|payment-history$|payments/\d+/receipt$|dashboard(?:/[^?#]*)?)~');
    }

    public static function administrator(Request $request, string $fallback): RedirectResponse
    {
        return self::toAllowedPath($request, $fallback, '~^/admin(?:/[^?#]*)?$~');
    }

    private static function toAllowedPath(Request $request, string $fallback, string $pattern): RedirectResponse
    {
        $intended = $request->session()->pull('url.intended');
        if (! is_string($intended) || $intended === '' || str_starts_with($intended, '//')) {
            return redirect()->to($fallback);
        }

        $path = parse_url($intended, PHP_URL_PATH);
        if (! is_string($path) || ! preg_match($pattern, $path)) {
            return redirect()->to($fallback);
        }

        $query = parse_url($intended, PHP_URL_QUERY);
        $fragment = parse_url($intended, PHP_URL_FRAGMENT);
        $destination = $path.($query !== null ? '?'.$query : '').($fragment !== null ? '#'.$fragment : '');

        return redirect()->to($destination);
    }
}
