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
use filter_embedquestion\utils;

// Process required parameters.
$categoryidnumber = required_param('catid', PARAM_RAW);
$questionidnumber = required_param('qid', PARAM_RAW);
$courseid = required_param('course', PARAM_INT);
$token = required_param('token', PARAM_RAW);

require_login($courseid);
$PAGE->set_pagelayout('embedded');
if (isguestuser()) {
    print_error('noguests', 'filter_embedquestion');
}
$context = context_course::instance($courseid);

if ($token !== filter_embedquestion\token::make_iframe_token($categoryidnumber, $questionidnumber)) {
    print_error('invalidtoken', 'filter_embedquestion');
}

$category = utils::get_category_by_idnumber($context, $categoryidnumber);
if (!$category) {
    return $this->display_error('invalidtoken');
}

$questiondata = utils::get_question_by_idnumber($category->id, $questionidnumber);
if (!$questiondata) {
    return $this->display_error('invalidtoken');
}

$question = question_bank::load_question($questiondata->id);

// Process options.
$options = new filter_embedquestion\question_options($courseid);
$options->set_from_request();
$PAGE->set_url($options->get_page_url($categoryidnumber, $questionidnumber));

// Get and validate existing preview, or start a new one.
$qubaid = optional_param('qubaid', 0, PARAM_INT);
if ($qubaid) {
    try {
        $quba = question_engine::load_questions_usage_by_activity($qubaid);

    } catch (Exception $e) {
        // This may not seem like the right error message to display, but
        // actually from the user point of view, it makes sense.
        print_error('submissionoutofsequencefriendlymessage', 'question', $PAGE->url, null, $e);
    }

    filter_embedquestion\utils::verify_usage($quba);

    $slot = $quba->get_first_question_number();
    $usedquestion = $quba->get_question($slot);
    if ($usedquestion->id != $question->id) {
        print_error('questionidmismatch', 'question');
    }
    $question = $usedquestion;
    $options->variant = $quba->get_variant($slot);

} else {
    $quba = question_engine::make_questions_usage_by_activity(
            'filter_embedquestion', context_user::instance($USER->id));
    $quba->set_preferred_behaviour($options->behaviour);
    $slot = $quba->add_question($question, $options->maxmark);

    if ($options->variant) {
        $options->variant = min($question->get_num_variants(), max(1, $options->variant));
    } else {
        $options->variant = rand(1, $question->get_num_variants());
    }

    $quba->start_question($slot, $options->variant);

    $transaction = $DB->start_delegated_transaction();
    question_engine::save_questions_usage_by_activity($quba);
    $transaction->allow_commit();

    \filter_embedquestion\event\question_started::create(
            ['context' => $context, 'objectid' => $question->id])->trigger();
}
$options->behaviour = $quba->get_preferred_behaviour();
$options->maxmark = $quba->get_question_max_mark($slot);

// Prepare a URL that is used in various places.
$actionurl = $options->get_action_url($quba, $categoryidnumber, $questionidnumber);

// Process any actions from the buttons at the bottom of the form.
if (data_submitted() && confirm_sesskey()) {

    try {

        if (optional_param('restart', false, PARAM_BOOL)) {
            $transaction = $DB->start_delegated_transaction();
            question_engine::delete_questions_usage_by_activity($quba->get_id());
            $transaction->allow_commit();

            // Not logged, because we immediately redirect to start a new attempt, which is logged.

            redirect($options->get_page_url($categoryidnumber, $questionidnumber));

        } else {
            $quba->process_all_actions();

            $transaction = $DB->start_delegated_transaction();
            question_engine::save_questions_usage_by_activity($quba);
            $transaction->allow_commit();

            $scrollpos = optional_param('scrollpos', '', PARAM_RAW);
            if ($scrollpos !== '') {
                $actionurl->param('scrollpos', (int) $scrollpos);
            }

            // Log the submit.
            \filter_embedquestion\event\question_attempted::create(
                    ['context' => $context, 'objectid' => $question->id])->trigger();

            redirect($actionurl);
        }

    } catch (question_out_of_sequence_exception $e) {
        print_error('submissionoutofsequencefriendlymessage', 'question', $actionurl);

    } catch (Exception $e) {
        // This sucks, if we display our own custom error message, there is no way
        // to display the original stack trace.
        $debuginfo = '';
        if (!empty($e->debuginfo)) {
            $debuginfo = $e->debuginfo;
        }
        print_error('errorprocessingresponses', 'question', $actionurl,
                $e->getMessage(), $debuginfo);
    }
}

if ($question->length) {
    $displaynumber = '1';
} else {
    $displaynumber = 'i';
}

if ($quba->get_question_state($slot)->is_finished()) {
    $options->extrainfocontent = html_writer::div(html_writer::empty_tag('input', ['type' => 'submit',
            'name' => 'restart', 'value' => get_string('restart', 'filter_embedquestion'),
            'class' => 'btn btn-secondary']));
}

// Log the view.
\filter_embedquestion\event\question_viewed::create(
        ['context' => $context, 'objectid' => $question->id])->trigger();

// Start output.
$title = get_string('iframetitle', 'filter_embedquestion');
question_engine::initialise_js();
$quba->render_question_head_html($slot);
$PAGE->set_title($title);
$PAGE->set_heading($title);
echo $OUTPUT->header();

// Start the question form.
echo html_writer::start_tag('form', array('method' => 'post', 'action' => $actionurl,
        'enctype' => 'multipart/form-data', 'id' => 'responseform'));
echo html_writer::start_tag('div');
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'slots', 'value' => $slot));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'scrollpos', 'value' => '', 'id' => 'scrollpos'));
echo html_writer::end_tag('div');

// Output the question.
echo $quba->render_question($slot, $options, $displaynumber);

// Finish the question form.
echo html_writer::end_tag('form');

$PAGE->requires->js_module('core_question_engine');
$PAGE->requires->js_call_amd('filter_embedquestion/question', 'init');
echo $OUTPUT->footer();
