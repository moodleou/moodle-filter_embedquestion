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
* Embedquestion filter allows question bank questions to be used within other activities.
*
* @package    filter
* @subpackage embedquestion
* @copyright  2018 The Open University
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

/**
* Glossary linking filter class.
*
* NOTE: multilang glossary entries are not compatible with this filter.
*/
class filter_embedquestion extends moodle_text_filter {
    public function filter($text, array $options = array()) {
        if (!is_string($text) or empty($text)) {
            return $text;
        }
        if (strpos($text, '{Q{') === false) {
            return $text;
        }
        // Look for text to filter ({Q{ … 40 character token … }Q}).
        if (!preg_match_all('~\{Q\{[a-zA-Z0-9]*\}Q\}~', $text, $matches)) {
            return $text;
        }
        foreach ($matches as $match) {
            $token = $match[1];
            // TODO, replace the token with a suitable iframe.
        }
        return $text;
    }
}
