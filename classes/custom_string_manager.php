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
 * Nasty hack to let us force the language for one page only.
 *
 * To use this, call custom_string_manager::force_page_language($lang);
 *
 * @package   filter_embedquestion
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_string_manager extends \core_string_manager_standard {

    /** @var string language to force. */
    protected $forcedlanguage;

    public static function force_page_language($lang) {
        global $CFG;

        $CFG->config_php_settings['customstringmanager'] = self::class;
        get_string_manager(true)->forcedlanguage = $lang;
    }

    public function get_string($identifier, $component = '', $a = null, $lang = null) {
        return parent::get_string($identifier, $component, $a, $lang ?? $this->forcedlanguage);
    }
}
