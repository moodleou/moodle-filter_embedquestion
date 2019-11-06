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
 * Question display options with helpers for use with filter_embedquestion.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_embedquestion;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/filter/embedquestion/filter.php');


/**
 * Class for handling the options for how the question is displayed.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_options extends \question_display_options {
    /** @var string the behaviour to use for this preview. */
    public $behaviour;

    /** @var number the maximum mark to use for this preview. */
    public $maxmark;

    /** @var int the variant of the question to preview. */
    public $variant;

    /**
     * The question_options constructor.
     *
     * This creates the options with all default values. Use
     * a method like set_from_request or set_from_form to complete the setup.
     */
    public function __construct() {
        $defaults = get_config('filter_embedquestion');

        $this->behaviour = $defaults->behaviour;
        $this->maxmark = null;
        $this->variant = null;
        $this->correctness = $defaults->correctness;
        $this->marks = $defaults->marks;
        $this->markdp = $defaults->markdp;
        $this->feedback = $defaults->feedback;
        $this->numpartscorrect = $defaults->feedback;
        $this->generalfeedback = $defaults->generalfeedback;
        $this->rightanswer = $defaults->rightanswer;
        $this->history = $defaults->history;
        $this->flags = self::HIDDEN;
        $this->manualcomment = self::HIDDEN;
    }

    /**
     * Single source of truth for what options exist and their types.
     *
     * @return array names and param types of the options we read from the request.
     */
    public static function get_field_types(): array {
        return array(
            'behaviour' => PARAM_ALPHA,
            'maxmark' => PARAM_FLOAT,
            'variant' => PARAM_INT,
            'correctness' => PARAM_BOOL,
            'marks' => PARAM_INT,
            'markdp' => PARAM_INT,
            'feedback' => PARAM_BOOL,
            'generalfeedback' => PARAM_BOOL,
            'rightanswer' => PARAM_BOOL,
            'history' => PARAM_BOOL,
        );
    }

    /**
     * Set the value of any fields included in the request.
     */
    public function set_from_request(): void {
        foreach (self::get_field_types() as $field => $type) {
            $this->$field = optional_param($field, $this->$field, $type);
        }
        $this->numpartscorrect = $this->feedback;

        $contextid = optional_param('contextid', null, PARAM_INT);
        if ($contextid) {
            $this->context = \context_helper::instance_by_id($contextid);
        }
    }

    /**
     * Set the value of any fields from the $params that came from the filter.
     *
     * @param array $params that came from parsing the filter embed code.
     */
    public function set_from_filter_options(array $params): void {
        foreach (self::get_field_types() as $field => $type) {
            if (array_key_exists($field, $params) && $params[$field] !== '') {
                $this->$field = clean_param($params[$field], $type);
            }
        }
        $this->numpartscorrect = $this->feedback;
    }

    /**
     * Add parameters representing this location to a URL.
     *
     * @param \moodle_url $url the URL to add to.
     */
    public function add_params_to_url(\moodle_url $url): void {
        foreach (self::get_field_types() as $field => $notused) {
            if (is_null($this->$field)) {
                continue;
            }
            $url->param($field, $this->$field);
        }
    }

    /**
     * Get the non-default URL parameters from form.
     *
     * It is assumed that the form data is all already validated.
     *
     * @param \stdClass $fromform the form data. E.g. from the form in question_helper.
     *
     * @return string the code that the filter will process to show this question.
     */
    public static function get_embed_from_form_options(\stdClass $fromform): string {

        $embedid = new embed_id($fromform->categoryidnumber, $fromform->questionidnumber);
        $parts = [(string) $embedid];
        foreach (self::get_field_types() as $field => $type) {
            if (!isset($fromform->$field) || $fromform->$field === '') {
                continue;
            }

            $value = clean_param($fromform->$field, $type);
            $parts[] = $field . '=' . $value;
        }
        $parts[] = token::make_secret_token($embedid);

        return \filter_embedquestion::STRING_PREFIX . implode('|', $parts) . \filter_embedquestion::STRING_SUFFIX;
    }
}
