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
 * Simple class to represent a categoryidnumber/questionidnumber embed code.
 *
 * @package   filter_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_embedquestion;
defined('MOODLE_INTERNAL') || die();


/**
 * Simple class to represent a categoryidnumber/questionidnumber embed id.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class embed_id {
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
     * To-string method.
     *
     * @return string categoryidnumber/questionidnumber.
     */
    public function __toString(): string {
        return $this->categoryidnumber . '/' . $this->questionidnumber;
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
