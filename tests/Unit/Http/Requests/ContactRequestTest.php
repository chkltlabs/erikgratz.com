<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

uses(TestCase::class);

/**
 * @see \App\Http\Requests\ContactRequest
 */
beforeEach(function () {
    $this->subject = new \App\Http\Requests\ContactRequest();
});


test('authorize', function () {
    $actual = $this->subject->authorize();

    $this->assertFalse($actual);
});

test('rules', function () {
    $actual = $this->subject->rules();

    $this->assertEquals([], $actual);
});