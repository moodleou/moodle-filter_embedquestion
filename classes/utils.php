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
use filter_embedquestion\output\error_message;
use filter_embedquestion\output\renderer;

/**
 * Helper functions for filter_embedquestion.
 *
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

    /**
     * Display a warning notification if the filter is not enabled in this context.
     *
     * @param \context $context the context to check.
     */
    public static function warn_if_filter_disabled(\context $context): void {
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
     * If the attempt is in an error state, report the error appropriately and die.
     *
     * Otherwise returns so processing can continue.
     *
     * @param attempt $attempt the attempt
     * @param \context $context the context we are in.
     */
    public static function report_if_error(attempt $attempt, \context $context): void {
        if ($attempt->is_valid()) {
            return;
        }
        if (has_capability('moodle/question:useall', $context)) {
            self::filter_error($attempt->get_problem_description());
        } else {
            self::filter_error(get_string('invalidtoken', 'filter_embedquestion'));
        }
    }

    /**
     * Display an error inside the filter iframe. Does not return.
     *
     * @param string $message the error message to display.
     */
    public static function filter_error(string $message): void {
        global $PAGE;

        /** @var renderer $renderer */
        $renderer = $PAGE->get_renderer('filter_embedquestion');
        echo $renderer->header();
        echo $renderer->render(new error_message($message));
        echo $renderer->footer();

        // In a unit test, throw an exception instead of terminating.
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            throw new \coding_exception('Filter error');
        } else {
            die;
        }
    }

    /**
     * Given any context, find the associated course from which to embed questions.
     *
     * Anywhere inside a course, that is the id of that course. Outside of
     * a particular course, it is the front page course id.
     *
     * @param \context $context the current context.
     * @return int the course id to use the question bank of.
     */
    public static function get_relevant_courseid(\context $context): int {
        $coursecontext = $context->get_course_context(false);
        if ($coursecontext) {
            return $coursecontext->instanceid;
        } else {
            return SITEID;
        }
    }

    /**
     * Get the URL for showing this question in the iframe.
     *
     * @param embed_id $embedid identifies the question being embedded.
     * @param embed_location $embedlocation identifies where it is being embedded.
     * @param question_options $options the options for how it is displayed.
     * @return \moodle_url the URL to access the question.
     */
    public static function get_show_url(embed_id $embedid, embed_location $embedlocation,
            question_options $options): \moodle_url {
        $url = new \moodle_url('/filter/embedquestion/showquestion.php');
        $embedid->add_params_to_url($url);
        $embedlocation->add_params_to_url($url);
        $options->add_params_to_url($url);
        token::add_iframe_token_to_url($url);
        return $url;
    }

    /**
     * Find a category with a given idnumber in a given context.
     *
     * @param \context $context a context.
     * @param string $idnumber the idnumber to look for.
     * @return \stdClass|null row from the question_categories table, or false if none.
     */
    public static function get_category_by_idnumber(\context $context, string $idnumber): ?\stdClass {
        global $DB;

        $category = $DB->get_record_select('question_categories',
                'contextid = ? AND idnumber = ?',
                [$context->id, $idnumber]) ?? null;
        if (!$category) {
            return null;
        }
        return $category;
    }

    /**
     * Find a question with a given idnumber in a given context.
     *
     * @param int $categoryid id of the question category to look in.
     * @param string $idnumber the idnumber to look for.
     * @return \stdClass|null row from the question table, or false if none.
     */
    public static function get_question_by_idnumber(int $categoryid, string $idnumber): ?\stdClass {
        global $DB;

        $question = $DB->get_record_select('question',
                "category = ? AND idnumber = ? AND hidden = 0 AND parent = 0",
                [$categoryid, $idnumber]);
        if (!$question) {
            return null;
        }
        return $question;
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
     * @param int|null $userid (optional) if set, only count questions created by this user.
     * @return array category idnumber => Category name (question count).
     */
    public static function get_categories_with_sharable_question_choices(\context $context, int $userid = null): array {
        global $DB;

        $params = [];

        $creatortest = '';
        if ($userid) {
            $creatortest = 'AND q.createdby = ?';
            $params[] = $userid;
        }
        $params[] = $context->id;

        $categories = $DB->get_records_sql("
                SELECT qc.id, qc.name, qc.idnumber, COUNT(q.id) AS count

                  FROM {question_categories} qc
                  JOIN {question} q ON q.category = qc.id
                                    AND q.idnumber IS NOT NULL
                                    $creatortest
                                    AND q.hidden = 0
                                    AND q.parent = 0

                 WHERE qc.contextid = ?
                   AND qc.idnumber IS NOT NULL

              GROUP BY qc.id, qc.name
                HAVING COUNT(q.id) > 0
              ORDER BY qc.name
                ", $params);

        $choices = ['' => get_string('choosedots')];
        foreach ($categories as $category) {
            $choices[$category->idnumber] = get_string('nameidnumberandcount', 'filter_embedquestion',
                    ['name' => format_string($category->name), 'idnumber' => s($category->idnumber), 'count' => $category->count]);
        }
        return $choices;
    }

    /**
     * Get the ids of shareable questions from a category (those which have an idnumber set).
     *
     * If a userid is given, then only questions created by that user
     * are considered.
     *
     * @param int $categoryid id of a question category.
     * @param int|null $userid (optional) if set, only count questions created by this user.
     * @return \stdClass[] question id => object with fields question id, name and idnumber.
     */
    public static function get_sharable_question_ids(int $categoryid, int $userid = null): array {
        global $DB;

        $params = [];
        $params[] = $categoryid;

        $creatortest = '';
        if ($userid) {
            $creatortest = 'AND q.createdby = ?';
            $params[] = $userid;
        }

        return $DB->get_records_sql("
                SELECT q.id, q.name, q.idnumber

                  FROM {question} q

                 WHERE q.category = ?
                   AND q. idnumber IS NOT NULL
                   $creatortest
                   AND q.hidden = 0
                   AND q.parent = 0

              ORDER BY q.name
                ", $params);
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
     * @param int|null $userid (optional) if set, only count questions created by this user.
     * @return array question idnumber => question name.
     */
    public static function get_sharable_question_choices(int $categoryid, int $userid = null): array {
        $questions = self::get_sharable_question_ids($categoryid, $userid);

        $choices = ['' => get_string('choosedots')];
        foreach ($questions as $question) {
            $choices[$question->idnumber] = get_string('nameandidnumber', 'filter_embedquestion',
                    ['name' => format_string($question->name), 'idnumber' => s($question->idnumber)]);
        }

        // When we are not restricting by user, and there are at least 2 questions in the category,
        // allow random choice. > 2 because of the 'Choose ...' option.
        if (!$userid && count($choices) > 2) {
            $choices['*'] = get_string('chooserandomly', 'filter_embedquestion');
        }
        return $choices;
    }

    /**
     * Get the behaviours that can be used with this filter.
     *
     * @return array behaviour name => lang string for this behaviour name.
     */
    public static function behaviour_choices(): array {
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
