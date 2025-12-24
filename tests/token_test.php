<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace filter_embedquestion;

/**
 * Unit tests for the util methods.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \filter_embedquestion\token
 */
final class token_test extends \advanced_testcase {
    public function test_is_authorized_secret_token(): void {
        $this->resetAfterTest();

        // First make a token, and check it.
        $embedid = new embed_id('catid', 'qid');
        $token1 = token::make_secret_token($embedid);
        $wrongtoken = substr($token1, 0, -1) . 'x';

        // Check it is acceptable with no other tokens allowed.
        $this->assertTrue(token::is_authorized_secret_token($token1, $embedid));
        $this->assertFalse(token::is_authorized_secret_token($wrongtoken, $embedid));

        // Now add some other secret to authorizedsecrets.
        set_config('authorizedsecrets', random_string(40), 'filter_embedquestion');

        // Check acceptable tokens have not changed.
        $this->assertTrue(token::is_authorized_secret_token($token1, $embedid));
        $this->assertFalse(token::is_authorized_secret_token($wrongtoken, $embedid));

        // Now move previous token to authorized secrets and change the token.
        $oldsecret = get_config('filter_embedquestion', 'secret');
        set_config('secret', random_string(40), 'filter_embedquestion');
        // Intentionally setting this to a messy value.
        set_config('authorizedsecrets', "\r
                " . random_string(40) . "
                $oldsecret
                " . random_string(40) . "    \n\r\n", 'filter_embedquestion');
        $token2 = token::make_secret_token($embedid);

        // Check acceptable tokens.
        $this->assertTrue(token::is_authorized_secret_token($token1, $embedid));
        $this->assertTrue(token::is_authorized_secret_token($token2, $embedid));
        $this->assertFalse(token::is_authorized_secret_token($wrongtoken, $embedid));
    }
}
