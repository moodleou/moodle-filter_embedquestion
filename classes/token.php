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
 * Token managment.
 *
 * @package    filter
 * @subpackage embedquestion
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_embedquestion;
defined('MOODLE_INTERNAL') || die();


class token {

    public static function make_secret_token(\stdClass $data) {
        if (empty($data->qidnum) || empty($data->catidnum)) {
            return false;
        }
        $secret = get_config('filter_embedquestion', 'secret');
        $datastring = $data->qidnum . '/' . $data->catidnum . '##' . $secret;
        $token = hash('sha256', $datastring);
        return $token;
    }

    public static function make_iframe_token($questionid) {
        $secret = get_config('filter_embedquestion', 'secret');
        return hash('sha256', "$questionid#iframe#$secret");
    }

}
