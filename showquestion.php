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

use filter_embedquestion\attempt;
use filter_embedquestion\attempt_storage;
use filter_embedquestion\embed_id;
use filter_embedquestion\embed_location;
use filter_embedquestion\utils;

// Check login.
$contextid = required_param('contextid', PARAM_INT);
list($context, $course, $cm) = get_context_info_array($contextid);
require_login($course, false, $cm);
$PAGE->set_pagelayout('embedded');
$PAGE->requires->js_call_amd('filter_embedquestion/question', 'init');

if (isguestuser()) {
    print_error('noguests', 'filter_embedquestion');
}

// Process other parameters.
$categoryidnumber = required_param('catid', PARAM_RAW);
$questionidnumber = required_param('qid', PARAM_RAW);
$embedid = new embed_id($categoryidnumber, $questionidnumber);

$embedlocation = embed_location::make_from_url_params();

$options = new filter_embedquestion\question_options();
$options->set_from_request();

$PAGE->set_url(utils::get_show_url($embedid, $embedlocation, $options));

$token = required_param('token', PARAM_RAW);
if ($token !== $PAGE->url->param('token')) {
    print_error('invalidtoken', 'filter_embedquestion');
}

// Either continue existing current attempt, or find/create one.
$attempt = new attempt($embedid, $embedlocation, $USER, $options);
utils::report_if_error($attempt, $context);

$attemptstorage = attempt_storage::instance();
$qubaid = optional_param('qubaid', 0, PARAM_INT);
if ($qubaid) {
    // Continuing the current attempt.
    $slot = required_param('slot', PARAM_INT);
    try {
        $quba = question_engine::load_questions_usage_by_activity($qubaid);
    } catch (Exception $e) {
        // This may not seem like the right error message to display, but
        // actually from the user point of view, it makes sense.
        throw new moodle_exception('submissionoutofsequencefriendlymessage', 'question',
                $PAGE->url, null, $e);
    }

    $attemptstorage->verify_usage($quba, $context);
    $quba->get_question($slot); // Verifies that the slot exists.

    $attempt->setup_usage_info($quba, $slot);

} else {
    // We don't know if there is a current attempt. Find or make one.
    list($existingquba, $slot) = $attemptstorage->find_existing_attempt($embedid, $embedlocation, $USER);
    if ($existingquba) {
        $attempt->setup_usage_info($existingquba, $slot);
    } else {
        // There is not already an attempt at this question. Start one.
        $transaction = $DB->start_delegated_transaction();
        $quba = $attemptstorage->make_new_usage($embedid, $embedlocation, $USER);
        $quba->set_preferred_behaviour($options->behaviour);
        $attempt->start_new_attempt_at_question($quba);
        utils::report_if_error($attempt, $context);
        $attemptstorage->new_usage_saved($quba, $embedid, $embedlocation, $USER);
        $transaction->allow_commit();
    }
}

// Process any actions from the buttons at the bottom of the form.
if (data_submitted() && confirm_sesskey()) {
    try {
        if (optional_param('restart', false, PARAM_BOOL)) {
            $attempt->start_new_attempt_at_question();
            redirect($attempt->get_action_url());

        } else {
            $attempt->process_submitted_actions();

            $actionurl = $attempt->get_action_url();
            $scrollpos = optional_param('scrollpos', '', PARAM_RAW);
            if ($scrollpos !== '') {
                $actionurl->param('scrollpos', (int) $scrollpos);
            }
            redirect($actionurl);
        }

    } catch (question_out_of_sequence_exception $e) {
        print_error('submissionoutofsequencefriendlymessage', 'question',
                $attempt->get_action_url());

    } catch (Exception $e) {
        // This sucks, if we display our own custom error message, there is no way
        // to display the original stack trace.
        $debuginfo = '';
        if (!empty($e->debuginfo)) {
            $debuginfo = $e->debuginfo;
        }
        print_error('errorprocessingresponses', 'question',
                $attempt->get_action_url(), $e->getMessage(), $debuginfo);
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
echo $attempt->render_question($renderer);

echo $renderer->footer();
