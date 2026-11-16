<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and apply browser security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Frame protection & clickjacking prevention
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME-sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Disable older buggy browser XSS auditors in favor of CSP
        $response->headers->set('X-XSS-Protection', '0');

        // Restrict sensitive browser APIs
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(self), geolocation=(), payment=(self)');

        // Content Security Policy
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data:",
            "connect-src 'self' https://*.getsafepay.com",
            "frame-src 'self' https://www.youtube-nocookie.com https://www.youtube.com https://*.getsafepay.com https:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self' https://*.getsafepay.com",
            "frame-ancestors 'self'",
        ];
        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        // Enforce HSTS when request is over HTTPS
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
