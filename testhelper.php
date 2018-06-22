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
require_once($CFG->libdir . '/formslib.php');

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

class filter_embedquestion_test_form extends moodleform {
    public function definition() {
        global $USER;

        $mform = $this->_form;
        $context = $this->_customdata['context'];

        if (has_capability('moodle/question:useall', $context)) {
            $userlimit = null;
        } else if (has_capability('moodle/question:usemine', $context)) {
            $userlimit = $USER->id;
        } else {
            throw new coding_exception('This user is not allowed to embed questions.');
        }

        $mform->addElement('hidden', 'courseid', $context->instanceid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('header', 'questionheader', 'Select question');

        $mform->addElement('select', 'categoryidnumber', 'Category',
                \filter_embedquestion\utils::get_categories_with_sharable_question_choices($context, $userlimit));
        $mform->addRule('categoryidnumber', null, 'required', null, 'client');

        $mform->addElement('text', 'questionidnumber', 'Question idnumber');
        $mform->setType('questionidnumber', PARAM_RAW);
        $mform->addRule('questionidnumber', null, 'required', null, 'client');

        $mform->addElement('text', 'questionvariant', 'Question variant');
        $mform->setType('questionvariant', PARAM_INT);

        $mform->addElement('header', 'attemptheader', 'Attempt options');

        $allbehaviours = question_engine::get_archetypal_behaviours();
        $behaviours = ['' => 'Default'];
        foreach ($allbehaviours as $behaviour => $name) {
            if (question_engine::can_questions_finish_during_the_attempt($behaviour)) {
                $behaviours[$behaviour] = $name;
            }
        }
        $mform->addElement('select', 'behaviour', 'Behaviour', $behaviours);

        // Question max mark.
        $mform->addElement('text', 'maxmark', 'Marked out of', ['size' => 7]);
        $mform->setType('maxmark', PARAM_FLOAT);

        // Decimal places to display.
        $options = array_merge([-1 => 'Default'], question_engine::get_dp_options());
        $mform->addElement('select', 'markdp',
                get_string('decimalplacesingrades', 'question'), $options);

        $mform->addElement('header', 'reviewheader', 'Review options');

        $hiddenorvisible = array(
                -1 => 'Default',
                question_display_options::HIDDEN => get_string('notshown', 'question'),
                question_display_options::VISIBLE => get_string('shown', 'question'),
        );

        $mform->addElement('select', 'correctness', get_string('whethercorrect', 'question'),
                $hiddenorvisible);

        $marksoptions = array(
                -1 => 'Default',
                question_display_options::HIDDEN => get_string('notshown', 'question'),
                question_display_options::MAX_ONLY => get_string('showmaxmarkonly', 'question'),
                question_display_options::MARK_AND_MAX => get_string('showmarkandmax', 'question'),
        );
        $mform->addElement('select', 'marks', get_string('marks', 'question'), $marksoptions);

        $mform->addElement('select', 'feedback',
                get_string('specificfeedback', 'question'), $hiddenorvisible);

        $mform->addElement('select', 'generalfeedback',
                get_string('generalfeedback', 'question'), $hiddenorvisible);

        $mform->addElement('select', 'rightanswer',
                get_string('rightanswer', 'question'), $hiddenorvisible);

        $mform->addElement('select', 'history',
                get_string('responsehistory', 'question'), $hiddenorvisible);

        $this->add_action_buttons(false, 'Generate information');
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $context = $this->_customdata['context'];

        $category = \filter_embedquestion\utils::get_category_by_idnumber($context, $data['categoryidnumber']);

        $question = \filter_embedquestion\utils::get_question_by_idnumber($category->id, $data['questionidnumber']);
        if (!$question) {
            $errors['questionidnumber'] = 'Unknown, or unsharable question.';
        }

        return $errors;
    }
}

$form = new filter_embedquestion_test_form(null, ['context' => $context]);

echo $OUTPUT->header();

if ($fromform = $form->get_data()) {
    $question = question_bank::load_question($fromform->id);
    $context = context::instance_by_id($question->contextid);

    $options = new filter_embedquestion\question_options($question,
            $context->get_course_context()->instanceid, $fromform->behaviour);

    echo $OUTPUT->heading('Information for embedding question ' . format_string($question->name));

    $iframeurl = $options->get_page_url($question->id);
    echo html_writer::tag('p', 'Link to show the question in the iframe: ' .
            html_writer::link($iframeurl, $iframeurl));

    echo html_writer::tag('p', 'Code to embed the question: TODO');
}

echo $OUTPUT->heading('Generate code an links for embedding a question.');
echo $form->render();

echo $OUTPUT->footer();

