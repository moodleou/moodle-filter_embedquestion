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
 * Deals with finding or creating the usages to store question attempts.
 *
 * This default implementation does not keep attempts long term. If you
 * install report_embedquestion then there is an alternative implementation
 * which does keep the data.
 *
 * @package   filter_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_embedquestion;
defined('MOODLE_INTERNAL') || die();

/**
 * Deals with finding or creating the usages to store question attempts.
 *
 * This default implementation does not keep attempts long term. If you
 * install report_embedquestion then there is an alternative implementation
 * which does keep the data.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_manager {

    /**
     * @var \context The context where the embedded question is being displayed.
     */
    protected $context;

    /**
     * @var \stdClass The user whose attempts we are managing.
     */
    protected $user;

    /**
     * Private constructor. Use {@link instance()} to get an instance.
     *
     * @param \context $context The context where the embedded question is being displayed.
     * @param \stdClass $user The user who is attempting the question (defaults to $USER).
     */
    protected function __construct(\context $context, \stdClass $user = NULL) {
        global $USER;
        $this->context = $context;
        $this->user = $user ?? $USER;
    }

    /**
     * Static factory: get the most appropriate attempt_manager to use.
     *
     * If report_embedquestion is installed, then we use its implementation,
     * which stores the attempts long-term. Otherwise we use our own, which
     * does not keep data long term.
     *
     * @param \context $context
     * @return attempt_manager
     */
    public static function instance(\context $context) {
        if (false && class_exists('\report_embedquestion\attempt_manager')) {
            return new \report_embedquestion\attempt_manager($context);
        } else {
            return new self($context);
        }
    }

    /**
     * Create or continue an attempt at a given question, when we come in with just the id numbers.
     *
     * @param embed_id $embedid embed code for the question to embed.
     * @param int $courseid the id of the course we are in.
     * @param question_options $options options about how the attempt should function. May get updated.
     * @return attempt the newly created attempt.
     */
    public function find_new_attempt(embed_id $embedid, int $courseid,
            question_options $options): attempt {

        $attempt = new attempt($courseid, $embedid);
        if (!$attempt->is_valid()) {
            return $attempt;
        }

        list($existingquba, $slot) = $this->find_existing_attempt($embedid);
        if ($existingquba) {
            $attempt->setup_usage_info($existingquba, $slot, $options);
            return $attempt;
        }

        // There is not already an attempt at this question. Start one.
        $quba = $this->make_new_usage($options);
        $attempt->start_new_attempt_at_question($quba, $options);
        return $attempt;
    }

    /**
     * Continue the attempt at a given question when we already know the qubaid.
     *
     * @param embed_id $embedid embed code for the question to embed.
     * @param int $courseid the id of the course we are in.
     * @param int $qubaid the question usage id of the attempt we are continuing.
     * @param question_options $options options about how the attempt should function. May get updated.
     * @return attempt the newly created attempt.
     */
    public function find_continuing_attempt(embed_id $embedid,
            int $courseid, int $qubaid, question_options $options): attempt {
        global $PAGE;

        $attempt = new attempt($courseid, $embedid);
        if (!$attempt->is_valid()) {
            return $attempt;
        }

        try {
            $quba = \question_engine::load_questions_usage_by_activity($qubaid);
        } catch (\Exception $e) {
            // This may not seem like the right error message to display, but
            // actually from the user point of view, it makes sense.
            throw new \moodle_exception('submissionoutofsequencefriendlymessage', 'question',
                    $PAGE->url, null, $e);
        }

        $this->verify_usage($quba);

        $slot = $quba->get_first_question_number();
        $attempt->setup_usage_info($quba, $slot, $options);
        return $attempt;
    }

    // The following methods are the ones that subclasses are expected to override.

    /**
     * Is there already an attempt at this question?
     *
     * @param embed_id $embedid embed code for the question to embed.
     * @return array [question usage, slot number], or [null, 0] if not found.
     */
    protected function find_existing_attempt(embed_id $embedid) {
        return [null, 0];
    }

    /**
     * @param question_options $options options about how the attempt should function. May get updated.
     * @return \question_usage_by_activity usage to use.
     */
    protected function make_new_usage(question_options $options): \question_usage_by_activity {
        $quba = \question_engine::make_questions_usage_by_activity(
                'filter_embedquestion', \context_user::instance($this->user->id));
        $quba->set_preferred_behaviour($options->behaviour);
        return $quba;
    }

    /**
     * Checks to verify that a given usage is one we should be using.
     *
     * @param \question_usage_by_activity $quba the usage to check.
     */
    public function verify_usage(\question_usage_by_activity $quba) {
        if ($quba->get_owning_component() != 'filter_embedquestion') {
            throw new \moodle_exception('notyourattempt', 'filter_embedquestion');
        }
        if ($quba->get_owning_context()->instanceid !== $this->user->id) {
            throw new \moodle_exception('notyourattempt', 'filter_embedquestion');
        }
    }

    /**
     * Finish the currently active attempt, so when we next call find_new_attempt(),
     * a new attempt at this question will be started.
     *
     * You should redirect after calling this.
     *
     * @param attempt $attempt the attempt to restart.
     */
    public function prepare_to_restart(attempt $attempt) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        \question_engine::delete_questions_usage_by_activity($attempt->get_qubaid());
        $transaction->allow_commit();

        // Not logged, because we immediately redirect to start a new attempt, which is logged.
    }
}
