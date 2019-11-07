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

use filter_embedquestion\embed_id;
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
                ['name' => 'Category with idnumber',
                        'contextid' => context_course::instance($course->id)->id, 'idnumber' => 'abc123']);

        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 2', 'idnumber' => 'toad']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 1', 'idnumber' => 'frog']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id]);

        $this->assertEquals([
                ['value' => '', 'label' => 'Choose...'],
                ['value' => 'frog', 'label' => 'Question 1 [frog]'],
                ['value' => 'toad', 'label' => 'Question 2 [toad]'],
                ['value' => '*', 'label' => get_string('chooserandomly', 'filter_embedquestion')]],
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
                ['name' => 'Category with idnumber', 'idnumber' => 'abc123',
                        'contextid' => context_course::instance($course->id)->id]);

        $this->setAdminUser();
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 2', 'idnumber' => 'toad']);
        $this->setUser($user);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 1', 'idnumber' => 'frog']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id]);

        $this->assertEquals([
                ['value' => '', 'label' => 'Choose...'],
                ['value' => 'frog', 'label' => 'Question 1 [frog]']],
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
                ['name' => 'Category', 'idnumber' => 'abc123',
                        'contextid' => context_course::instance($course->id)->id]);

        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question', 'idnumber' => 'toad']);

        $embedid = new embed_id('abc123', 'toad');
        $behaviour = '';
        $maxmark = '';
        $variant = '';
        $correctness = '';
        $marks = '';
        $markdp = '';
        $feedback = '';
        $generalfeedback = '';
        $rightanswer = '';
        $history = '';

        $token = token::make_secret_token($embedid);
        $expected = '{Q{' . $embedid . '|' . $token . '}Q}';
        $actual = external::get_embed_code($course->id, $embedid->categoryidnumber,
                $embedid->questionidnumber, $behaviour,
                $maxmark, $variant, $correctness, $marks, $markdp, $feedback, $generalfeedback, $rightanswer, $history);

        $this->assertEquals($expected, $actual);

        $behaviour = 'immediatefeedback';
        $expected = '{Q{' . $embedid . '|behaviour=' . $behaviour . '|' . $token . '}Q}';
        $actual = external::get_embed_code($course->id, $embedid->categoryidnumber,
                $embedid->questionidnumber, $behaviour,
                $maxmark, $variant, $correctness, $marks, $markdp, $feedback, $generalfeedback, $rightanswer, $history);

        $this->assertEquals($expected, $actual);
    }

    public function test_get_embed_code_working_with_random_questions() {

        $this->resetAfterTest();

        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category(
                ['name' => 'Category', 'idnumber' => 'abc123',
                        'contextid' => context_course::instance($course->id)->id]);

        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question1', 'idnumber' => 'toad']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question2', 'idnumber' => 'frog']);

        $embedid = new embed_id('abc123', 'toad');
        $behaviour = '';
        $maxmark = '';
        $variant = '';
        $correctness = '';
        $marks = '';
        $markdp = '';
        $feedback = '';
        $generalfeedback = '';
        $rightanswer = '';
        $history = '';

        $token = token::make_secret_token($embedid);
        $expected = '{Q{' . $embedid . '|' . $token .'}Q}';
        $actual = external::get_embed_code($course->id, $embedid->categoryidnumber,
                $embedid->questionidnumber, $behaviour,
                $maxmark, $variant, $correctness, $marks, $markdp, $feedback, $generalfeedback, $rightanswer, $history);
        $this->assertEquals($expected, $actual);

        $embedid = new embed_id('abc123', 'frog');
        $token = token::make_secret_token($embedid);
        $expected = '{Q{' . $embedid . '|' . $token .'}Q}';
        $actual = external::get_embed_code($course->id, $embedid->categoryidnumber,
                $embedid->questionidnumber, $behaviour,
                $maxmark, $variant, $correctness, $marks, $markdp, $feedback, $generalfeedback, $rightanswer, $history);
        $this->assertEquals($expected, $actual);

        // Accept '*' for $questionidnumber to indicate a random question.
        $embedid = new embed_id('abc123', '*');
        $token = token::make_secret_token($embedid);
        $expected = '{Q{' . $embedid . '|' . $token .'}Q}';
        $actual = external::get_embed_code($course->id, $embedid->categoryidnumber,
                $embedid->questionidnumber, $behaviour,
                $maxmark, $variant, $correctness, $marks, $markdp, $feedback, $generalfeedback, $rightanswer, $history);
        $this->assertEquals($expected, $actual);

        $behaviour = 'immediatefeedback';
        $expected = '{Q{' . $embedid . '|behaviour=' . $behaviour . '|' . $token . '}Q}';
        $actual = external::get_embed_code($course->id, $embedid->categoryidnumber,
                $embedid->questionidnumber, $behaviour,
                $maxmark, $variant, $correctness, $marks, $markdp, $feedback, $generalfeedback, $rightanswer, $history);
        $this->assertEquals($expected, $actual);
    }
}
