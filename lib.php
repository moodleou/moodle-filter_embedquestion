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
 * Question display options with helpers for use with filter_embedquestion.
 *
 * @package    filter_embedquestion
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use filter_embedquestion\attempt_storage;

defined('MOODLE_INTERNAL') || die();

/**
 * Called via pluginfile.php -> question_pluginfile to serve files belonging to
 * a question in a question_attempt when that attempt an embedded question.
 *
 * @category files
 * @param stdClass $givencourse course settings object
 * @param stdClass $context context object
 * @param string $component the name of the component we are serving files for.
 * @param string $filearea the name of the file area.
 * @param int $qubaid the question_usage this image belongs to.
 * @param int $slot the relevant slot within the usage.
 * @param array $args the remaining bits of the file path.
 * @param bool $forcedownload whether the user must be forced to download the file.
 * @param array $fileoptions additional options affecting the file serving
 */
function filter_embedquestion_question_pluginfile($givencourse, $context, $component,
        $filearea, $qubaid, $slot, $args, $forcedownload, $fileoptions) {

    list($context, $course, $cm) = get_context_info_array($context->id);
    if ($givencourse->id !== $course->id) {
        send_file_not_found();
    }
    require_login($course, false, $cm);

    $quba = question_engine::load_questions_usage_by_activity($qubaid);
    attempt_storage::instance()->verify_usage($quba, $context);

    $options = new question_display_options();
    $options->feedback = question_display_options::VISIBLE;
    $options->numpartscorrect = question_display_options::VISIBLE;
    $options->generalfeedback = question_display_options::VISIBLE;
    $options->rightanswer = question_display_options::VISIBLE;
    $options->manualcomment = question_display_options::VISIBLE;
    $options->history = question_display_options::VISIBLE;
    if (!$quba->check_file_access($slot, $options, $component,
        $filearea, $args, $forcedownload)) {
        send_file_not_found();
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/{$context->id}/{$component}/{$filearea}/{$relativepath}";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $fileoptions);
}
