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


/**
 * Displays question preview options as default and set the options
 * Setting default, getting and setting user preferences in question preview options.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_options extends \question_display_options {
    /** @var the course id the qusetion is being displayed within. */
    public $courseid;

    /** @var string the behaviour to use for this preview. */
    public $behaviour;

    /** @var number the maximum mark to use for this preview. */
    public $maxmark;

    /** @var int the variant of the question to preview. */
    public $variant;

    /**
     * Constructor.
     */
    public function __construct($question, $courseid, $behaviour) {
        $this->courseid = $courseid;
        $this->behaviour = $behaviour;
        $this->maxmark = $question->defaultmark;
        $this->variant = null;
        $this->correctness = self::VISIBLE;
        $this->marks = self::MARK_AND_MAX;
        $this->markdp = 2;
        $this->feedback = self::VISIBLE;
        $this->numpartscorrect = $this->feedback;
        $this->generalfeedback = self::VISIBLE;
        $this->rightanswer = self::VISIBLE;
        $this->history = self::HIDDEN;
        $this->flags = self::HIDDEN;
        $this->manualcomment = self::HIDDEN;
    }

    /**
     * @return array names and param types of the options we read from the request.
     */
    protected function get_field_types() {
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
        foreach ($this->get_field_types() as $field => $type) {
            $this->$field = optional_param($field, $this->$field, $type);
        }
        $this->numpartscorrect = $this->feedback;
    }

    /**
     * Get the URL parameters needed for starting or continuing the display of a question.
     *
     * @return array URL parameters.
     */
    protected function get_url_params($questionid, $qubaid = null) {
        $token = token::make_iframe_token($questionid);
        $params = ['id' => $questionid, 'course' => $this->courseid, 'token' => $token];
        if ($qubaid) {
            $params['qubaid'] = $qubaid;
        }

        foreach ($this->get_field_types() as $field => $notused) {
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
     * @param int $questionid the question to view with these options.
     * @return \moodle_url the URL.
     */
    public function get_page_url($questionid) {
        return new \moodle_url('/filter/embedquestion/showquestion.php',
                $this->get_url_params($questionid));
    }

    /**
     * Get the URL for continuing interacting with a given attempt at this question.
     *
     * @param \question_usage_by_activity $quba the usage.
     * @param int $slot the slot number.
     * @return \moodle_url the URL.
     */
    public function get_action_url(\question_usage_by_activity $quba, $slot) {
        return new \moodle_url('/filter/embedquestion/showquestion.php',
                $this->get_url_params($quba->get_question($slot)->id, $quba->get_id()));
    }
}
