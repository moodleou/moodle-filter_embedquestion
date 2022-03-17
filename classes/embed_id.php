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

/**
 * Simple class to represent a categoryidnumber/questionidnumber embed id.
 *
 * @package   filter_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class embed_id {
    private const TO_ESCAPE = ['%', '/', '|'];
    private const ESCAPED = ['%25', '%2F', '%7C'];

    /**
     * @var string the category idnumber.
     */
    public $categoryidnumber;

    /**
     * @var string the question idnumber.
     */
    public $questionidnumber;

    /**
     * Simple embed_id constructor.
     *
     * @param string $categoryidnumber the category idnumber.
     * @param string $questionidnumber the question idnumber.
     */
    public function __construct(string $categoryidnumber, string $questionidnumber) {
        $this->categoryidnumber = $categoryidnumber;
        $this->questionidnumber = $questionidnumber;
    }

    /**
     * Create an embed id from a string that was output by ou to-string method.
     *
     * @param string $questioninfo a string in the form output by {@see __toString()}.
     * @return embed_id|null if the string can be parse
     */
    public static function create_from_string(string $questioninfo): ?embed_id {
        if (strpos($questioninfo, '/') === false) {
            return null;
        }

        list($categoryidnumber, $questionidnumber) = explode('/', $questioninfo, 2);
        return new embed_id(str_replace(self::ESCAPED, self::TO_ESCAPE, $categoryidnumber),
                str_replace(self::ESCAPED, self::TO_ESCAPE, $questionidnumber));
    }

    /**
     * To-string method.
     *
     * @return string categoryidnumber/questionidnumber.
     */
    public function __toString(): string {
        return str_replace(self::TO_ESCAPE, self::ESCAPED, $this->categoryidnumber) . '/' .
                str_replace(self::TO_ESCAPE, self::ESCAPED, $this->questionidnumber);
    }

    /**
     * To-string method.
     *
     * @return string categoryidnumber/questionidnumber - but cleaned up to only have characters
     *       that are safe in HTML id attributes.
     */
    public function to_html_id(): string {
        return clean_param($this->categoryidnumber, PARAM_ALPHANUMEXT) . '/' .
                clean_param($this->questionidnumber, PARAM_ALPHANUMEXT);
    }

    /**
     * Add parameters representing this location to a URL.
     *
     * @param \moodle_url $url the URL to add to.
     */
    public function add_params_to_url(\moodle_url $url): void {
        $url->param('catid', $this->categoryidnumber);
        $url->param('qid', $this->questionidnumber);
    }
}
