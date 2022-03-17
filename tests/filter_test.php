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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/embedquestion/filter.php');

/**
 * Unit tests for \filter_embedquestion.
 *
 * Test the delimiter parsing used by the embedquestion filter.
 *
 * @package    \filter_embedquestion
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_test extends \advanced_testcase {

    /**
     * Data provider for {@link test_filter()}.
     * @return array the test cases.
     */
    public function get_cases_for_test_filter(): array {
        $tokenerror = ['<div class="filter_embedquestion-error">',
                'This embedded question is incorrectly configured.'];

        $cases = [
            'noembed' => ['Frog', 'Frog'],
            'invalidembed' => ['{Q{x}Q}', $tokenerror],
            'missingtoken' => ['{Q{cat/q|not-the-right-token}Q}', $tokenerror],
        ];

        $title = get_string('title', 'filter_embedquestion');

        $requiredtoken = token::make_secret_token(new embed_id('cat', 'q'));
        $expectedurl = new \moodle_url('/filter/embedquestion/showquestion.php', [
                'catid' => 'cat', 'qid' => 'q', 'contextid' => '1', 'pageurl' => '/', 'pagetitle' => 'System',
                'behaviour' => 'interactive', 'correctness' => '1', 'marks' => '2', 'markdp' => '2',
                'feedback' => '1', 'generalfeedback' => '1', 'rightanswer' => '0', 'history' => '0']);
        token::add_iframe_token_to_url($expectedurl);
        $cases['defaultoptions'] = ['{Q{cat/q|' . $requiredtoken . '}Q}',
                '<iframe
    class="filter_embedquestion-iframe" allowfullscreen
    title="' . $title . '"
    src="' . $expectedurl . '"
    id="cat/q"></iframe>'];

        $requiredtoken = token::make_secret_token(new embed_id('A/V questions', '|<--- 100%'));
        $expectedurl = new \moodle_url('/filter/embedquestion/showquestion.php', [
                'catid' => 'A/V questions', 'qid' => '|<--- 100%', 'contextid' => '1', 'pageurl' => '/', 'pagetitle' => 'System',
                'behaviour' => 'immediatefeedback', 'correctness' => '1', 'marks' => '10', 'markdp' => '3',
                'feedback' => '1', 'generalfeedback' => '0', 'rightanswer' => '0', 'history' => '0', 'forcedlanguage' => 'en']);
        token::add_iframe_token_to_url($expectedurl);
        $cases['givenoptions'] = ['{Q{A%2FV questions/%7C&lt;--- 100%25|' .
                'behaviour=immediatefeedback|marks=10|markdp=3|generalfeedback=0|forcedlanguage=en|' .
                $requiredtoken . '}Q}',
                '<iframe
    class="filter_embedquestion-iframe" allowfullscreen
    title="' . $title . '"
    src="' . $expectedurl . '"
    id="AVquestions/---100"></iframe>'];

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

        $context = \context_course::instance(SITEID);
        $filter = new \filter_embedquestion($context, []);
        $PAGE->set_url('/');
        $filter->setup($PAGE, $context);

        $actualoutput = $filter->filter($input);

        if (is_string($expectedoutput)) {
            $this->assertSame($expectedoutput, $actualoutput);

        } else if (is_array($expectedoutput)) {
            foreach ($expectedoutput as $expectedpart) {
                $this->assertStringContainsString($expectedpart, $actualoutput);
            }

        } else {
            throw new \coding_exception('Unexpected expected output type.');
        }
    }

    public function test_no_guests(): void {
        global $PAGE;

        $this->resetAfterTest();
        $this->setGuestUser();

        $embedid = new embed_id('cat', 'q');
        $context = \context_course::instance(SITEID);
        $filter = new \filter_embedquestion($context, []);
        $filter->setup($PAGE, $context);

        $actualoutput = $filter->filter('{Q{cat/q|' . token::make_secret_token($embedid) . '}Q}');

        $this->assertStringContainsString('<div class="filter_embedquestion-error">', $actualoutput);
        $this->assertStringContainsString('Guest users do not have permission to interact with embedded questions.', $actualoutput);
    }
}
