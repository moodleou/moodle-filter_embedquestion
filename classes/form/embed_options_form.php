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
require_once($CFG->libdir . '/modinfolib.php');

use filter_embedquestion\utils;
use filter_embedquestion\question_options;
use cm_info;

/**
 * Form to let users edit all the options for embedding a question.
 *
 * @copyright 2018 The Open University - based on question/preview.php
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class embed_options_form extends \moodleform {
    #[\Override]
    public function definition() {
        global $PAGE, $OUTPUT;

        $mform = $this->_form;
        /** @var \context $context */
        $context = $this->_customdata['context'];
        $courseshortname = $this->_customdata['courseshortname'] ?? null;
        $defaultqbankcmid = $this->_customdata['qbankcmid'] ?? null;
        $embedcode = $this->_customdata['embedcode'] ?? null;

        // The default form id ('mform1') is also highly likely to be the same as the
        // id of the form in the background when we are shown in an atto editor pop-up.
        // Therefore, set something different.
        $mform->updateAttributes(['id' => 'embedqform']);

        $defaultoptions = new question_options();
        $mform->addElement('hidden', 'contextid', $context->id);
        $mform->setType('contextid', PARAM_INT);
        $mform->addElement('hidden', 'courseid', $context->instanceid);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'issamecourse', 1);
        $mform->setType('issamecourse', PARAM_INT);
        $mform->addElement('header', 'questionheader', get_string('whichquestion', 'filter_embedquestion'));

        $prefs = [];

        // Only load user preference if we do not have a default question bank cmid or embed code.
        if (!$defaultqbankcmid && !$embedcode) {
            // Retrieve existing preference (empty array if none).
            $prefs = json_decode(get_user_preferences('filter_embedquestion_userdefaultqbank', '{}'));
        }
        $cmid = !empty($defaultqbankcmid) ? $defaultqbankcmid : ($prefs->{$context->instanceid} ?? null);
        // If we have default question bank cmid, we will use it to get the course id.
        if ($cmid) {
            [, $cm] = get_course_and_cm_from_cmid($cmid);
            $cminfo = cm_info::create($cm);
            $courseid = $cminfo->get_course()->id;
        } else if ($courseshortname) {
            $courseid = utils::get_courseid_by_course_shortname($courseshortname);
            if ($courseid != $context->instanceid) {
                $mform->setDefault('issamecourse', 0);
            }
        } else {
            $courseid = $context->instanceid;
        }
        $qbanks = utils::get_shareable_question_banks($courseid, $this->get_user_retriction());
        $qbanksselectoptions = utils::create_select_qbank_choices($qbanks);
        // If we have a default question bank cmid, we will use it to set the default value.
        // If the default question bank cmid is not in the list of question banks, we will add it.
        if ($cmid && empty($qbanksselectoptions[$cmid])) {
            $qbanksselectoptions[$cmid] = format_string($cminfo->name);
        }
        $mform->addElement(
            'html'
        );
        $mform->addElement(
            'select',
            'qbankcmid',
            get_string('questionbank', 'question'),
            $qbanksselectoptions
        );
        $mform->addRule(
            'qbankcmid',
            null,
            'required',
            null,
            'client'
        );

        $mform->addElement(
            'select',
            'categoryidnumber',
            get_string('questioncategory', 'question'),
            []
        );
        $mform->addRule(
            'categoryidnumber',
            null,
            'required',
            null,
            'client'
        );
        $mform->disabledIf(
            'questionidnumber',
            'qbankcmid',
            'eq',
            ''
        );
        $mform->addElement(
            'select',
            'questionidnumber',
            get_string('question'),
            []
        );
        $mform->addRule(
            'questionidnumber',
            null,
            'required',
            null,
            'client'
        );
        $mform->disabledIf(
            'questionidnumber',
            'categoryidnumber',
            'eq',
            ''
        );
        $PAGE->requires->js_call_amd(
            'filter_embedquestion/questionid_choice_updater',
            'init',
            [$cmid]
        );

        $mform->addElement(
            'text',
            'iframedescription',
            get_string('iframedescription', 'filter_embedquestion'),
            ['size' => 100]
        );
        $mform->setType(
            'iframedescription',
            PARAM_TEXT
        );
        $mform->addRule(
            'iframedescription',
            get_string('iframedescriptionmaxlengthwarning', 'filter_embedquestion'),
            'maxlength',
            100,
            'client'
        );
        $mform->addRule(
            'iframedescription',
            get_string('iframedescriptionminlengthwarning', 'filter_embedquestion'),
            'minlength',
            3,
            'client'
        );
        $mform->addHelpButton(
            'iframedescription',
            'iframedescription',
            'filter_embedquestion'
        );

        $mform->addElement(
            'header',
            'attemptheader',
            get_string('attemptoptions', 'filter_embedquestion')
        );

        $behaviours = [
            '' => get_string(
                'defaultx',
                'filter_embedquestion',
                \question_engine::get_behaviour_name($defaultoptions->behaviour)
            ),
        ] + utils::behaviour_choices();
        $mform->addElement(
            'select',
            'behaviour',
            get_string('howquestionbehaves', 'filter_embedquestion'),
            $behaviours
        );

        $mform->addElement(
            'text',
            'maxmark',
            get_string('markedoutof', 'filter_embedquestion'),
            ['size' => 7]
        );
        $mform->setType(
            'maxmark',
            PARAM_RAW_TRIMMED
        ); // Not PARAM_FLOAT because we need to keep blank input as ''.

        $mform->addElement(
            'text',
            'variant',
            get_string('questionvariant', 'question')
        );
        $mform->setType(
            'variant',
            PARAM_RAW_TRIMMED
        ); // Not PARAM_INT because we need to keep blank input as ''.
        $mform->disabledIf(
            'variant',
            'questionidnumber',
            'eq',
            ''
        );

        $mform->addElement(
            'header',
            'reviewheader',
            get_string('displayoptions', 'filter_embedquestion')
        );

        $mform->addElement(
            'select',
            'correctness',
            get_string('whethercorrect', 'question'),
            $this->get_show_hide_options($defaultoptions->correctness)
        );

        $mform->addElement(
            'select',
            'marks',
            get_string('marks', 'question'),
            $this->get_marks_options($defaultoptions->marks)
        );

        $options = array_merge(
            ['' => get_string('defaultx', 'filter_embedquestion', $defaultoptions->markdp)],
            \question_engine::get_dp_options()
        );
        $mform->addElement(
            'select',
            'markdp',
            get_string('decimalplacesingrades', 'question'),
            $options
        );

        $mform->addElement(
            'select',
            'feedback',
            get_string('specificfeedback', 'question'),
            $this->get_show_hide_options($defaultoptions->feedback)
        );

        $mform->addElement(
            'select',
            'generalfeedback',
            get_string('generalfeedback', 'question'),
            $this->get_show_hide_options($defaultoptions->generalfeedback)
        );

        $mform->addElement(
            'select',
            'rightanswer',
            get_string('rightanswer', 'question'),
            $this->get_show_hide_options($defaultoptions->rightanswer)
        );

        $mform->addElement(
            'select',
            'history',
            get_string('responsehistory', 'question'),
            $this->get_show_hide_options($defaultoptions->history)
        );

        $languages = utils::get_installed_language_choices();
        if ($languages) {
            $mform->addElement(
                'select',
                'forcedlanguage',
                get_string('forcelanguage'),
                $languages
            );
        }

        $this->add_action_buttons(
            false,
            get_string('embedquestion', 'filter_embedquestion')
        );
        $mform->disabledIf(
            'submitbutton',
            'questionidnumber',
            'eq',
            ''
        );
    }

    /**
     * Get the options for a show/hide setting.
     *
     * @param int $default the default if a value is not set here.
     * @return array the options for the form.
     */
    protected function get_show_hide_options(int $default): array {
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
    protected function get_marks_options(int $default): array {
        $options = [
                \question_display_options::HIDDEN => get_string('notshown', 'question'),
                \question_display_options::MAX_ONLY => get_string('showmaxmarkonly', 'question'),
                \question_display_options::MARK_AND_MAX => get_string('showmarkandmax', 'question'),
        ];

        return ['' => get_string('defaultx', 'filter_embedquestion', $options[$default])] + $options;
    }

    #[\Override]
    public function definition_after_data() {
        parent::definition_after_data();
        $mform = $this->_form;
        $qbankcmid = $mform->getElementValue('qbankcmid');
        if (is_null($qbankcmid)) {
            return;
        }

        $categoryidnumbers = $mform->getElementValue('categoryidnumber');
        if (is_null($categoryidnumbers)) {
            return;
        }
        $qbankcmid = $qbankcmid[0];
        if (!$qbankcmid || $qbankcmid == -1) {
            return;
        }
        $categoryidnumber = $categoryidnumbers[0];
        if ($categoryidnumber === '' || $categoryidnumber === null) {
            return;
        }

        [$course, ] = get_course_and_cm_from_cmid($qbankcmid);
        $qbanks = utils::get_shareable_question_banks($course->id, $this->get_user_retriction());
        $qbanksselectoptions = utils::create_select_qbank_choices($qbanks);
        $element = $mform->getElement('qbankcmid');
        // Clear the existing options, so that we can load the new ones.
        $element->_options = [];
        $mform->getElement('qbankcmid')->loadArray($qbanksselectoptions);
        $context = \context_module::instance($qbankcmid);
        $mform->setDefault('qbankcmid', $qbankcmid);

        $categories = utils::get_categories_with_sharable_question_choices(
            $context,
            $this->get_user_retriction()
        );
        $mform->getElement('categoryidnumber')->loadArray($categories);
        $category = utils::get_category_by_idnumber($context, $categoryidnumber);
        if ($category) {
            $choices = utils::get_sharable_question_choices($category->id, $this->get_user_retriction());
            $mform->getElement('questionidnumber')->loadArray($choices);
        }
    }

    /**
     * If the current user can use any question, return null, else return their user id.
     *
     * Foru use with utils methods like get_sharable_question_choices.
     *
     * @return int|null the $userlimit option.
     */
    protected function get_user_retriction(): ?int {
        global $USER;

        $context = $this->_customdata['context'];

        if (has_capability('moodle/question:useall', $context)) {
            return null;
        } else if (has_capability('moodle/question:usemine', $context)) {
            return $USER->id;
        } else {
            throw new \coding_exception('This user is not allowed to embed questions.');
        }
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $qbankcontext = \context_module::instance($data['qbankcmid']);
        if (!$qbankcontext) {
            $errors['qbankcmid'] = get_string('errorquestionbanknotfound', 'filter_embedquestion');
            return $errors;
        }
        if (!utils::has_permission($qbankcontext) || !get_coursemodule_from_id('qbank', $data['qbankcmid'])) {
            $errors['qbankcmid'] = get_string('errornopermissions', 'filter_embedquestion');
            return $errors;
        }

        $category = utils::get_category_by_idnumber($qbankcontext, $data['categoryidnumber']);
        $questiondata = false;
        if (isset($data['questionidnumber'])) {
            $questiondata = utils::get_question_by_idnumber($category->id, $data['questionidnumber']);
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
            if ($maxmark === '.' || !preg_match('~^\d*([.,]\d*)?$~', $data['maxmark'])) {
                $errors['maxmark'] = get_string('errormaxmarknumber', 'filter_embedquestion');
            }
        }

        return $errors;
    }

    #[\Override]
    public function get_data() {
        $data = parent::get_data();

        if ($data && $data->maxmark !== '') {
            $data->maxmark = (float) str_replace(',', '.', $data->maxmark);
        }

        return $data;
    }
}
