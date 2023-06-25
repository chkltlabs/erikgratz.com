<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


/**
 * A basic test example.
 *
 * @return void
 */
test('basic test', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
