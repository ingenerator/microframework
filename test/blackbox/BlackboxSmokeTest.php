<?php

namespace test\blackbox;

class BlackboxSmokeTest extends BaseBlackboxTestCase
{
    public function test_test_subject_responds_from_default_handler()
    {
        $this->assertResponseMatches(
            200,
            'Blackbox is alive',
            $this->guzzle->get('/'),
        );
    }

}
