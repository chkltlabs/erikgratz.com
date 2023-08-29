<?php



test('200 response', function () {
    $this->json('GET', 'api/contactapi')
    ->assertOk();
});
