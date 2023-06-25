<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @see \App\Http\Controllers\ContactApiController
 */

test('destroy returns an ok response', function () {
    $contact = \App\Models\Contact::factory()->create();
    $response = $this->delete(route('contactapi.destroy', [$contact]));

    $response->assertStatus(204);
    $this->assertModelMissing($contact);

    // TODO: perform additional assertions
});

test('index returns an ok response', function () {
    $contacts = \App\Models\Contact::factory()->times(3)->create();

    $response = $this->getJson(route('contactapi.index'));
    $response->assertOk();
    $response->assertJsonStructure([
        // TODO: compare expected response data
        '*'=>['id','created_at','updated_at','contact','name','message','ab_message']
    ]);

    // TODO: perform additional assertions
});
