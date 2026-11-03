<?php

namespace Tests\Unit;

use App\Support\Tenancy\BlockedWords;
use Tests\TestCase;

class BlockedWordsTest extends TestCase
{
    public function test_abuse_and_phishing_names_are_blocked(): void
    {
        foreach ([
            'fuck', 'fuckyou', 'f-u-c-k', 'fu-ck', 'fuuuck', 'Fuck Library', 'b1tch', 'shit', 'shit-library',
            'chutiya', 'madarchod', 'bhosdike', 'gandu', 'lund', 'randi', 'sex', 's-e-x',
            'sbi-kyc', 'hdfc-netbanking', 'paytm-help', 'google', 'libcontrol-support', 'verify-account',
        ] as $name) {
            $this->assertTrue(BlockedWords::found($name), "Expected '{$name}' to be blocked.");
        }
    }

    public function test_ordinary_library_names_are_allowed(): void
    {
        foreach ([
            'sunrise', 'Peacock Library', 'gandhi-library', 'Gandhinagar Study Point', 'class-room', 'chutney',
            'Kota Knowledge', 'Who Reads', 'Dr BC Roy Library', 'MCC Library', 'Ashita Library', 'therapist',
            'assam-library', 'Essex', 'laudable', 'Wankaner Library', 'Book Bank', 'Cocktail', 'Scholars Hub',
            '24x7 Library', 'Shitala Mata Library', 'Randive Study Centre', 'Chodavaram Library',
        ] as $name) {
            $this->assertFalse(BlockedWords::found($name), "Expected '{$name}' to be allowed.");
        }
    }
}
