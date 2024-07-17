<?php

namespace test\blackbox;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class BlackboxSmokeTest extends TestCase
{
    public function test_test_subject_responds_from_default_handler()
    {
        $client = new Client();
        $response = $client->get('http://test_subject/');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Blackbox is alive', $response->getBody()->getContents());
    }

}
