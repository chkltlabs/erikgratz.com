<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class);

/**
 * A basic test example.
 *
 * @return void
 */
test('basic test', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
