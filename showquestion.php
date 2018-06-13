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
require_once($CFG->dirroot . '/question/previewlib.php');

// Process required parameters.
$id = required_param('id', PARAM_INT);
$courseid = required_param('course', PARAM_INT);
$token = required_param('token', PARAM_RAW);
$behaviour = optional_param('behaviour', 'interactive', PARAM_COMPONENT);

$PAGE->set_pagelayout('popup');
require_login($courseid);
if (isguestuser()) {
    print_error('noguests', 'filter_embedquestion');
}
$context = context_course::instance($courseid);
$question = question_bank::load_question($id);
if ($token !== filter_embedquestion\token::make_iframe_token($question->id)) {
    print_error('invalidtoken', 'filter_embedquestion');
}

// Process options.
$options = new filter_embedquestion\question_options($question, $courseid, $behaviour);
$options->set_from_request();
$PAGE->set_url($options->get_page_url($question->id));

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
}
$options->behaviour = $quba->get_preferred_behaviour();
$options->maxmark = $quba->get_question_max_mark($slot);

// Prepare a URL that is used in various places.
$actionurl = $options->get_action_url($quba, $slot);

// Process any actions from the buttons at the bottom of the form.
if (data_submitted() && confirm_sesskey()) {

    try {

        if (optional_param('restart', false, PARAM_BOOL)) {
            // TODO
            restart_preview($qubaid, $question->id, $options, $context);

        } else {
            $quba->process_all_actions();

            $transaction = $DB->start_delegated_transaction();
            question_engine::save_questions_usage_by_activity($quba);
            $transaction->allow_commit();

            $scrollpos = optional_param('scrollpos', '', PARAM_RAW);
            if ($scrollpos !== '') {
                $actionurl->param('scrollpos', (int) $scrollpos);
            }
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
$PAGE->requires->strings_for_js(array(
    'closepreview',
), 'question');
$PAGE->requires->yui_module('moodle-question-preview', 'M.question.preview.init');
echo $OUTPUT->footer();

