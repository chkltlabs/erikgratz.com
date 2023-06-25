<?php

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Redirect;
use Tests\TestCase;

uses(RefreshDatabase::class);

/**
 * @see \App\Http\Controllers\ContactController
 */

test('destroy returns an ok response', function () {
    $contact = \App\Models\Contact::factory()->create();
    $user = User::first() ?? User::factory()->create();
    $this->actingAs($user);
    $response = $this->delete(route('contacts.destroy', [$contact]));
    // dd($response);
    $response->assertRedirect(route('contacts.index'));
    $this->assertModelMissing($contact);

    // TODO: perform additional assertions
});

test('index returns an ok response', function () {
    $contacts = \App\Models\Contact::factory()->times(3)->create();

    $response = $this->get(route('contacts.index'));

    $response->assertOk();

    // TODO: perform additional assertions
});

test('show returns an ok response', function () {
    $contact = \App\Models\Contact::factory()->create();

    $response = $this->get(route('contacts.show', [$contact]));

    $response->assertOk();

    // TODO: perform additional assertions
});

test('store returns an ok response', function () {
    $data = [
        'contact' => 'spoot@benis.fart',
        'name' => 'Dr. Spoot',
        'message' => 'Spoodleedeeee'
    ];
    $response = $this->post(route('contacts.store'), $data);

    $this->assertDatabaseHas('contacts', $data);
});

test('store validates with a form request', function () {
    $this->assertActionUsesFormRequest(
        \App\Http\Controllers\ContactController::class,
        'store',
        \App\Http\Requests\ContactStoreRequest::class
    );
});
