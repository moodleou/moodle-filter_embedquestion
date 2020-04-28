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

use filter_embedquestion\embed_id;
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

    /**
     * Data provider for {@link test_filter()}.
     * @return array the test cases.
     */
    public function get_cases_for_test_filter(): array {
        global $CFG;
        $tokenerror = ['<div class="filter_embedquestion-error">',
                'This question may not be embedded here.'];
        $embedid = new embed_id('cat', 'q');
        $requiredtoken = token::make_secret_token($embedid);

        $cases = [
            ['Frog', 'Frog'],
            ['{Q{x}Q}', $tokenerror],
            ['{Q{cat/q|not-the-right-token}Q}', $tokenerror],
        ];

        $title = get_string('title', 'filter_embedquestion');

        $expectedurl = new moodle_url('/filter/embedquestion/showquestion.php', [
                'catid' => 'cat', 'qid' => 'q', 'contextid' => '1', 'pageurl' => '/', 'pagetitle' => 'System',
                'behaviour' => 'interactive', 'correctness' => '1', 'marks' => '2', 'markdp' => '2',
                'feedback' => '1', 'generalfeedback' => '1', 'rightanswer' => '0', 'history' => '0']);
        token::add_iframe_token_to_url($expectedurl);
        $cases[] = ['{Q{cat/q|' . $requiredtoken . '}Q}',
                '<iframe
    class="filter_embedquestion-iframe" allowfullscreen
    title="' . $title . '"
    src="' . $expectedurl . '"
    id="cat/q"></iframe>'];

        $expectedurl = new moodle_url('/filter/embedquestion/showquestion.php', [
                'catid' => 'cat', 'qid' => 'q', 'contextid' => '1', 'pageurl' => '/', 'pagetitle' => 'System',
                'behaviour' => 'immediatefeedback', 'correctness' => '1', 'marks' => '10', 'markdp' => '3',
                'feedback' => '1', 'generalfeedback' => '0', 'rightanswer' => '0', 'history' => '0']);
        token::add_iframe_token_to_url($expectedurl);
        $cases[] = ['{Q{cat/q|behaviour=immediatefeedback|marks=10|markdp=3|generalfeedback=0|' . $requiredtoken . '}Q}',
                '<iframe
    class="filter_embedquestion-iframe" allowfullscreen
    title="' . $title . '"
    src="' . $expectedurl . '"
    id="cat/q"></iframe>'];

        return $cases;
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
    public function test_filter(string $input, $expectedoutput): void {
        global $PAGE;

        $context = context_course::instance(SITEID);
        $filter = new filter_embedquestion($context, []);
        $PAGE->set_url('/');
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

    public function test_no_guests(): void {
        global $PAGE;

        $this->resetAfterTest();
        $this->setGuestUser();

        $embedid = new embed_id('cat', 'q');
        $context = context_course::instance(SITEID);
        $filter = new filter_embedquestion($context, []);
        $filter->setup($PAGE, $context);

        $actualoutput = $filter->filter('{Q{cat/q|' . token::make_secret_token($embedid) . '}Q}');

        $this->assertContains('<div class="filter_embedquestion-error">', $actualoutput);
        $this->assertContains('Guest users do not have permission to interact with embedded questions.', $actualoutput);
    }
}
