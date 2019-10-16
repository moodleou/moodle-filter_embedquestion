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
 * Behat steps for filter_embedquestion.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test because this file is required by Behat.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Gherkin\Node\TableNode as TableNode;

/**
 * Behat steps for filter_embedquestion.
 *
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_filter_embedquestion extends behat_base {

    /**
     * Opens the filter test page for a particular course.
     *
     * @Given /^I am on the filter test page for "(?P<coursefullname_string>(?:[^"]|\\")*)"$/
     * @param string $coursefullname The full name of the course.
     */
    public function i_am_on_filter_test_page($coursefullname) {
        global $DB;
        $course = $DB->get_record('course', array('fullname' => $coursefullname), 'id', MUST_EXIST);
        $url = new moodle_url('/filter/embedquestion/testhelper.php', ['courseid' => $course->id]);
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
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
     *                {@link core_question_generator::get_simulated_post_data_for_question_attempt()}
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
    public function user_has_attempted_with_responses($username, $contextlevel, $contextref, TableNode $attemptinfo) {
        global $DB;

        /** @var filter_embedquestion_generator $generator */
        $generator = behat_util::get_data_generator()->get_plugin_generator('filter_embedquestion');

        $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
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

            $question = $generator->get_question_from_embed_id($questioninfo['question']);

            $generator->create_attempt_at_embedded_question($question,
                $user, $questioninfo['response'], $attemptcontext, $questioninfo['pagename']);
        }
    }
}
