<?php

namespace Tests\Feature;

use Tests\TestCase;

class FriendRecsEndpointTest extends TestCase
{
    public function test_200_response()
    {
        $res = $this->json('GET', '/api/friendRecs');
        var_dump($res->getOriginalContent());
        $res->assertOk();
    }
}
