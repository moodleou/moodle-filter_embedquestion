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

use advanced_testcase;
use core_privacy\local\request\writer;
use filter_embedquestion\privacy\provider;

/**
 * Unit tests for filter_embedquestion privacy provider.
 *
 * @package    filter_embedquestion
 * @copyright  2025 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \filter_embedquestion\privacy\provider
 */
final class provider_test extends advanced_testcase {
    /**
     * Test to check export_user_preferences.
     *
     * @covers ::export_user_preferences
     */
    public function test_export_user_preferences(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $qbank = $this->getDataGenerator()->create_module('qbank', ['course' => $course->id, 'idnumber' => 'abc123']);
        // Simulate saved user preference JSON.
        $example = [$course->id  => $qbank->id];
        set_user_preference('filter_embedquestion_userdefaultqbank', json_encode($example), $user->id);
        provider::export_user_preferences($user->id);
        $writer = writer::with_context(\context_system::instance());
        $prefs = $writer->get_user_preferences('filter_embedquestion');
        $this->assertEquals(get_string('defaultqbank', 'filter_embedquestion'), $prefs->userdefaultqbank->description);
        $this->assertEquals(json_encode($example), $prefs->userdefaultqbank->value);
    }
}
