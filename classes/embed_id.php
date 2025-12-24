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
    /** @var string[] Characters that need to be escaped. */
    private const TO_ESCAPE = ['%', '/', '|'];

    /** @var string[] The escaped equivalents. */
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
     * @var string the course shortname.
     */
    public $courseshortname;
    /**
     * @var string the question bank idnumber.
     */
    public $questionbankidnumber;

    /**
     * Simple embed_id constructor.
     *
     * @param string $categoryidnumber the category idnumber.
     * @param string $questionidnumber the question idnumber.
     * @param null|string $questionbankidnumber the question bank idnumber, optional.
     * @param null|string $courseshortname the course shortname, optional.
     */
    public function __construct(
        string $categoryidnumber,
        string $questionidnumber,
        ?string $questionbankidnumber = null,
        ?string $courseshortname = null
    ) {
        $this->categoryidnumber = $categoryidnumber;
        $this->questionidnumber = $questionidnumber;
        $this->questionbankidnumber = $questionbankidnumber;
        $this->courseshortname = $courseshortname;
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
        $parts = explode('/', $questioninfo);
        // Ensure 4 parts, right-aligned.
        $parts = array_pad($parts, -4, '');
        // Assign in order: courseshortname, qbankid, categoryid, questionid.
        [$courseshortname, $questionbankidnumber, $categoryidnumber, $questionidnumber] = $parts;
        return new embed_id(
            str_replace(
                self::ESCAPED,
                self::TO_ESCAPE,
                $categoryidnumber
            ),
            str_replace(
                self::ESCAPED,
                self::TO_ESCAPE,
                $questionidnumber
            ),
            str_replace(
                self::ESCAPED,
                self::TO_ESCAPE,
                $questionbankidnumber
            ),
            str_replace(
                self::ESCAPED,
                self::TO_ESCAPE,
                $courseshortname
            )
        );
    }

    /**
     * To-string method.
     *
     * @return string categoryidnumber/questionidnumber.
     */
    public function __toString(): string {
        $optional = !empty($this->courseshortname) ?
                str_replace(self::TO_ESCAPE, self::ESCAPED, $this->courseshortname) . '/' : '';
        $optional .= !empty($this->questionbankidnumber) ?
                str_replace(self::TO_ESCAPE, self::ESCAPED, $this->questionbankidnumber) . '/' : '';

        return  $optional .
                str_replace(self::TO_ESCAPE, self::ESCAPED, $this->categoryidnumber) . '/' .
                str_replace(self::TO_ESCAPE, self::ESCAPED, $this->questionidnumber);
    }

    /**
     * To-string method.
     *
     * @return string categoryidnumber/questionidnumber - but cleaned up to only have characters
     *       that are safe in HTML id attributes.
     */
    public function to_html_id(): string {
        $optional = !empty($this->courseshortname) ? clean_param($this->courseshortname, PARAM_ALPHANUMEXT) . '/' : '';
        $optional .= !empty($this->questionbankidnumber) ? clean_param($this->questionbankidnumber, PARAM_ALPHANUMEXT) . '/' : '';

        return $optional .
                clean_param($this->categoryidnumber, PARAM_ALPHANUMEXT) . '/' .
                clean_param($this->questionidnumber, PARAM_ALPHANUMEXT);
    }

    /**
     * Add parameters representing this location to a URL.
     *
     * @param \moodle_url $url the URL to add to.
     */
    public function add_params_to_url(\moodle_url $url): void {
        $url->param('courseshortname', $this->courseshortname);
        $url->param('questionbankidnumber', $this->questionbankidnumber);
        $url->param('catid', $this->categoryidnumber);
        $url->param('qid', $this->questionidnumber);
    }
}
