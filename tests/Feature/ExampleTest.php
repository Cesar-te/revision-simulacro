<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/exams');

        $response->assertStatus(200);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/exams')->assertRedirect(route('login'));
    }
}
