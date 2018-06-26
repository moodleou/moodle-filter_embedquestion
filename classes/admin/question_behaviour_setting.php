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
 * Admin settings class for embed question filter default behaviour.
 *
 * @package   filter_embedquestion
 * @category  admin
 * @copyright 2018 the Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace filter_embedquestion\admin;
use filter_embedquestion\utils;

defined('MOODLE_INTERNAL') || die();


/**
 * Admin settings class to select a questoin behaviour that can finish during the attempt.
 *
 * Just so we can lazy-load the choices.
 *
 * @copyright 2018 the Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_behaviour_setting extends \admin_setting_configselect {
    public function load_choices() {
        if (is_array($this->choices)) {
            return true;
        }

        $this->choices = utils::behaviour_choices();

        return true;
    }
}
