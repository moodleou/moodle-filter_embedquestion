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
 * CRUD for tokens.
 *
 * @package    filter
 * @subpackage embedquestion
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_embedquestion;

defined('MOODLE_INTERNAL') || die();

class data {

    public static $table = 'filter_embedquestion';

    public static function create_token(\stdClass $data) {
        global $DB;
        if (empty($data->cmid)) {
            return false;
        }
        $token = sha1($data->cmid . $data->qidnum . $data->catidnum . time());
        if (self::check_token_exists($token)) {
            return false;
        } else {
            $data->token = $token;
            self::create_record($data);
            return $token;
        }
    }

    public static function create_record(\stdClass $data) {
        global $DB;
        if (empty($data->cmid) || empty($data->token)) {
            return false;
        }
        $data->timecreated = time();
        $data->timemodified = $data->timecreated;
        return $DB->insert_record(self::$table, $data, true);
    }

    public static function get_data_from_token($token) {
        global $DB;
        return $DB->get_record(self::$table, ['token' => $token], '*', MUST_EXIST);
    }

    public static function check_token_exists($token, $cmid = 0) {
        global $DB;
        $conditions = ['token' => $token];
        if ($cmid) {
            $conditions['cmid'] = $cmid;
        }
        return $DB->record_exists(self::$table, $conditions);
    }

    public static function update_token(\stdClass $data) {
        global $DB;
        if (empty($data->token) || self::check_token_exists($data->token)) {
            return false;
        }
        $existing = self::get_data_from_token($data->token);
        $data->id = $existing->id;
        return $DB->update_record(self::$table, $data);
    }

    public static function delete_token($token) {
        global $DB;
        if (empty($token)) {
            return false;
        }
        if (!self::check_token_exists($token)) {
            return false;
        }
        return $DB->delete_records(self::$table, ['token' => $token]);
    }

}
