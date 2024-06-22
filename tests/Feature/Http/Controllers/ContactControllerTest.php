<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ContactController
 */
class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function destroy_returns_an_ok_response()
    {
        $contact = \App\Models\Contact::factory()->create();
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);
        $response = $this->delete(route('contacts.destroy', [$contact]));
        // dd($response);
        $response->assertRedirect(route('contacts.index'));
        $this->assertModelMissing($contact);

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function index_returns_an_ok_response()
    {
        $contacts = \App\Models\Contact::factory()->times(3)->create();

        $response = $this->get(route('contacts.index'));

        $response->assertOk();

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function show_returns_an_ok_response()
    {
        $contact = \App\Models\Contact::factory()->create();

        $response = $this->get(route('contacts.show', [$contact]));

        $response->assertOk();

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function store_returns_an_ok_response()
    {
        $data = [
            'contact' => 'spoot@benis.fart',
            'name' => 'Dr. Spoot',
            'message' => 'Spoodleedeeee',
        ];
        $response = $this->post(route('contacts.store'), $data);

        $this->assertDatabaseHas('contacts', $data);
    }

    /**
     * @test
     */
    public function store_validates_with_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ContactController::class,
            'store',
            \App\Http\Requests\ContactStoreRequest::class
        );
    }

    /**
     * @test not implemented
     */
    // public function update_returns_an_ok_response()
    // {
    //     $contact = \App\Models\Contact::factory()->create();
    //     $user = User::first() ?? User::factory()->create();
    //     $this->actingAs($user);
    //     $response = $this->put(route('contacts.update', [$contact]), [
    //         // TODO: send request data
    //     ]);

    //     $response->assertOk();

    //     // TODO: perform additional assertions
    // }

    // test cases...
}
