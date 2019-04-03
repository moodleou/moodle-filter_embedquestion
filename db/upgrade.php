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
 * Upgrade script for filter_embedquestion.
 *
 * @package   filter_embedquestion
 * @copyright 2019 the Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Embed question plugin upgrade function.
 *
 * @param string $oldversion the version we are upgrading from.
 * @return bool true
 */
function xmldb_filter_embedquestion_upgrade($oldversion) {

    // This upgrade will update the question and question_categories
    // tables by extracting the idnumber from the name and putting it
    // in the idnumber field.
    $newversion = 2019032900;
    if ($oldversion < $newversion) {
        $updater = new filter_embedquestion\idnumber_upgrader();
        $updater->update_question_category_idnumbers();
        $updater->update_question_idnumbers();

        // Filter_embedquestion savepoint reached.
        upgrade_plugin_savepoint(true, $newversion, 'filter', 'embedquestion');
    }

    return true;
}
