<?php

namespace test\blackbox;

class BlackboxSmokeTest extends BaseBlackboxTestCase
{
    public function testTestSubjectRespondsFromDefaultHandler()
    {
        $this->assertResponseMatches(
            200,
            'Blackbox is alive',
            $this->guzzle->get('/'),
        );
    }
}
