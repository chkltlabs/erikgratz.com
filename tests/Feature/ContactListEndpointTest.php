<?php

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;


test('200 response', function () {
    $res = $this->json('GET', 'api/contactapi');

    $res->assertOk();
//        var_dump($res->getOriginalContent());
});
