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
 * Script to help developers.
 *
 * Generates the necessary embed code and show question url.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University - based on question/preview.php
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/questionlib.php');
use filter_embedquestion\question_options;
use filter_embedquestion\utils;
use filter_embedquestion\form\embed_options_form;

$courseid = required_param('courseid', PARAM_INT);

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
if (!has_capability('moodle/question:useall', $context)) {
    require_capability('moodle/question:usemine', $context);
}

$PAGE->set_url('/filter/embedquestion/testhelper.php', ['courseid' => $courseid]);
$PAGE->set_heading('Embed question filter test helper script');
$PAGE->set_title('Embed question filter test helper script');

$form = new embed_options_form(null, ['context' => $context]);

echo $OUTPUT->header();

utils::warn_if_filter_disabled($context);

if ($fromform = $form->get_data()) {
    $category = utils::get_category_by_idnumber($context, $fromform->categoryidnumber);

    if ($fromform->questionidnumber === '*') {
        echo $OUTPUT->heading('Information for embedding question selected randomly from ' . format_string($category->name));

        \filter_embedquestion\event\category_token_created::create(
                ['context' => $context, 'objectid' => $category->id])->trigger();

    } else {
        $questiondata = utils::get_question_by_idnumber($category->id, $fromform->questionidnumber);
        $question = question_bank::load_question($questiondata->id);
        echo $OUTPUT->heading('Information for embedding question ' . format_string($question->name));

        // Log this.
        \filter_embedquestion\event\token_created::create(
                ['context' => $context, 'objectid' => $question->id])->trigger();
    }

    $embedcode = question_options::get_embed_from_form_options($fromform);
    echo html_writer::tag('p', 'Code to embed the question: ' . $embedcode);

    echo format_text('The embedded question: ' . $embedcode, FORMAT_HTML, ['context' => $context]);
}

echo $OUTPUT->heading('Generate the code to embed a question');
echo $form->render();

echo $OUTPUT->footer();

