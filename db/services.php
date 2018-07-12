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
 * Web service declarations.
 *
 * @package   filter_embedquestion
 * @copyright 2018 the Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'filter_embedquestion_get_sharable_question_choices' => [
        'classname' => 'filter_embedquestion\external',
        'methodname' => 'get_sharable_question_choices',
        'classpath' => '',
        'description' => 'Use by form autocomplete for selecting a sharable question.',
        'type' => 'read',
        'ajax' => true,
    ],

    'filter_embedquestion_get_embed_code' => [
        'classname' => 'filter_embedquestion\external',
        'methodname' => 'get_embed_code',
        'classpath' => '',
        'description' => 'Use by atto-editer embedquestion button.',
        'type' => 'read',
        'ajax' => true,
    ],
];
