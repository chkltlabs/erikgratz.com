<?php



test('200 response', function () {
    $res = $this->json('GET', 'api/contactapi');

    $res->assertOk();
//        var_dump($res->getOriginalContent());
});
