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
 * Web service end point for the embed question filter.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_embedquestion;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/externallib.php');


/**
 * External API for AJAX calls.
 */
class external extends \external_api {
    /**
     * Returns parameter types for get_status function.
     *
     * @return \external_function_parameters Parameters
     */
    public static function get_sharable_question_choices_parameters() {
        return new \external_function_parameters([
                'courseid' => new \external_value(PARAM_INT, 'Course id.'),
                'categoryidnumber' => new \external_value(PARAM_RAW, 'Idnumber of the question category.'),
        ]);
    }

    /**
     * Returns result type for get_status function.
     *
     * @return \external_description Result type
     */
    public static function get_sharable_question_choices_returns() {
        return new \external_multiple_structure(
            new \external_single_structure([
                    'value' => new \external_value(PARAM_RAW, 'Choice value to return from the form.'),
                    'label' => new \external_value(PARAM_RAW, 'Choice name, to display to users.'),
            ]));
    }

    /**
     * Confirms that the get_status function is allowed from AJAX.
     *
     * @return bool True
     */
    public static function get_sharable_question_choices_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Get the list of sharable questions in a category.
     *
     * @return array of arrays with two elements, keys value and label.
     */
    public static function get_sharable_question_choices($courseid, $categoryidnumber) {
        global $USER;

        self::validate_parameters(self::get_sharable_question_choices_parameters(),
                array('courseid' => $courseid, 'categoryidnumber' => $categoryidnumber));

        $context = \context_course::instance($courseid);
        self::validate_context($context);

        if (has_capability('moodle/question:useall', $context)) {
            $userlimit = null;
        } else if (has_capability('moodle/question:usemine', $context)) {
            $userlimit = $USER->id;
        } else {
            throw new \coding_exception('This user is not allowed to embed questions.');
        }

        $category = utils::get_category_by_idnumber($context, $categoryidnumber);
        if (!$category) {
            throw new \coding_exception('Unknown question category.');
        }
        $choices = utils::get_sharable_question_choices($category->id, $userlimit);

        $out = [];
        foreach ($choices as $value => $label) {
            $out[] = ['value' => $value, 'label' => $label];
        }
        return $out;
    }

    /**
     * Returns parameter types for get_embed_code function.
     *
     * @return \external_function_parameters Parameters
     */
    public static function get_embed_code_parameters() {
        return new \external_function_parameters([
                'courseid' => new \external_value(PARAM_INT, 'Course id.'),
                'categoryidnumber' => new \external_value(PARAM_RAW, 'Idnumber of the question category.'),
                'questionidnumber' => new \external_value(PARAM_RAW, 'Idnumber of the question.'),
                'behaviour' => new \external_value(PARAM_RAW, 'Question behaviour.'),
                'maxmark' => new \external_value(PARAM_RAW_TRIMMED, 'Question maximum mark.'),
                'variant' => new \external_value(PARAM_RAW_TRIMMED, 'Question variant.'),
                'correctness' => new \external_value(PARAM_RAW, 'Question correctness (show/hide).'),
                'marks' => new \external_value(PARAM_RAW, 'Question marks (hide/show mark & max, max only).'),
                'markdp' => new \external_value(PARAM_RAW, 'Decimal places in grades.'),
                'feedback' => new \external_value(PARAM_RAW, 'Specific feedback.'),
                'generalfeedback' => new \external_value(PARAM_RAW, 'Genaral feedback.'),
                'rightanswer' => new \external_value(PARAM_RAW, 'Right answer (show/hide).'),
                'history' => new \external_value(PARAM_RAW, 'Question response hidtory(show/hide).'),
        ]);
    }

    /**
     * Returns result type for for get_embed_code function.
     *
     * @return \external_description Result type
     */
    public static function get_embed_code_returns() {
        return new \external_value(PARAM_RAW, 'Embed code to show this question with those options.');
    }

    /**
     * Confirms that the get_embed_code function is allowed from AJAX.
     *
     * @return bool True
     */
    public static function get_embed_code_is_allowed_from_ajax() {
        return true;
    }

    /**
     * Given the course id, category and question idnumbers, and any display options,
     * return the {Q{...}Q} code needed to embed this question.
     *
     * @return string the embed code.
     */
    public static function get_embed_code($courseid, $categoryidnumber, $questionidnumber, $behaviour,
            $maxmark, $variant, $correctness, $marks, $markdp, $feedback, $generalfeedback, $rightanswer, $history) {
        global $CFG;

        self::validate_parameters(self::get_embed_code_parameters(),
                array('courseid' => $courseid, 'categoryidnumber' => $categoryidnumber, 'questionidnumber' => $questionidnumber,
                        'behaviour' => $behaviour, 'maxmark' => $maxmark, 'variant' => $variant, 'correctness' => $correctness,
                        'marks' => $marks, 'markdp' => $markdp, 'feedback' => $feedback, 'generalfeedback' => $generalfeedback,
                        'rightanswer' => $rightanswer, 'history' => $history,
                ));

        $context = \context_course::instance($courseid);
        self::validate_context($context);

        // Check permissions.
        require_once($CFG->libdir . '/questionlib.php');
        $category = utils::get_category_by_idnumber($context, $categoryidnumber);
        $questiondata = utils::get_question_by_idnumber($category->id, $questionidnumber);
        $question = \question_bank::load_question($questiondata->id);
        question_require_capability_on($question, 'use');

        $fromform = new \stdClass();
        $fromform->courseid = $courseid;
        $fromform->categoryidnumber = $categoryidnumber;
        $fromform->questionidnumber = $questionidnumber;
        $fromform->behaviour = $behaviour;
        $fromform->maxmark = $maxmark;
        $fromform->variant = $variant;
        $fromform->correctness = $correctness;
        $fromform->marks = $marks;
        $fromform->markdp = $markdp;
        $fromform->feedback = $feedback;
        $fromform->generalfeedback = $generalfeedback;
        $fromform->rightanswer = $rightanswer;
        $fromform->history = $history;

        // Log this.
        \filter_embedquestion\event\token_created::create(
                ['context' => $context, 'objectid' => $question->id])->trigger();

        return question_options::get_embed_from_form_options($fromform);
    }
}
