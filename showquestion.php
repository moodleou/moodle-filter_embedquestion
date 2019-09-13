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
 * This script displays a particular question for a user to interact with.
 *
 * It shows the question inside the filter_embedquestion iframe.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University - based on question/preview.php
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/questionlib.php');

use filter_embedquestion\attempt_manager;
use filter_embedquestion\embed_id;
use filter_embedquestion\utils;

// Process required parameters.
$categoryidnumber = required_param('catid', PARAM_RAW);
$questionidnumber = required_param('qid', PARAM_RAW);
$courseid = required_param('course', PARAM_INT);
$token = required_param('token', PARAM_RAW);

$embedid = new embed_id($categoryidnumber, $questionidnumber);

require_login($courseid);
$PAGE->set_pagelayout('embedded');

if (isguestuser()) {
    print_error('noguests', 'filter_embedquestion');
}
$context = context_course::instance($courseid);

if ($token !== filter_embedquestion\token::make_iframe_token($embedid)) {
    print_error('invalidtoken', 'filter_embedquestion');
}

// Process options.
$options = new filter_embedquestion\question_options($courseid);
$options->set_from_request();
$PAGE->set_url($options->get_page_url($embedid));
$PAGE->requires->js_call_amd('filter_embedquestion/question', 'init');

// Get and validate existing preview, or start a new one.
$attemptmanager = attempt_manager::instance($PAGE->context);
$qubaid = optional_param('qubaid', 0, PARAM_INT);
if ($qubaid) {
    $attempt = $attemptmanager->find_continuing_attempt(
            $embedid, $courseid, $qubaid, $options);
} else {
    $attempt = $attemptmanager->find_new_attempt(
            $embedid, $courseid, $options);
}

if (!$attempt->is_valid()) {
    if (has_capability('moodle/question:useall', $context)) {
        utils::filter_error($attempt->get_problem_description());
    } else {
        utils::filter_error('invalidtoken');
    }
}

// Process any actions from the buttons at the bottom of the form.
if (data_submitted() && confirm_sesskey()) {

    try {
        if (optional_param('restart', false, PARAM_BOOL)) {
            $attemptmanager->prepare_to_restart($attempt);
            redirect($options->get_page_url($embedid));

        } else {
            $attempt->process_submitted_actions();

            $actionurl = $attempt->get_action_url($options);
            $scrollpos = optional_param('scrollpos', '', PARAM_RAW);
            if ($scrollpos !== '') {
                $actionurl->param('scrollpos', (int) $scrollpos);
            }
            redirect($actionurl);
        }

    } catch (question_out_of_sequence_exception $e) {
        print_error('submissionoutofsequencefriendlymessage', 'question',
                $attempt->get_action_url($options));

    } catch (Exception $e) {
        // This sucks, if we display our own custom error message, there is no way
        // to display the original stack trace.
        $debuginfo = '';
        if (!empty($e->debuginfo)) {
            $debuginfo = $e->debuginfo;
        }
        print_error('errorprocessingresponses', 'question',
                $attempt->get_action_url($options), $e->getMessage(), $debuginfo);
    }
}

// Log the view.
$attempt->log_view();

// Start output.
$title = get_string('iframetitle', 'filter_embedquestion');
question_engine::initialise_js();
$PAGE->set_title($title);
$PAGE->set_heading($title);
$renderer = $PAGE->get_renderer('filter_embedquestion');
echo $renderer->header();

// Output the question.
echo $attempt->render_question($renderer, $options);

echo $renderer->footer();
