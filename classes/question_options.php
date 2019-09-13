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
    /** @var int the course id the qusetion is being displayed within. */
    public $courseid;

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
     *
     * @param int $courseid the course within which this question is being displayed.
     */
    public function __construct($courseid) {
        $this->courseid = $courseid;

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
    public static function get_field_types() {
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
    public function set_from_request() {
        foreach (self::get_field_types() as $field => $type) {
            $this->$field = optional_param($field, $this->$field, $type);
        }
        $this->numpartscorrect = $this->feedback;
    }

    /**
     * Set the value of any fields from the $params that came from the filter.
     *
     * @param array $params that came from parsing the filter embed code.
     */
    public function set_from_filter_options(array $params) {
        foreach (self::get_field_types() as $field => $type) {
            if (array_key_exists($field, $params) && $params[$field] !== '') {
                $this->$field = clean_param($params[$field], $type);
            }
        }
        $this->numpartscorrect = $this->feedback;
    }

    /**
     * Get the URL parameters needed for starting or continuing the display of a question.
     *
     * @param embed_id $embedid embed code for the question.
     * @param int $qubaid the usage id of the current attempt, if there is one.
     *
     * @return array URL parameters.
     */
    protected function get_url_params(embed_id $embedid, $qubaid = null) {
        $token = token::make_iframe_token($embedid);
        $params = [
            'catid'  => $embedid->categoryidnumber,
            'qid'    => $embedid->questionidnumber,
            'course' => $this->courseid,
            'token'  => $token,
        ];
        if ($qubaid) {
            $params['qubaid'] = $qubaid;
        }

        foreach (self::get_field_types() as $field => $notused) {
            if (is_null($this->$field)) {
                continue;
            }
            if ($qubaid && ($field == 'behaviour' || $field == 'maxmark')) {
                continue;
            }
            $params[$field] = $this->$field;
        }
        return $params;
    }

    /**
     * Get the URL for starting a new view of this question.
     *
     * @param embed_id $embedid embed code for the question.
     * @return \moodle_url the URL.
     */
    public function get_page_url(embed_id $embedid) {
        return new \moodle_url('/filter/embedquestion/showquestion.php',
                $this->get_url_params($embedid));
    }

    /**
     * Get the URL for continuing interacting with a given attempt at this question.
     *
     * @param \question_usage_by_activity $quba the usage.
     * @param embed_id $embedid embed code for the question.
     * @return \moodle_url the URL.
     */
    public function get_action_url(\question_usage_by_activity $quba, embed_id $embedid) {
        return new \moodle_url('/filter/embedquestion/showquestion.php',
                $this->get_url_params($embedid, $quba->get_id()));
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
    public static function get_embed_from_form_options($fromform) {

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
