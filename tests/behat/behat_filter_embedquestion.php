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

// NOTE: no MOODLE_INTERNAL test because this file is required by Behat.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;
use Behat\Gherkin\Node\TableNode;

/**
 * Behat steps for filter_embedquestion.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_filter_embedquestion extends behat_base {
    /**
     * Convert page names to URLs for steps like 'When I am on the "[page name]" page'.
     *
     * Recognised page names are:
     * | pagetype          | description |
     * | None so far!      |             |
     *
     * @param string $page name of the page, with the component name removed e.g. 'Admin notification'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_url(string $page): moodle_url {
        switch (strtolower($page)) {
            default:
                throw new Exception('Unrecognised quiz page type "' . $page . '."');
        }
    }

    /**
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * Recognised page names are:
     * | pagetype | name meaning | description          |
     * | test     | Course name  | The filter test page |
     *
     * @param string $type identifies which type of page this is, e.g. 'Attempt review'.
     * @param string $identifier identifies the particular page, e.g. 'Test quiz > student > Attempt 1'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_instance_url(
        string $type,
        string $identifier
    ): moodle_url {
        switch (strtolower($type)) {
            case 'test':
                return new moodle_url(
                    '/filter/embedquestion/testhelper.php',
                    [
                        'courseid' => $this->get_course_id($identifier),
                    ]
                );
            default:
                throw new Exception('Unrecognised quiz page type "' . $type . '."');
        }
    }

    /**
     * Attempt an embedded question.
     *
     * The first row should be column names:
     * | pagename | question | response |
     * All columns are required.
     *
     * pagename     This column provides more clarity in context, for example, in
     *              Given "student1" has attempted embedded questions in "activity" context "page1":
     *                | pagename | question    | response |
     *                | C1:page1 | embed/test1 | True     |
     *              pagename column make it clear that page1 is in C1 and not C2.
     *
     * question     This represents the embedid of the embedded question which has to be a valid
     *              embedable question. Rendomised embedded question are not covered at the moment.
     *
     * response       The response that was submitted. How this is interpreted depends on
     *                the question type. It gets passed to
     *                {@see core_question_generator::get_simulated_post_data_for_question_attempt()}
     *                and therefore to the un_summarise_response method of the question to decode.
     *
     * Then there should be a number of rows of data, one for each question you want to add.
     * There is no need to supply answers to all questions. If so, other qusetions will be
     * left unanswered.
     *
     * @Given :username has attempted embedded questions in :contextlevel context :contextref:
     * @param string $username the username of the user that will attempt.
     * @param string $contextlevel 'course' or 'activity'.
     * @param string $contextref either course name or activity idnumber.
     * @param TableNode $attemptinfo information about the questions to add, as above.
     */
    public function user_has_attempted_with_responses(
        $username,
        $contextlevel,
        $contextref,
        TableNode $attemptinfo
    ) {
        global $DB;

        /** @var filter_embedquestion_generator $generator */
        $generator = behat_util::get_data_generator()->get_plugin_generator('filter_embedquestion');

        $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        $attemptcontext = $this->get_attempt_context($contextlevel, $contextref);

        $datas = [];
        foreach ($attemptinfo->getHash() as $questioninfo) {
            if (empty($questioninfo['question'])) {
                throw new ExpectationException('When simulating embedded questions, ' .
                        'the question column is required.', $this->getSession());
            }
            if (!array_key_exists('pagename', $questioninfo)) {
                throw new ExpectationException('When simulating a embedded questions, ' .
                        'the pagename column is required.', $this->getSession());
            }
            if (!array_key_exists('response', $questioninfo)) {
                throw new ExpectationException('When simulating a embedded questions, ' .
                        'the response column is required.', $this->getSession());
            }
            if (!array_key_exists('slot', $questioninfo)) {
                $questioninfo['slot'] = 1;
            }

            if (!array_key_exists($questioninfo['pagename'], $datas)) {
                $datas[$questioninfo['pagename']] = [
                    'context' => $attemptcontext,
                    'user' => $user,
                    'slots' => [],
                ];
            }

            $question = $generator->get_question_from_embed_id($questioninfo['question']);

            $datas[$questioninfo['pagename']]['slots'][] = [
                'no' => $questioninfo['slot'],
                'question' => $question,
                'response' => $questioninfo['response'],
            ];
        }

        foreach ($datas as $pagename => $data) {
            $attemptcontext = $data['context'];
            $user = $data['user'];
            foreach ($data['slots'] as $slot) {
                $generator->create_attempt_at_embedded_question(
                    $slot['question'],
                    $user,
                    $slot['response'],
                    $attemptcontext,
                    $pagename,
                    $slot['no']
                );
            }
        }
    }

    /**
     * Start an embedded question.
     *
     * @Given :username has started embedded question :questioninfo in :contextlevel context :contextref
     *
     * @param string $username the username of the user that will attempt.
     * @param string $questioninfo embedded question to attempt
     * @param string $contextlevel 'course' or 'activity'.
     * @param string $contextref either course name or activity idnumber.
     */
    public function user_has_start_embedded_question(
        string $username,
        string $questioninfo,
        string $contextlevel,
        string $contextref
    ) {
        $this->create_inprogress_attempt_for_embedded_question(
            $username,
            $questioninfo,
            $contextlevel,
            $contextref
        );
    }

    /**
     * Start an embedded question with slot.
     *
     * @Given :username has started embedded question :questioninfo in :contextlevel context :contextref with slot :slot
     *
     * @param string $username the username of the user that will attempt.
     * @param string $questioninfo embedded question to attempt
     * @param string $contextlevel 'course' or 'activity'.
     * @param string $contextref either course name or activity idnumber.
     * @param string $slot slot no
     */
    public function user_has_start_embedded_question_with_slot(
        string $username,
        string $questioninfo,
        string $contextlevel,
        string $contextref,
        string $slot
    ) {
        $this->create_inprogress_attempt_for_embedded_question(
            $username,
            $questioninfo,
            $contextlevel,
            $contextref,
            $slot
        );
    }

    /**
     * Create attempt for embedded question with given information.
     *
     * @param string $username the username of the user that will attempt.
     * @param string $questioninfo embedded question to attempt
     * @param string $contextlevel 'course' or 'activity'.
     * @param string $contextref either course name or activity idnumber.
     * @param int $slot Slot not
     */
    public function create_inprogress_attempt_for_embedded_question(
        string $username,
        string $questioninfo,
        string $contextlevel,
        string $contextref,
        int $slot = 1
    ) {
        global $DB;

        /** @var filter_embedquestion_generator $generator */
        $generator = behat_util::get_data_generator()->get_plugin_generator('filter_embedquestion');

        $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        $attemptcontext = $this->get_attempt_context($contextlevel, $contextref);

        $question = $generator->get_question_from_embed_id($questioninfo);
        $generator->create_attempt_at_embedded_question($question, $user, '', $attemptcontext, '', $slot, false);
    }

    /**
     * Get attempt context.
     *
     * @param string $contextlevel Context level
     * @param string $contextref Context reference
     * @return bool|context|context_course|context_module
     */
    private function get_attempt_context(
        string $contextlevel,
        string $contextref
    ) {
        global $DB;

        switch ($contextlevel) {
            case 'course':
                $courseid = $DB->get_field('course', 'id', ['fullname' => $contextref]);
                $attemptcontext = context_course::instance($courseid);
                break;

            case 'activity':
                $cmid = $DB->get_field('course_modules', 'id', ['idnumber' => $contextref]);
                $attemptcontext = context_module::instance($cmid);
                break;

            default:
                throw new ExpectationException('When simulating a embedded questions, ' .
                        'contextlevel must be "activity" or "course".', $this->getSession());
        }

        return $attemptcontext;
    }
}
