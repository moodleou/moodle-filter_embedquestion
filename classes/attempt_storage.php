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
class attempt_storage {

    /**
     * Private constructor. Use {@link instance()} to get an instance.
     */
    protected function __construct() {
    }

    /**
     * Static factory: get the most appropriate attempt_manager to use.
     *
     * If report_embedquestion is installed, then we use its implementation,
     * which stores the attempts long-term. Otherwise we use our own, which
     * does not keep data long term.
     *
     * @return attempt_storage
     */
    public static function instance(): attempt_storage {
        if (class_exists('\report_embedquestion\attempt_storage')) {
            return new \report_embedquestion\attempt_storage();
        } else {
            return new self();
        }
    }

    /**
     * Is there already an attempt at this question in this location?
     *
     * @param embed_id $embedid identity of the question(s) being embedded in this place.
     * @param embed_location $embedlocation where the question(s) are being embedded.
     * @param \stdClass $user The user who is attempting the question (defaults to $USER).
     * @return array [question_usage, int slot number], or [null, 0] if not found.
     */
    public function find_existing_attempt(embed_id $embedid, embed_location $embedlocation,
            \stdClass $user): array {
        return [null, 0];
    }

    /**
     * Update the timemodified time associated with this attempt.
     *
     * @param int $qubaid usage id for the attempt to update.
     */
    public function update_timemodified(int $qubaid): void {
    }

    /**
     * Make a new usage. Will only be called if find_existing_attempt has not found anything.
     *
     * Do not try to save the new usage yet. That won't work until MDL-66685 is fixed.
     * {@link new_usage_saved()} will be called once the usage id is known.
     *
     * @param embed_id $embedid identity of the question(s) being embedded in this place.
     * @param embed_location $embedlocation where the question(s) are being embedded.
     * @param \stdClass $user the user who is attempting the question (defaults to $USER).
     * @return \question_usage_by_activity usage to use.
     */
    public function make_new_usage(embed_id $embedid, embed_location $embedlocation,
            \stdClass $user): \question_usage_by_activity {
        $quba = \question_engine::make_questions_usage_by_activity(
                'filter_embedquestion', \context_user::instance($user->id));
        return $quba;
    }

    /**
     * New usage has been saved so we now know its id.
     *
     * Called after {@link make_new_usage()} and after the at least one
     * question_attempt has been added.
     *
     * @param \question_usage_by_activity $quba the usage that has just been saved.
     * @param embed_id $embedid identity of the question(s) being embedded in this place.
     * @param embed_location $embedlocation where the question(s) are being embedded.
     * @param \stdClass $user the user who is attempting the question (defaults to $USER).
     */
    public function new_usage_saved(\question_usage_by_activity $quba,
            embed_id $embedid, embed_location $embedlocation, \stdClass $user): void {
    }

    /**
     * Checks to verify that a given usage is one we should be using.
     *
     * Throws an exception if not OK to continue.
     *
     * @param \question_usage_by_activity $quba the usage to check.
     * @param \context $context the context we are in.
     */
    public function verify_usage(\question_usage_by_activity $quba, \context $context): void {
        global $USER;

        if ($quba->get_owning_component() != 'filter_embedquestion') {
            throw new \moodle_exception('notyourattempt', 'filter_embedquestion');
        }
        // Since responses are not stored long term, users can only access their own attempts.
        if ($quba->get_owning_context()->instanceid !== $USER->id) {
            throw new \moodle_exception('notyourattempt', 'filter_embedquestion');
        }
    }

    /**
     * Completely delete the attempt corresponding to this usage.
     *
     * This includes deleting the usage.
     *
     * @param \question_usage_by_activity $quba
     */
    public function delete_attempt(\question_usage_by_activity $quba) {
        // Do nothing here. Use by subclasses.
    }
}
