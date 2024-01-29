<?php

namespace Shrd\Laravel\Azure\Identity\Tests\Unit\Tokens;

use Shrd\Laravel\Azure\Identity\Tokens\AccessToken;
use Shrd\Laravel\Azure\Storage\Tests\TestCase;

class AccessTokenTest extends TestCase
{
    public function test_accessTokenFromString()
    {
        $token = AccessToken::from("AAAA.AAAA.AAAA");

        $this->assertEquals("AAAA.AAAA.AAAA", $token->accessToken);
    }

    public function test_accessTokenFromJsonString()
    {
        $token = AccessToken::from(<<<'JSON'
          {
            "access_token": "BBBB.BBBB.BBBB"
          }
          JSON);

        $this->assertEquals("BBBB.BBBB.BBBB", $token->accessToken);
    }
}
