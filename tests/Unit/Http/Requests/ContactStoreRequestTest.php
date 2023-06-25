<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

uses(TestCase::class);

/**
 * @see \App\Http\Requests\ContactStoreRequest
 */
beforeEach(function () {
    $this->subject = new \App\Http\Requests\ContactStoreRequest();
});


test('authorize', function () {
    $actual = $this->subject->authorize();

    $this->assertTrue($actual);
});

test('rules', function () {
    $actual = $this->subject->rules();

    $this->assertValidationRules([
        'name' => 'required|string',
        'message' => 'required|string',
    ], $actual);
});
