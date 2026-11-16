<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityBaselineTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_routes_include_hardening_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-XSS-Protection', '0');
        $this->assertStringContainsString("default-src 'self'", (string) $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("frame-ancestors 'self'", (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_https_requests_include_strict_transport_security(): void
    {
        $response = $this->get('https://localhost/api/health');

        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_user_password_cast_automatically_hashes_plaintext_passwords(): void
    {
        $user = new User([
            'first_name' => 'Sec',
            'last_name' => 'User',
            'name' => 'Sec User',
            'email' => 'secuser@example.test',
            'password' => 'PlaintextPassword123!',
        ]);

        $this->assertTrue(Hash::check('PlaintextPassword123!', $user->password));
        $this->assertNotSame('PlaintextPassword123!', $user->password);
    }

    public function test_email_change_endpoint_is_rate_limited(): void
    {
        $user = User::create([
            'first_name' => 'Parent',
            'last_name' => 'Test',
            'name' => 'Parent Test',
            'email' => 'parent-settings-throttle@example.test',
            'password' => bcrypt('StrongPassword123'),
        ]);
        $user->forceFill(['role' => User::ROLE_PARENT])->save();
        $user->markEmailAsVerified();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->post(route('dashboard.settings.email'), [
                'email' => "new-{$i}@example.test",
            ])->assertRedirect();
        }

        // 6th attempt within the same minute should be throttled (HTTP 429)
        $this->actingAs($user)->post(route('dashboard.settings.email'), [
            'email' => 'new-6@example.test',
        ])->assertStatus(429);
    }
}
