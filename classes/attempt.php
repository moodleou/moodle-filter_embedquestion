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
 * Represents the attempt at one embedded question.
 *
 * @package   filter_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_embedquestion;
defined('MOODLE_INTERNAL') || die();

/**
 * Represents the attempt at one embedded question.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt {

    /**
     * @var int the id of the course we are in.
     */
    protected $courseid;

    /**
     * @var embed_id $embedid identifies the question to embed.
     */
    protected $embedid;

    /**
     * @var \stdClass the question category we are in.
     */
    protected $category;

    /**
     * @var \context context corresponding to $courseid.
     */
    protected $coursecontext;

    /**
     * @var \question_usage_by_activity where the attempt data is stored.
     */
    protected $quba;

    /**
     * @var int $slot slot number, within $quba, which is the current attempt.
     */
    protected $slot;

    /**
     * @var string if something has gone wrong, a lang string for a description of the problem.
     */
    protected $problem = null;

    /**
     * @var array if something has gone wrong, details about the problem. Can be fed to
     * get_string($this->problem, 'filter_embedquestion', $this->problemdetails);
     */
    protected $problemdetails = [];

    /**
     * Do not use this constructor.
     *
     * You should use attempt_manager::instance(...)->find_new_attempt(...)
     * or ...->find_continuing_attempt(...).
     *
     * @param int $courseid the id of the course we are in.
     * @param embed_id $embedid embed code for the question to embed.
     */
    public function __construct(int $courseid, embed_id $embedid) {
        $this->courseid = $courseid;
        $this->coursecontext = \context_course::instance($courseid);
        $this->embedid = $embedid;
        $this->category = $this->find_category($embedid->categoryidnumber);
    }

    /**
     * Find the category for a category idnumber, if it exists.
     *
     * @param string $categoryidnumber idnumber of the category to use.
     * @return \stdClass if the category was OK. If not null and problem and problemdetails are set.
     */
    private function find_category(string $categoryidnumber): ?\stdClass { // phpcs:ignore
        $category = utils::get_category_by_idnumber($this->coursecontext, $categoryidnumber);
        if (!$category) {
            $this->problem = 'invalidcategory';
            $this->problemdetails = [
                'catid' => $categoryidnumber,
                'contextname' => $this->coursecontext->get_context_name(false, true),
            ];
            return null;
        }
        return $category;
    }

    public function setup_usage_info(\question_usage_by_activity $quba, int $slot,
            question_options $options) {
        $this->quba = $quba;
        $this->slot = $slot;

        $question = $this->quba->get_question($this->slot);

        if ($this->embedid->questionidnumber === '*') {
            if (empty($question->idnumber) && $question->category == $this->category->id) {
                print_error('questionidmismatch', 'question');
            }
        } else {
            if ($this->embedid->questionidnumber !== $question->idnumber) {
                print_error('questionidmismatch', 'question');
            }
        }

        $this->synch_options_from_loaded_quba($options);
    }

    /**
     * @param \question_usage_by_activity $quba
     * @param question_options $options options about how the attempt should function. May get updated.
     */
    public function start_new_attempt_at_question(
            \question_usage_by_activity $quba,
            question_options $options) {
        global $DB;

        if ($this->embedid->questionidnumber === '*') {
            $questionid = $this->pick_random_questionid();
        } else {
            $questionid = $this->find_questionid($this->embedid->questionidnumber);
        }
        if (!$this->is_valid()) {
            return;
        }

        $question = \question_bank::load_question($questionid);

        $this->quba = $quba;
        $this->slot = $this->quba->add_question($question, $options->maxmark);

        if ($options->variant) {
            $options->variant = min($question->get_num_variants(), max(1, $options->variant));
        } else {
            $options->variant = rand(1, $question->get_num_variants());
        }

        $this->quba->start_question($this->slot, $options->variant);

        $transaction = $DB->start_delegated_transaction();
        \question_engine::save_questions_usage_by_activity($this->quba);
        $transaction->allow_commit();

        \filter_embedquestion\event\question_started::create(
                ['context' => $this->coursecontext, 'objectid' => $question->id])->trigger();

        $this->synch_options_from_loaded_quba($options);
    }

    /**
     * Pick a sharable questionid at random from a category.
     *
     * @return int the question id. If not 0 and problem and problemdetails are set.
     */
    public function pick_random_questionid(): int {
        $questionids = utils::get_sharable_question_ids($this->category->id);
        if (empty($questionids)) {
            $this->problem = 'invalidemptycategory';
            $this->problemdetails = [
                'catname' => format_string($this->category->name),
                'contextname' => $this->coursecontext->get_context_name(false, true),
            ];
            return 0;
        }
        return array_rand($questionids);
    }

    /**
     * Find the question for a question idnumber, if it exists.
     *
     * @param string $questionidnumber idnumber of the question to use.
     * @return int corresponding questionid if found, else 0 and problem and problemdetails are set.
     */
    public function find_questionid(string $questionidnumber): int {
        $questiondata = utils::get_question_by_idnumber($this->category->id, $questionidnumber);
        if (!$questiondata) {
            $this->problem = 'invalidemptycategory';
            $this->problemdetails = [
                    'qid' => $questionidnumber,
                    'catname' => format_string($this->category->name),
            ];
            return 0;
        }
        return $questiondata->id;
    }

    /**
     * Helper used by the two find_..._attempt() methods.
     *
     * @param question_options $options options to update.
     */
    private function synch_options_from_loaded_quba(question_options $options) {
        $options->behaviour = $this->quba->get_preferred_behaviour();
        $options->maxmark = $this->quba->get_question_max_mark($this->slot);
        $options->variant = $this->quba->get_variant($this->slot);
    }

    /**
     * Is this attempt valid, and something that can be used?
     *
     * Should be called after one of the find method, and if false is
     * returned, show an error.
     *
     * @return bool true if it is safe to continue.
     */
    public function is_valid(): bool {
        return $this->problem === null;
    }

    /**
     * If is_valid() returns false, this gives a descriptoin of the problem.
     *
     * The description contains technical details that should only be shown to trusted users.
     *
     * @return string
     */
    public function get_problem_description(): string {
        return get_string($this->problem, 'filter_embedquestion', $this->problemdetails);
    }

    /**
     * Get the question usage id.
     *
     * @return int|string the question usage id we are part of.
     */
    public function get_qubaid() {
        return $this->quba->get_id();
    }

    /**
     * Finish the currently active attempt, so when we next call find_new_attempt(),
     * a new attempt at this question will be started.
     */
    public function process_submitted_actions() {
        global $DB;

        $this->quba->process_all_actions();

        $transaction = $DB->start_delegated_transaction();
        \question_engine::save_questions_usage_by_activity($this->quba);
        $transaction->allow_commit();

        // Log the submit.
        \filter_embedquestion\event\question_attempted::create(['context' => $this->coursecontext,
                'objectid' => $this->current_question()->id])->trigger();
    }

    /**
     * Log that the user is viewing the question.
     */
    public function log_view() {
        \filter_embedquestion\event\question_viewed::create(['context' => $this->coursecontext,
                'objectid' => $this->current_question()->id])->trigger();
    }

    /**
     * Get the base part of URL we need in various places.
     *
     * @param question_options $options question options.
     * @return \moodle_url the URL.
     */
    public function get_action_url(question_options $options): \moodle_url {
        return $options->get_action_url($this->quba, $this->embedid);
    }
    /**
     * Render the currently active question, including the required form.
     *
     * @param \filter_embedquestion\output\renderer instance of our renderer to use.
     * @param question_options $options options about how the attempt should function. May get updated.
     * @return string HTML to display.
     */
    public function render_question(\filter_embedquestion\output\renderer $renderer,
            question_options $options): string {

        // Work out the question number to display.
        if ($this->current_question()->length) {
            $displaynumber = "\u{00a0}"; // Non-breaking space.
        } else {
            $displaynumber = 'i';
        }

        // Allow questions to initialise their JavaScript.
        $this->quba->render_question_head_html($this->slot);

        // If the question is finished, add a Start again button.
        if ($this->is_question_finished()) {
            $options->extrainfocontent = \html_writer::div(
                    \html_writer::empty_tag('input', ['type' => 'submit', 'name' => 'restart',
                            'value' => get_string('restart', 'filter_embedquestion'),
                    'class' => 'btn btn-secondary'])
                );
        }

        // Start the question form.
        $output = '';
        $output .= \html_writer::start_tag('form',
                ['method' => 'post', 'action' => $this->get_action_url($options),
                'enctype' => 'multipart/form-data', 'id' => 'responseform']);
        $output .= \html_writer::start_tag('div');
        $output .= \html_writer::empty_tag('input',
                ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $output .= \html_writer::empty_tag('input',
                ['type' => 'hidden', 'name' => 'slots', 'value' => $this->slot]);
        $output .= \html_writer::empty_tag('input',
                ['type' => 'hidden', 'name' => 'scrollpos', 'value' => '', 'id' => 'scrollpos']);
        $output .= \html_writer::end_tag('div');

        // Render the question.
        $output .= $renderer->embedded_question($this->quba, $this->slot, $options, $displaynumber);

        // Finish the question form.
        $output .= \html_writer::end_tag('form');

        return $output;
    }

    /**
     * Get the question currently being attempted.
     *
     * @return \question_definition the question.
     */
    protected function current_question(): \question_definition {
        return $this->quba->get_question($this->slot);
    }

    /**
     * Is the currently active question finished (showing final feedback)?
     *
     * @return bool true if it is.
     */
    protected function is_question_finished(): bool {
        return $this->quba->get_question_state($this->slot)->is_finished();
    }
}
