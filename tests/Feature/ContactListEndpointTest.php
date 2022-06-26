<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ContactListEndpointTest extends TestCase
{
    public function test_200_response()
    {
        $res = $this->json('GET', 'api/contactapi');

        $res->assertOk();
//        var_dump($res->getOriginalContent());
    }
}
