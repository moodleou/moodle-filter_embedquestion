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
use filter_embedquestion\embed_id;
use filter_embedquestion\embed_location;
use filter_embedquestion\output\renderer;
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

try {
    $qubaid = optional_param('qubaid', 0, PARAM_INT);
    if ($qubaid) {
        $slot = required_param('slot', PARAM_INT);
        $attempt->continue_current_attempt($qubaid, $slot);
    } else {
        $attempt->find_or_create_attempt();
    }
    utils::report_if_error($attempt, $context);

} catch (Exception $e) {
    // They have already seen the error once (see below), and clicked the restart button.
    if (optional_param('forcerestart', false, PARAM_BOOL)) {
        $attempt->discard_broken_attempt();
        redirect($PAGE->url);

    } else {
        // First time error happened, show a nice message, with a button to get out of the mess.
        $nexturl = new moodle_url($PAGE->url, ['forcerestart' => '1']);
        print_error('corruptattempt', 'filter_embedquestion', $nexturl, null, $e);
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
// Without this class, Safari sometimes fails to get the iframe height right.
$PAGE->add_body_class('clearfix');
/** @var renderer $renderer */
$renderer = $PAGE->get_renderer('filter_embedquestion');
echo $renderer->header();

// Output the question.
echo $attempt->render_question($renderer);

echo $renderer->footer();
