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

/**
 * Tests data access for filter_embedquestion.
 *
 * @package    filter
 * @subpackage embedquestion
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use filter_embedquestion\data;

class basic_test extends advanced_testcase {

    public function test_create_token() {
        $course = self::getDataGenerator()->create_course();
        $page = self::getDataGenerator()->create_module('page', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('page', $page->id);
        $this->setAdminUser();
        $token = data::create_token((object)[
                'cmid' => $cm->id,
                'qidnum' => 'Q1',
                'categoryidnum' => 'Q2',
                'params' => '{sectionid : 1}'
        ]);
        // Expect success and new id return.
        $this->assertNotEmpty($token);
        // Check data is added into database.
        $data = data::get_data_from_token($token);
        $this->assertNotEmpty($data);
        $this->assertEquals($cm->id, $data->cmid);
        $this->assertEquals('Q1', $data->qidnum);
    }

}
