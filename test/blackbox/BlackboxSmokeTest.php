<?php

declare(strict_types=1);

namespace test\blackbox;

class BlackboxSmokeTest extends BaseBlackboxTestCase
{
    public function testTestSubjectRespondsFromDefaultHandler(): void
    {
        $this->assertResponseMatches(
            200,
            'Blackbox is alive',
            $this->guzzle->get('/'),
        );
    }
}
