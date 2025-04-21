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

namespace filter_embedquestion\event;

/**
 * An event to record when someone makes a token to embed a single question.
 *
 * @property-read array $other {
 * }
 *
 * @package   filter_embedquestion
 * @category  event
 * @copyright 2018 the Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class token_created extends \core\event\base {

    #[\Override]
    protected function init() {
        $this->data['objecttable'] = 'question';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    #[\Override]
    public function get_description() {
        return "The user with id '$this->userid' created a token for embedded question " .
                "'$this->objectid' in course '$this->courseid'.";
    }

    #[\Override]
    public static function get_objectid_mapping() {
        return ['db' => 'question', 'restore' => 'question'];
    }
}
