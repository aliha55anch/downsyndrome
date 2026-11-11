<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModuleTenPatientExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role = User::ROLE_PARENT): User
    {
        $user = User::create([
            'first_name' => 'Sam', 'last_name' => 'Parent', 'name' => 'Sam Parent',
            'email' => 'sam@example.test', 'password' => Hash::make('password-example'),
        ]);
        $user->forceFill(['role' => $role, 'email_verified_at' => now()])->save();

        return $user;
    }

    public function test_visitors_get_private_appointments_explained_and_login_returns_to_the_site_page(): void
    {
        $this->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('Your appointment list is private.')
            ->assertSee(route('appointments.login'));

        $this->get(route('appointments.login'))->assertRedirect(route('login'));
        $this->account();

        $this->withSession(['url.intended' => route('appointments.login')])
            ->post(route('login'), ['email' => 'sam@example.test', 'password' => 'password-example'])
            ->assertRedirect(route('appointments.login'));

        $this->get(route('appointments.login'))->assertRedirect(route('appointments.index'));
        $this->get(route('appointments.index'))->assertOk();
    }

    public function test_parent_login_without_an_intended_page_returns_to_activities(): void
    {
        $this->account();

        $this->post(route('login'), ['email' => 'sam@example.test', 'password' => 'password-example'])
            ->assertRedirect(route('activities.index'));
    }

    public function test_parent_login_rejects_external_and_wrong_role_intended_destinations(): void
    {
        $this->account();

        $this->withSession(['url.intended' => 'https://evil.example/admin'])
            ->post(route('login'), ['email' => 'sam@example.test', 'password' => 'password-example'])
            ->assertRedirect(route('activities.index'));

        auth()->logout();
        $this->withSession(['url.intended' => '/admin'])
            ->post(route('login'), ['email' => 'sam@example.test', 'password' => 'password-example'])
            ->assertRedirect(route('activities.index'));

        auth()->logout();
        $this->withSession(['url.intended' => 'https://little-steps.test/doctors/42/book?consultation=online'])
            ->post(route('login'), ['email' => 'sam@example.test', 'password' => 'password-example'])
            ->assertRedirect('/doctors/42/book?consultation=online');
    }

    public function test_other_roles_cannot_use_the_patient_appointments_page(): void
    {
        $doctor = $this->account(User::ROLE_APPROVED_DOCTOR);

        $this->actingAs($doctor)->get(route('appointments.index'))->assertForbidden();
    }

    public function test_unknown_pages_return_a_branded_not_found_page(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('We could not find that page');
    }
}
