<?php

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

    expect($actual)->toBeTrue();
});

test('rules', function () {
    $actual = $this->subject->rules();

    $this->assertValidationRules([
        'name' => 'required|string',
        'message' => 'required|string',
    ], $actual);
});
