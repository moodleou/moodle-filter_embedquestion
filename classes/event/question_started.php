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
 * The question viewed event.
 *
 * @package   filter_embedquestion
 * @category  event
 * @copyright 2018 the Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace filter_embedquestion\event;
defined('MOODLE_INTERNAL') || die();


/**
 * The question viewed event.
 *
 * @property-read array $other {
 * }
 *
 * @package   filter_embedquestion
 * @copyright 2018 the Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_started extends \core\event\base {

    protected function init() {
        $this->data['objecttable'] = 'question';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    public function get_description() {
        return "The user with id '$this->userid' has started an attempt at embedded question " .
                "'$this->objectid' in course '$this->courseid'.";
    }

    protected function get_legacy_logdata() {
        return array($this->courseid, 'filter_embedquestion', 'start', '', $this->objectid);
    }

    public static function get_objectid_mapping() {
        return array('db' => 'question', 'restore' => 'question');
    }
}
