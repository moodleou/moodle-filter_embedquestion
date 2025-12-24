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
 * The question attempted event.
 *
 * @property-read array $other {
 * }
 *
 * @package   filter_embedquestion
 * @category  event
 * @copyright 2018 the Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_attempted extends \core\event\base {
    #[\Override]
    protected function init() {
        $this->data['objecttable'] = 'question';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    #[\Override]
    public function get_description(): string {
        return "The user with id '$this->userid' has submitted data to the embedded question " .
                "'$this->objectid' in course '$this->courseid'.";
    }

    #[\Override]
    public static function get_objectid_mapping(): array {
        return ['db' => 'question', 'restore' => 'question'];
    }
}
