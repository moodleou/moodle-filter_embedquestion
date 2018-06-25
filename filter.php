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
 * Embedquestion filter allows question bank questions to be used within other activities.
 *
 * @package    filter_embedquestion
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


class filter_embedquestion extends moodle_text_filter {
    const STRING_PREFIX = '{Q{';
    const STRING_SUFFIX = '}Q}';

    /**
     * @param some $text
     * @param array $options
     * @return some|string
     * @throws coding_exception
     */
    public function filter($text, array $options = array()) {
        global $PAGE;
        // TODO: Find a better way to sanity check the input stirng.
        //if(!$this->validate_input($text)) {
        //    return $text;
        //}
        if (!is_string($text) or empty($text)) {
            return $text;
        }
        // Break down the text to paragraphs
        $paragraphs = explode('</p><p>', $text);
        $courseid = $this->context->get_course_context(true)->instanceid;
        $output = '';
        foreach ($paragraphs as $i => $p) {
            //if(!$this->validate_input($p)) {
            // Look for text to filter ({Q{ … 40 character token … }Q}).
            if (!preg_match_all('~\{Q\{[a-zA-Z0-9|=\-\/]*\}Q\}~', $p, $match)) {
                $output .= $p;
            }
            if (!empty($match[0])) {
                $params = $this->tokenise($p);
                $question = question_bank::load_question($params['id']);
                $questionoptions = new filter_embedquestion\question_options($question, $courseid, $params['behaviour']);
                $src = $questionoptions->get_page_url($question->id);
                $PAGE->requires->js_call_amd('filter_embedquestion/question', 'init', array($params['id']));
                $iframeid = 'filter-embedquestion' . $params['id'];
                $iframe = "<iframe name='filter-embedquestion' id='$iframeid' width='99%' height='500px' src='$src' ></iframe>";
                $output .= $iframe;
            }
        }
        return $output;
    }

    /**
     * @param $text
     * @return bool
     * @throws moodle_exception
     */
    public function validate_input($text) {
        if (!is_string($text) or empty($text)) {
            print("'$text' is not a valid input string");
            return false;
        }
        if (strpos($text, self::STRING_PREFIX) === false) {
            print("'$text' is not a valid input string, the string should starts with '" . self::STRING_PREFIX . "'.");
            return false;
        }
        if (strpos($text, '}Q}') === false) {
            print("'$text' is not a valid input string, the string should starts with '" . self::STRING_SUFFIX . "'.");
            return false;
        }
        return true;
    }

    /**
     * Tokenise the input text, generate a temporary token and return an assossiative array.
     *
     * @param string $text
     * @return array
     */
    public function tokenise($text) {
        $text = $this->clean_text($text);
        $params = preg_split('/[|]+/', $text);
        $catandqueidnum = $params[0];
        $keyvaluepairs = array();
        foreach ($params as $param) {
            if ($param === $catandqueidnum) {
                continue;
            }
            $keyvaluepair = preg_split('/[=]+/', $param);
            $keyvaluepairs[$keyvaluepair[0]] = $keyvaluepair[1];
        }
        // TODO: sort this out properly
        // Add the hash token.
        $keyvaluepairs['token'] = hash('md5', $catandqueidnum, false);
        return $keyvaluepairs;
    }

    /**
     * Chop off the prefix '{Q{', suffix '}Q}' and return the cleaned text.
     *
     * @param string $text
     * @return string
     */
    public function clean_text($text) {
        return str_replace(self::STRING_PREFIX, '', str_replace(self::STRING_SUFFIX, '', $text));
    }
}
