<?php

namespace Tests\Feature;

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
