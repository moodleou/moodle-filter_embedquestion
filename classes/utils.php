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
 * Helper functions for filter_embedquestion.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_embedquestion;
defined('MOODLE_INTERNAL') || die();


/**
 * Helper functions for filter_embedquestion.
 *
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class utils {

    /**
     * Display a warning notification if the filter is not enabled in this context.
     * @param \context $context the context to check.
     */
    public static function warn_if_filter_disabled(\context $context) {
        global $OUTPUT;
        if (!filter_is_enabled('embedquestion')) {
            echo $OUTPUT->notification(get_string('warningfilteroffglobally', 'filter_embedquestion'));
        } else {
            $activefilters = filter_get_active_in_context($context);
            if (!isset($activefilters['embedquestion'])) {
                echo $OUTPUT->notification(get_string('warningfilteroffhere', 'filter_embedquestion'));
            }
        }
    }

    /**
     * Checks to verify that a given usage is one we should be using.
     *
     * @param \question_usage_by_activity $quba the usage to check.
     */
    public static function verify_usage(\question_usage_by_activity $quba) {
        global $USER;

        if ($quba->get_owning_context()->instanceid != $USER->id) {
            throw new \moodle_exception('notyourattempt', 'filter_embedquestion');
        }
        if ($quba->get_owning_component() != 'filter_embedquestion') {
            throw new \moodle_exception('notyourattempt', 'filter_embedquestion');
        }
    }

    /**
     * Find a category with a given idnumber in a given context.
     *
     * @param \context $context a context.
     * @param string $idnumber the idnumber to look for.
     * @return \stdClass|false row from the question_categories table, or false if none.
     */
    public static function get_category_by_idnumber(\context $context, $idnumber) {
        global $DB;

        return $DB->get_record_select('question_categories',
                'contextid = ? AND ' . $DB->sql_like('name', '?'),
                [$context->id, '%[ID:' . $DB->sql_like_escape($idnumber) . ']%']);
    }

    /**
     * Find a question with a given idnumber in a given context.
     *
     * @param int $categoryid id of the question category to look in.
     * @param string $idnumber the idnumber to look for.
     * @return \stdClass|false row from the question table, or false if none.
     */
    public static function get_question_by_idnumber($categoryid, $idnumber) {
        global $DB;

        return $DB->get_record_select('question',
                'category = ? AND ' . $DB->sql_like('name', '?'),
                [$categoryid, '%[ID:' . $DB->sql_like_escape($idnumber) . ']%']);
    }

    /**
     * Get a list of the question categories in a particular context that
     * contain sharable questions (and which have an idnumber set).
     *
     * The list is returned in a form suitable for using in a select menu.
     *
     * If a userid is given, then only questions created by that user
     * are considered.
     *
     * @param \context $context a context.
     * @param int $userid (optional) if set, only count questions created by this user.
     * @return array category idnumber => Category name (question count).
     */
    public static function get_categories_with_sharable_question_choices(\context $context, $userid = null) {
        global $DB;

        $params = [];
        $params[] = '%[ID:%]%';

        $creatortest = '';
        if ($userid) {
            $creatortest = 'AND q.createdby = ?';
            $params[] = $userid;
        }
        $params[] = $context->id;
        $params[] = '%[ID:%]%';

        $categories = $DB->get_records_sql("
                SELECT qc.id, qc.name, COUNT(q.id) AS count

                  FROM {question_categories} qc
             LEFT JOIN {question} q ON q.category = qc.id
                                    AND " . $DB->sql_like('q.name', '?') . "
                                    $creatortest

                 WHERE qc.contextid = ?
                   AND " . $DB->sql_like('qc.name', '?') . "

              GROUP BY qc.id, qc.name
              ORDER BY qc.name
                ", $params);

        $choices = ['' => get_string('choosedots')];
        foreach ($categories as $category) {
            if (!preg_match('~\[ID:(.*)\]~', $category->name, $matches)) {
                continue;
            }

            $choices[$matches[1]] = get_string('nameandcount', 'filter_embedquestion',
                    ['name' => format_string($category->name), 'count' => $category->count]);
        }

        return $choices;
    }


    /**
     * Get shareable questions from a category (those which have an idnumber set).
     *
     * The list is returned in a form suitable for using in a select menu.
     *
     * If a userid is given, then only questions created by that user
     * are considered.
     *
     * @param int $categoryid id of a question category.
     * @param int $userid (optional) if set, only count questions created by this user.
     * @return array question idnumber => question name.
     */
    public static function get_sharable_question_choices($categoryid, $userid = null) {
        global $DB;

        $params = [];
        $params[] = $categoryid;
        $params[] = '%[ID:%]%';

        $creatortest = '';
        if ($userid) {
            $creatortest = 'AND q.createdby = ?';
            $params[] = $userid;
        }

        $questions = $DB->get_records_sql("
                SELECT q.name

                  FROM {question} q

                 WHERE q.category = ?
                   AND " . $DB->sql_like('q.name', '?') . "
                   $creatortest

              ORDER BY q.name
                ", $params);

        $choices = ['' => get_string('choosedots')];
        foreach ($questions as $question) {
            if (!preg_match('~\[ID:(.*)\]~', $question->name, $matches)) {
                continue;
            }

            $choices[$matches[1]] = format_string($question->name);
        }

        return $choices;
    }

    public static function behaviour_choices() {
        $behaviours = [];
        foreach (\question_engine::get_archetypal_behaviours() as $behaviour => $name) {
            $unusedoptions = \question_engine::get_behaviour_unused_display_options($behaviour);
            // Apologies for the double-negative here.
            // A behaviour is suitable if specific feedback is relevant during the attempt.
            if (!in_array('specificfeedback', $unusedoptions)) {
                $behaviours[$behaviour] = $name;
            }
        }
        return $behaviours;
    }
}
