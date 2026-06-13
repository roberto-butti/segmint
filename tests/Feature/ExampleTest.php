<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_is_rendered_with_blade(): void
    {
        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertViewIs('public.home')
            ->assertSee('Know your audience.')
            ->assertSee(route('login'), false)
            ->assertSee(route('register'), false)
            ->assertHeaderMissing('X-Inertia');
    }

    public function test_homepage_links_authenticated_users_to_the_dashboard(): void
    {
        $response = $this
            ->actingAs(User::factory()->create())
            ->get(route('home'));

        $response
            ->assertOk()
            ->assertSee(route('dashboard'), false)
            ->assertDontSee(route('login'), false);
    }

    public function test_homepage_hides_registration_links_when_registration_is_disabled(): void
    {
        config()->set('fortify.features', array_values(array_filter(
            config('fortify.features'),
            fn (string $feature): bool => $feature !== Features::registration(),
        )));

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee(route('register'), false);
    }
}
