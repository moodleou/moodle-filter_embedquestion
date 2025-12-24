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

use core\exception\moodle_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

/**
 * External API for AJAX calls.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends \external_api {
    /**
     * Returns parameter types for get_status function.
     *
     * @return \external_function_parameters Parameters
     */
    public static function get_sharable_question_choices_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
            'categoryidnumber' => new \external_value(PARAM_RAW, 'Idnumber of the question category.'),
        ]);
    }

    /**
     * Returns result type for get_status function.
     *
     * @return \external_description Result type
     */
    public static function get_sharable_question_choices_returns(): \external_description {
        return new \external_multiple_structure(
            new \external_single_structure([
                'value' => new \external_value(PARAM_RAW, 'Choice value to return from the form.'),
                'label' => new \external_value(PARAM_RAW, 'Choice name, to display to users.'),
            ])
        );
    }

    /**
     * Confirms that the get_status function is allowed from AJAX.
     *
     * @return bool True
     */
    public static function get_sharable_question_choices_is_allowed_from_ajax(): bool {
        return true;
    }

    /**
     * Get the list of sharable questions in a category.
     *
     * @param int $cmid the course module id.
     * @param string $categoryidnumber the idnumber of the question category.
     *
     * @return array of arrays with two elements, keys value and label.
     */
    public static function get_sharable_question_choices(int $cmid, string $categoryidnumber): array {
        global $USER;

        self::validate_parameters(
            self::get_sharable_question_choices_parameters(),
            ['cmid' => $cmid, 'categoryidnumber' => $categoryidnumber]
        );

        $context = \context_module::instance($cmid);
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
    public static function get_embed_code_parameters(): \external_function_parameters {
        // We can't use things like PARAM_INT for things like variant, because it is
        // and int of '' for not set.
        return new \external_function_parameters([
            'cmid' => new \external_value(
                PARAM_INT,
                'Course module id of the question bank.'
            ),
            'categoryidnumber' => new \external_value(
                PARAM_RAW,
                'Id number of the question category.'
            ),
            'questionidnumber' => new \external_value(
                PARAM_RAW,
                'Id number of the question.'
            ),
            'iframedescription' => new \external_value(
                PARAM_TEXT,
                'Iframe description.'
            ),
            'behaviour' => new \external_value(
                PARAM_RAW,
                'Question behaviour.'
            ),
            'maxmark' => new \external_value(
                PARAM_RAW_TRIMMED,
                'Question maximum mark (float or "").'
            ),
            'variant' => new \external_value(
                PARAM_RAW_TRIMMED,
                'Question variant (int or "").'
            ),
            'correctness' => new \external_value(
                PARAM_RAW_TRIMMED,
                'Whether to show question correctness (1/0/"") for show, hide or default.'
            ),
            'marks' => new \external_value(
                PARAM_RAW_TRIMMED,
                'Wheter to show mark information (0/1/2/"") for hide, show max only, show mark and max or default.'
            ),
            'markdp' => new \external_value(
                PARAM_RAW_TRIMMED,
                'Decimal places to use when outputting grades.'
            ),
            'feedback' => new \external_value(
                PARAM_RAW_TRIMMED,
                'Whether to show specific feedback (1/0/"") for show, hide or default.'
            ),
            'generalfeedback' => new \external_value(
                PARAM_RAW_TRIMMED,
                'Whether to show general feedback (1/0/"") for show, hide or default.'
            ),
            'rightanswer' => new \external_value(
                PARAM_RAW_TRIMMED,
                'Whether to show the automatically generated right answer display (1/0/"") for show, hide or default.'
            ),
            'history' => new \external_value(
                PARAM_RAW_TRIMMED,
                'Whether to show the response history (1/0/"") for show, hide or default.'
            ),
            'forcedlanguage' => new \external_value(
                PARAM_LANG,
                'Whether to force the UI language of the question. Lang code or empty string.'
            ),
        ]);
    }

    /**
     * Returns result type for for get_embed_code function.
     *
     * @return \external_description Result type
     */
    public static function get_embed_code_returns(): \external_description {
        return new \external_value(
            PARAM_RAW,
            'Embed code to show this question with those options.'
        );
    }

    /**
     * Confirms that the get_embed_code function is allowed from AJAX.
     *
     * @return bool True
     */
    public static function get_embed_code_is_allowed_from_ajax(): bool {
        return true;
    }

    /**
     * Given the course id, category and question idnumbers, and any display options,
     * return the {Q{...}Q} code needed to embed this question.
     *
     * @param int $cmid the course module id of the question bank.
     * @param string $categoryidnumber the idnumber of the question category.
     * @param string $questionidnumber the idnumber of the question to be embedded, or '*' to mean a question picked at random.
     * @param string $iframedescription the iframe description.
     * @param string $behaviour which question behaviour to use.
     * @param string $maxmark float value or ''.
     * @param string $variant int value or ''.
     * @param string $correctness 0, 1 or ''.
     * @param string $marks 0, 1, 2 or ''.
     * @param string $markdp 0-7 or ''.
     * @param string $feedback 0, 1 or ''.
     * @param string $generalfeedback 0, 1 or ''.
     * @param string $rightanswer 0, 1 or ''.
     * @param string $history 0, 1 or ''.
     * @param string $forcedlanguage moodle lang pack (e.g. 'fr') or ''.
     *
     * @return string the embed code.
     */
    public static function get_embed_code(
        int $cmid,
        string $categoryidnumber,
        string $questionidnumber,
        string $iframedescription,
        string $behaviour,
        string $maxmark,
        string $variant,
        string $correctness,
        string $marks,
        string $markdp,
        string $feedback,
        string $generalfeedback,
        string $rightanswer,
        string $history,
        string $forcedlanguage
    ): string {
        global $CFG;

        self::validate_parameters(
            self::get_embed_code_parameters(),
            [
                'cmid' => $cmid,
                'categoryidnumber' => $categoryidnumber,
                'questionidnumber' => $questionidnumber,
                'iframedescription' => $iframedescription,
                'behaviour' => $behaviour,
                'maxmark' => $maxmark,
                'variant' => $variant,
                'correctness' => $correctness,
                'marks' => $marks,
                'markdp' => $markdp,
                'feedback' => $feedback,
                'generalfeedback' => $generalfeedback,
                'rightanswer' => $rightanswer,
                'history' => $history,
                'forcedlanguage' => $forcedlanguage,
            ]
        );
        $context = \context_module::instance($cmid);
        // Check permissions.
        self::validate_context($context);
        if (!utils::has_permission($context) || !get_coursemodule_from_id('qbank', $cmid)) {
            throw new moodle_exception('errornopermissions', 'filter_embedquestion');
        }
        require_once($CFG->libdir . '/questionlib.php');
        $category = utils::get_category_by_idnumber($context, $categoryidnumber);
        if ($questionidnumber === '*') {
            require_capability('moodle/question:useall', $context);
        } else {
            $questiondata = utils::get_question_by_idnumber($category->id, $questionidnumber);
            $question = \question_bank::load_question($questiondata->id);
            question_require_capability_on($question, 'use');
        }
        // When we get the question bank created by system, usually they don't have idnumber
        // So we need to add '*' to questionbankidnumber to make sure the question bank can be found.
        [$embedcourse, $cm] = get_course_and_cm_from_cmid($cmid, 'qbank');
        $questionbankidnumber = $cm->idnumber;
        if (empty($questionbankidnumber)) {
            $questionbankidnumber = '*';
        }
        $fromform = new \stdClass();
        $fromform->questionbankidnumber = $questionbankidnumber;
        $fromform->courseshortname = $embedcourse->shortname;
        $fromform->categoryidnumber = $categoryidnumber;
        $fromform->questionidnumber = $questionidnumber;
        $fromform->iframedescription = $iframedescription;
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
        $fromform->forcedlanguage = $forcedlanguage;

        // Log this.
        if ($questionidnumber === '*') {
            \filter_embedquestion\event\category_token_created::create([
                'context' => $context,
                'objectid' => $category->id,
            ])->trigger();
        } else {
            \filter_embedquestion\event\token_created::create([
                'context' => $context,
                'objectid' => $question->id,
            ])->trigger();
        }
        return question_options::get_embed_from_form_options($fromform);
    }
}
