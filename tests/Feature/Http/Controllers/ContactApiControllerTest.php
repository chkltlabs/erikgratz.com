<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ContactApiController
 */
class ContactApiControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function destroy_returns_an_ok_response()
    {
        $contact = \App\Models\Contact::factory()->create();
        $response = $this->delete(route('contactapi.destroy', [$contact]));

        $response->assertStatus(204);
        $this->assertModelMissing($contact);

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function index_returns_an_ok_response()
    {
        $contacts = \App\Models\Contact::factory()->times(3)->create();

        $response = $this->getJson(route('contactapi.index'));
        $response->assertOk();
        $response->assertJsonStructure([
            // TODO: compare expected response data
            '*' => ['id', 'created_at', 'updated_at', 'contact', 'name', 'message', 'ab_message'],
        ]);

        // TODO: perform additional assertions
    }

    // /**
    //  * @test
    //  */
    // public function show_returns_an_ok_response()
    // {
    //     $contact = \App\Models\Contact::factory()->create();

    //     $response = $this->getJson(route('contactapi.show', [$contact]));

    //     $response->assertOk();
    //     $response->assertJsonStructure([
    //         // TODO: compare expected response data
    //     ]);

    //     // TODO: perform additional assertions
    // }

    // /**
    //  * @test
    //  */
    // public function store_returns_an_ok_response()
    // {
    //     $response = $this->postJson(route('contactapi.store'), [
    //         // TODO: send request data
    //     ]);

    //     $response->assertOk();
    //     $response->assertJsonStructure([
    //         // TODO: compare expected response data
    //     ]);

    //     // TODO: perform additional assertions
    // }

    // /**
    //  * @test
    //  */
    // public function update_returns_an_ok_response()
    // {
    //     $contact = \App\Models\Contact::factory()->create();

    //     $response = $this->putJson(route('contactapi.update', [$contact]), [
    //         // TODO: send request data
    //     ]);

    //     $response->assertOk();
    //     $response->assertJsonStructure([
    //         // TODO: compare expected response data
    //     ]);

    //     // TODO: perform additional assertions
    // }

    // test cases...
}
