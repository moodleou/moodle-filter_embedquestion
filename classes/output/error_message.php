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
 * Represents an error that is shown if the question cannot be.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_embedquestion\output;

use renderer_base;

defined('MOODLE_INTERNAL') || die();


/**
 * Represents an error that is shown if the question cannot be.
 *
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class error_message implements \renderable, \templatable {
    /** @var string the error message text. */
    private $message;

    /**
     * The error_message constructor.
     *
     * @param string $string the string to use for the message.
     * @param array|\stdClass|null $a any values needed by the strings.
     */
    public function __construct($string, $a = null) {
        $this->message = get_string($string, 'filter_embedquestion', $a);
    }

    public function export_for_template(renderer_base $output) {
        return ['message' => $this->message];
    }
}
