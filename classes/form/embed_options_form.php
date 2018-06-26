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
 * Form to let users edit all the options for embedding a question.
 *
 * @package   filter_embedquestion
 * @category  form
 * @copyright 2018 The Open University - based on question/preview.php
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace filter_embedquestion\form;
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
use filter_embedquestion\utils;
use filter_embedquestion\question_options;


class embed_options_form extends \moodleform {
    public function definition() {
        global $USER;

        $mform = $this->_form;
        $context = $this->_customdata['context'];

        if (has_capability('moodle/question:useall', $context)) {
            $userlimit = null;
        } else if (has_capability('moodle/question:usemine', $context)) {
            $userlimit = $USER->id;
        } else {
            throw new \coding_exception('This user is not allowed to embed questions.');
        }

        $defaultoptions = new question_options($context->instanceid);

        $mform->addElement('hidden', 'courseid', $context->instanceid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('header', 'questionheader', get_string('whichquestion', 'filter_embedquestion'));

        $mform->addElement('select', 'categoryidnumber', get_string('questioncategory', 'question'),
                \filter_embedquestion\utils::get_categories_with_sharable_question_choices($context, $userlimit));
        $mform->addRule('categoryidnumber', null, 'required', null, 'client');

        $mform->addElement('text', 'questionidnumber', get_string('questionidnumber', 'filter_embedquestion'));
        $mform->setType('questionidnumber', PARAM_RAW_TRIMMED);
        $mform->addRule('questionidnumber', null, 'required', null, 'client');

        $mform->addElement('text', 'variant', get_string('questionvariant', 'question'));
        $mform->setType('variant', PARAM_RAW_TRIMMED); // Not PARAM_INT because we need to keep blank input as ''.

        $mform->addElement('header', 'attemptheader', get_string('attemptoptions', 'filter_embedquestion'));

        $behaviours = ['' => get_string('defaultx', 'filter_embedquestion',
                        \question_engine::get_behaviour_name($defaultoptions->behaviour))] +
                utils::behaviour_choices();
        $mform->addElement('select', 'behaviour', get_string('howquestionbehaves', 'filter_embedquestion'),
                $behaviours);

        $mform->addElement('text', 'maxmark', get_string('markedoutof', 'filter_embedquestion'), ['size' => 7]);
        $mform->setType('maxmark', PARAM_RAW_TRIMMED); // Not PARAM_FLOAT because we need to keep blank input as ''.

        $mform->addElement('header', 'reviewheader', get_string('displayoptions', 'filter_embedquestion'));

        $mform->addElement('select', 'correctness', get_string('whethercorrect', 'question'),
                $this->get_show_hide_options($defaultoptions->correctness));

        $mform->addElement('select', 'marks', get_string('marks', 'question'),
                $this->get_marks_options($defaultoptions->marks));

        $options = array_merge(
                ['' => get_string('defaultx', 'filter_embedquestion', $defaultoptions->markdp)],
                \question_engine::get_dp_options());
        $mform->addElement('select', 'markdp',
                get_string('decimalplacesingrades', 'question'), $options);

        $mform->addElement('select', 'feedback', get_string('specificfeedback', 'question'),
                $this->get_show_hide_options($defaultoptions->feedback));

        $mform->addElement('select', 'generalfeedback', get_string('generalfeedback', 'question'),
                $this->get_show_hide_options($defaultoptions->generalfeedback));

        $mform->addElement('select', 'rightanswer', get_string('rightanswer', 'question'),
                $this->get_show_hide_options($defaultoptions->rightanswer));

        $mform->addElement('select', 'history', get_string('responsehistory', 'question'),
                $this->get_show_hide_options($defaultoptions->history));

        $this->add_action_buttons(false, get_string('embedquestion', 'filter_embedquestion'));
    }

    /**
     * Get the options for a show/hide setting.
     *
     * @param int $default the default if a value is not set here.
     * @return array the options for the form.
     */
    protected function get_show_hide_options($default) {
        $options = [
            \question_display_options::HIDDEN => get_string('notshown', 'question'),
            \question_display_options::VISIBLE => get_string('shown', 'question'),
        ];

        return ['' => get_string('defaultx', 'filter_embedquestion', $options[$default])] + $options;
    }

    /**
     * Get the options for the marks field.
     *
     * @param int $default the default if a value is not set here.
     * @return array the options for the form.
     */
    protected function get_marks_options($default) {
        $options = [
                \question_display_options::HIDDEN => get_string('notshown', 'question'),
                \question_display_options::MAX_ONLY => get_string('showmaxmarkonly', 'question'),
                \question_display_options::MARK_AND_MAX => get_string('showmarkandmax', 'question'),
        ];

        return ['' => get_string('defaultx', 'filter_embedquestion', $options[$default])] + $options;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $context = $this->_customdata['context'];

        $category = \filter_embedquestion\utils::get_category_by_idnumber($context, $data['categoryidnumber']);

        $questiondata = \filter_embedquestion\utils::get_question_by_idnumber($category->id, $data['questionidnumber']);
        if (!$questiondata) {
            $errors['questionidnumber'] = get_string('errorunknownquestion', 'filter_embedquestion');
        } else if (!question_has_capability_on($questiondata, 'use')) {
            $errors['questionidnumber'] = get_string('errornopermissions', 'filter_embedquestion');
        }

        if ($data['variant'] !== '') {
            $variant = clean_param($data['variant'], PARAM_INT);
            if ($questiondata) {
                $question = \question_bank::load_question($questiondata->id);
                $maxvariant = $question->get_num_variants();
                if ($data['variant'] !== (string) $variant || $variant < 1 || $variant > $maxvariant) {
                    $errors['variant'] = get_string('errorvariantoutofrange', 'filter_embedquestion', $maxvariant);
                }
            } else {
                if ($data['variant'] !== (string) $variant || $variant < 1) {
                    $errors['variant'] = get_string('errorvariantformat', 'filter_embedquestion');
                }
            }
        }

        if ($data['maxmark'] !== '') {
            $maxmark = unformat_float($data['maxmark']);
            if ($maxmark === '.' || !preg_match('~^\d*([\.,]\d*)?$~', $data['maxmark'])) {
                $errors['maxmark'] = get_string('errormaxmarknumber', 'filter_embedquestion');
            }
        }

        return $errors;
    }

    public function get_data() {
        $data = parent::get_data();

        if ($data && $data->maxmark !== '') {
            $data->maxmark = (float) str_replace(',', '.', $data->maxmark);
        }

        return $data;
    }
}
