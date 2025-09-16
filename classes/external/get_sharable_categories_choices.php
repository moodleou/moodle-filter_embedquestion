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

namespace filter_embedquestion\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_description;
use filter_embedquestion\utils;

/**
 * External API to get the list of sharable question categories.
 *
 * @package   filter_embedquestion
 * @copyright 2025 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_sharable_categories_choices extends external_api {
    /**
     * Returns parameter types for get_sharable_categories_choices function.
     *
     * @return external_function_parameters Parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
        ]);
    }

    /**
     * Returns result type for get_sharable_categories_choices function.
     *
     * @return external_description Result type
     */
    public static function execute_returns(): external_description {
        return new external_multiple_structure(
            new external_single_structure([
                'value' => new external_value(PARAM_RAW, 'Choice value to return from the form.'),
                'label' => new external_value(PARAM_RAW, 'Choice name, to display to users.'),
            ]));
    }

    /**
     * Get the list of sharable categories.
     *
     * @param int $cmid the course module ID of the question bank.
     *
     * @return array of arrays with two elements, keys value and label.
     */
    public static function execute(int $cmid): array {
        global $USER;

        self::validate_parameters(self::execute_parameters(),
            ['cmid' => $cmid]);

        $context = \context_module::instance($cmid);
        self::validate_context($context);

        if (has_capability('moodle/question:useall', $context)) {
            $userlimit = null;

        } else if (has_capability('moodle/question:usemine', $context)) {
            $userlimit = $USER->id;
        } else {
            throw new \coding_exception('This user is not allowed to embed questions.');
        }

        $categories = utils::get_categories_with_sharable_question_choices($context, $userlimit);
        if (!$categories) {
            throw new \coding_exception('Unknown question category.');
        }

        $out = [];
        foreach ($categories as $value => $label) {
            $out[] = ['value' => $value, 'label' => $label];
        }
        return $out;
    }
}
