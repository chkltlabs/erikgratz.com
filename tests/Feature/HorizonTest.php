<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HorizonTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function guests_cannot_access_horizon()
    {
        $this->get('/horizon')
            ->assertStatus(403);
    }

    /** @test */
    public function unauthorized_users_cannot_access_horizon()
    {
        $user = User::factory()->create(['email' => 'other@example.com']);

        $this->actingAs($user)
            ->get('/horizon')
            ->assertStatus(403);
    }

    /** @test */
    public function authorized_users_can_access_horizon()
    {
        $user = User::whereEmail('erik@erikgratz.com')->first()
            ?? User::factory()->create(['email' => 'erik@erikgratz.com']);

        $this->actingAs($user)
            ->get('/horizon')
            ->assertStatus(200);
    }
}
