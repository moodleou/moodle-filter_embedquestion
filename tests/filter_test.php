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
use filter_embedquestion\token;


/**
 * Unit tests for filter_embedquestion.
 *
 * Test the delimiter parsing used by the embedquestion filter.
 *
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_embedquestion_testcase extends advanced_testcase {

    public function get_cases_for_test_filter() {
        global $CFG;
        $tokenerror = ['<div class="filter_embedquestion-error">',
                'This question may not be embedded here.'];
        $requiredtoken = token::make_secret_token('cat', 'q');
        return [
                ['Frog', 'Frog'],
                ['{Q{x}Q}', $tokenerror],
                ['{Q{cat/q|not-the-right-token}Q}', $tokenerror],
                ['{Q{cat/q|' . $requiredtoken . '}Q}',
                        '<iframe class="filter_embedquestion-iframe" src="' . $CFG->wwwroot .
                        '/filter/embedquestion/showquestion.php?catid=cat&amp;qid=q&amp;' .
                        'course=' . SITEID . '&amp;token=' . token::make_iframe_token('cat', 'q') .
                        '&amp;behaviour=interactive&amp;correctness=1&amp;marks=2&amp;markdp=2' .
                        '&amp;feedback=1&amp;generalfeedback=1&amp;rightanswer=0&amp;history=0"></iframe>'],
                ['{Q{cat/q|behaviour=immediatefeedback|marks=10|markdp=3|generalfeedback=0|' . $requiredtoken . '}Q}',
                        ['<iframe class="filter_embedquestion-iframe"',
                                '?catid=cat&amp;qid=q&amp;course=' . SITEID . '&amp;token=',
                                '&amp;behaviour=immediatefeedback&amp;', '&amp;marks=10&amp;markdp=3&amp;',
                                '&amp;generalfeedback=0&amp;']],
            ];
    }

    /**
     * Test the behaviour of the filter.
     *
     * @param string $input the content to be filtered
     * @param string|array $expectedoutput if a string, this is the exact expected output. If an array,
     *      all array elements must be present and substrings of the actual output.
     *
     * @dataProvider get_cases_for_test_filter
     */
    public function test_filter($input, $expectedoutput) {
        global $PAGE;

        $context = context_course::instance(SITEID);
        $filter = new filter_embedquestion($context, []);
        $filter->setup($PAGE, $context);

        $actualoutput = $filter->filter($input);

        if (is_string($expectedoutput)) {
            $this->assertSame($expectedoutput, $actualoutput);

        } else if (is_array($expectedoutput)) {
            foreach ($expectedoutput as $expectedpart) {
                $this->assertContains($expectedpart, $actualoutput);
            }

        } else {
            throw new coding_exception('Unexpected expected output type.');
        }
    }

    public function test_no_guests() {
        global $PAGE;

        $this->resetAfterTest();
        $this->setGuestUser();

        $context = context_course::instance(SITEID);
        $filter = new filter_embedquestion($context, []);
        $filter->setup($PAGE, $context);

        $actualoutput = $filter->filter('{Q{cat/q|' . token::make_secret_token('cat', 'q') . '}Q}');

        $this->assertContains('<div class="filter_embedquestion-error">', $actualoutput);
        $this->assertContains('Guest users do not have permission to interact with embedded questions.', $actualoutput);
    }
}
