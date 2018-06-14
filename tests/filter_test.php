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
 * Unit test for the filter_embedquestion
 *
 * @package    filter_embedquestion
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/embedquestion/filter.php');


/**
 * Unit tests for filter_embedquestion.
 *
 * Test the delimiter parsing used by the embedquestion filter.
 *
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_embedquestion_testcase extends advanced_testcase {

    protected $filter;

    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->filter = new filter_embedquestion(context_system::instance(), array());
    }

    function test_validate_input() {
        $text = '{Q{cat-id-num/que-id-num|id=4131|courseid=31||behaviour=interactive|maxmark=10|markdp=3|generalfeedback=hide}Q}';
        $actual = $this->filter->validate_input($text);
        $expected = true;
        $this->assertEquals($actual, $expected);
    }

    function test_tokenise() {
        $text = '{Q{cat-id-num/que-id-num|id=4131|courseid=31|behaviour=interactive|maxmark=10|markdp=3|generalfeedback=hide}Q}';
        $actual = $this->filter->tokenise($text);
        $expected = array('id' => 4131, 'courseid' => 31, 'behaviour' => 'interactive', 'maxmark' => 10,
                'markdp' => 3, 'generalfeedback' => 'hide', 'token' => hash('md5', 'cat-id-num/que-id-num', false));
        $this->assertEquals($actual, $expected);
    }

    function test_filter() {
        $text = '{Q{cat-id-num/que-id-num|id=4131|courseid=31|behaviour=interactive|maxmark=10|markdp=3|generalfeedback=hide}Q}';
        $actual = $this->filter->filter($text);
        // TODO: Careate a course and questions so that this test does not fail.
        $expected = "<iframe name='filter-embedquestion' id='filter-embedquestion' width='99%' height='500px'src='https://mk4359.vledev3.open.ac.uk/ou-moodle2/filter/embedquestion/showquestion.php?id=4131&amp;course=31&amp;token=f6934f69e4c5fd3c95bb433726c7f17936a7ffa4050aafb50ad00d9c34c20662&amp;behaviour=interactive&amp;maxmark=1&amp;correctness=1&amp;marks=2&amp;markdp=2&amp;feedback=1&amp;generalfeedback=1&amp;rightanswer=1&amp;history=0' ></iframe>";
        $this->assertEquals($actual, $expected);
    }
}
