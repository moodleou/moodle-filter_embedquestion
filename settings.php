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
 * Admin settings for filter_embedquestion.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    require_once($CFG->libdir . '/questionlib.php');

    $hiddenorvisible = array(
            question_display_options::HIDDEN => get_string('notshown', 'question'),
            question_display_options::VISIBLE => get_string('shown', 'question'),
    );

    $marksoptions = array(
            question_display_options::HIDDEN => get_string('notshown', 'question'),
            question_display_options::MAX_ONLY => get_string('showmaxmarkonly', 'question'),
            question_display_options::MARK_AND_MAX => get_string('showmarkandmax', 'question'),
    );

    // Intro text.
    $settings->add(new admin_setting_heading('filter_embedquestion/defaultinfo',
            get_string('defaultsheading', 'filter_embedquestion'),
            get_string('defaultsheading_desc', 'filter_embedquestion')));

    // Behaviour.
    $settings->add(new filter_embedquestion\admin\question_behaviour_setting(
            'filter_embedquestion/behaviour', get_string('howquestionbehaves', 'filter_embedquestion'),
            get_string('howquestionbehaves_desc', 'filter_embedquestion'), 'interactive', null));

    // Correctness.
    $settings->add(new admin_setting_configselect('filter_embedquestion/correctness',
            get_string('whethercorrect', 'question'),
            get_string('whethercorrect_desc', 'filter_embedquestion'),
            1, $hiddenorvisible));

    // Show marks.
    $settings->add(new admin_setting_configselect('filter_embedquestion/marks',
            get_string('marks', 'question'),
            get_string('marks_desc', 'filter_embedquestion'), 2, $marksoptions));

    // Decimal places in grades.
    $settings->add(new admin_setting_configselect('filter_embedquestion/markdp',
            get_string('decimalplaces', 'quiz'), get_string('markdp_desc', 'filter_embedquestion'),
            2, question_engine::get_dp_options()));

    // Specific feedback.
    $settings->add(new admin_setting_configselect('filter_embedquestion/feedback',
            get_string('specificfeedback', 'question'),
            get_string('specificfeedback_desc', 'filter_embedquestion'),
            1, $hiddenorvisible));

    // General feedback.
    $settings->add(new admin_setting_configselect('filter_embedquestion/generalfeedback',
            get_string('generalfeedback', 'question'),
            get_string('generalfeedback_desc', 'filter_embedquestion'),
            1, $hiddenorvisible));

    // Right answer.
    $settings->add(new admin_setting_configselect('filter_embedquestion/rightanswer',
            get_string('rightanswer', 'question'),
            get_string('rightanswer_desc', 'filter_embedquestion'),
            0, $hiddenorvisible));

    // Response history.
    $settings->add(new admin_setting_configselect('filter_embedquestion/history',
            get_string('responsehistory', 'question'),
            get_string('responsehistory_desc', 'filter_embedquestion'),
            0, $hiddenorvisible));
}
