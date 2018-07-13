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
 * Unit test for the external functions.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use filter_embedquestion\external;
use filter_embedquestion\token;


/**
 * Unit tests for the external functions.
 *
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_embedquestion_external_testcase extends advanced_testcase {

    public function test_get_sharable_question_choices_working() {

        $this->resetAfterTest();

        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber [ID:abc123]',
                        'contextid' => context_course::instance($course->id)->id]);

        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 2 [ID:toad]']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 1 [ID:frog]']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id]);

        $this->assertEquals([
                ['value' => '', 'label' => 'Choose...'],
                ['value' => 'frog', 'label' => 'Question 1 [ID:frog]'],
                ['value' => 'toad', 'label' => 'Question 2 [ID:toad]']],
                external::get_sharable_question_choices($course->id, 'abc123'));
    }

    public function test_get_sharable_question_choices_no_permissions() {
        $this->resetAfterTest();
        $this->setGuestUser();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('This user is not allowed to embed questions.');
        external::get_sharable_question_choices(SITEID, 'abc123');
    }

    public function test_get_sharable_question_choices_only_user() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        role_change_permission($DB->get_field('role', 'id', ['shortname' => 'editingteacher']),
                context_system::instance(), 'moodle/question:useall', CAP_INHERIT);
        $generator->enrol_user($user->id, $course->id, 'editingteacher');

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber [ID:abc123]',
                        'contextid' => context_course::instance($course->id)->id]);

        $this->setAdminUser();
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 2 [ID:toad]']);
        $this->setUser($user);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 1 [ID:frog]']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id]);

        $this->assertEquals([
                ['value' => '', 'label' => 'Choose...'],
                ['value' => 'frog', 'label' => 'Question 1 [ID:frog]']],
                external::get_sharable_question_choices($course->id, 'abc123'));
    }

    public function test_get_embed_code_working() {

        $this->resetAfterTest();

        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber [ID:abc123]',
                        'contextid' => context_course::instance($course->id)->id]);

        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 2 [ID:toad]']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 1 [ID:frog]']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id]);

        $categoryidnumber = 'abc123';
        $questionidnumber = 'toad';
        $behaviour = '';
        $maxmark = '';
        $variant = '';
        $correctness = '';
        $marks = '';
        $markdp = null;
        $feedback = '';
        $generalfeedback = '';
        $rightanswer = '';
        $history = '';

        $token = token::make_secret_token($categoryidnumber, $questionidnumber);
        $expected = '{Q{' . $categoryidnumber . '/' . $questionidnumber . '|' . $token .'}Q}';
        $actual = external::get_embed_code($course->id, $categoryidnumber, $questionidnumber, $behaviour,
                $maxmark, $variant, $correctness, $marks, $markdp, $feedback, $generalfeedback, $rightanswer, $history);

        $this->assertEquals($expected, $actual);

        $behaviour = 'immediatefeedback';
        $expected = '{Q{' . $categoryidnumber . '/' . $questionidnumber . '|behaviour=' . $behaviour .'|' . $token .'}Q}';
        $actual = external::get_embed_code($course->id, $categoryidnumber, $questionidnumber, $behaviour,
                $maxmark, $variant, $correctness, $marks, $markdp, $feedback, $generalfeedback, $rightanswer, $history);

        $this->assertEquals($expected, $actual);
    }
}
