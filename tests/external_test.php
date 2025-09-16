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

namespace filter_embedquestion;

/**
 * Unit tests for the external functions.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \filter_embedquestion\external
 * @runTestsInSeparateProcesses
 */
final class external_test extends \advanced_testcase {

    public function setUp(): void {
        global $SCRIPT;
        parent::setUp();
        // With @runTestsInSeparateProcesses, if you have auth_saml installed
        // then it gives an error about $SCRIPT being null.
        $SCRIPT = '';
    }

    public function test_get_sharable_question_choices_working(): void {

        $this->resetAfterTest();

        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $qbank = $this->getDataGenerator()->create_module('qbank', ['course' => $course->id, 'idnumber' => 'abc123']);
        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category([
                'name' => 'Category with idnumber',
                'contextid' => \context_module::instance($qbank->cmid)->id,
                'idnumber' => 'abc123',
            ]);

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
                ['value' => '*', 'label' => get_string('chooserandomly', 'filter_embedquestion')],
            ],
            external::get_sharable_question_choices($qbank->cmid, 'abc123'));
    }

    public function test_get_sharable_question_choices_no_permissions(): void {
        global $DB;
        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('This user is not allowed to embed questions.');
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        role_change_permission($DB->get_field('role', 'id', ['shortname' => 'editingteacher']),
            \context_system::instance(), 'moodle/question:useall', CAP_PREVENT);
        role_change_permission($DB->get_field('role', 'id', ['shortname' => 'editingteacher']),
            \context_system::instance(), 'moodle/question:usemine', CAP_PREVENT);
        $generator->enrol_user($user->id, $course->id, 'editingteacher');
        $this->setUser($user);
        $qbank1 = $this->getDataGenerator()->create_module('qbank', ['course' => $course->id]);

        external::get_sharable_question_choices($qbank1->cmid, 'abc123');
    }

    public function test_get_sharable_question_choices_only_user(): void {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        role_change_permission($DB->get_field('role', 'id', ['shortname' => 'editingteacher']),
                \context_system::instance(), 'moodle/question:useall', CAP_INHERIT);
        $generator->enrol_user($user->id, $course->id, 'editingteacher');

        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $qbank = $this->getDataGenerator()->create_module('qbank', ['course' => $course->id, 'idnumber' => 'abc123']);
        $category = $questiongenerator->create_question_category([
                'name' => 'Category with idnumber',
                'idnumber' => 'abc123',
                'contextid' => \context_module::instance($qbank->cmid)->id,
            ]);

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
                ['value' => 'frog', 'label' => 'Question 1 [frog]'],
            ],
            external::get_sharable_question_choices($qbank->cmid, 'abc123'));
    }

    /**
     * Test cases for {@see test_get_embed_code_working()} and {@see test_is_authorized_secret_token()}.
     */
    public static function get_embed_code_cases(): array {
        return [
            ['abc123', 'toad', '', '', 'abc123/toad'],
            ['abc123', 'toad', '', 'id1', 'id1/abc123/toad'],
            ['abc123', 'toad', 'c1', 'id1', 'c1/id1/abc123/toad'],
            ['abc123', 'toad', 'c1', '', 'c1/abc123/toad'],
            ['A/V questions', '|---> 100%', '', '', 'A%2FV questions/%7C---> 100%25'],
            ['A/V questions', '|---> 100%', '', 'id1', 'id1/A%2FV questions/%7C---> 100%25'],
            ['A/V questions', '|---> 100%', 'c1', 'id1', 'c1/id1/A%2FV questions/%7C---> 100%25'],
            ['A/V questions', '|---> 100%', 'c1', '', 'c1/A%2FV questions/%7C---> 100%25'],
        ];
    }

    /**
     * Test getting the embed code for a particular question.
     *
     * @param string $catid idnumber to use for the category.
     * @param string $questionid idnumber to use for the question.
     * @param string $courseshortname the course shortname.
     * @param string $qbankidnumber the question bank idnumber.
     * @param string $expectedembedid what the embed id in the output should be.
     * @dataProvider get_embed_code_cases
     */
    public function test_get_embed_code_working(string $catid, string $questionid,
            string $courseshortname, string $qbankidnumber, string $expectedembedid): void {
        $this->resetAfterTest();

        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['shortname' => $courseshortname]);
        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $qbank = $this->getDataGenerator()->create_module('qbank', ['course' => $course->id], ['idnumber' => $qbankidnumber]);
        $category = $questiongenerator->create_question_category(
                ['name' => 'Category', 'idnumber' => $catid, 'contextid' => \context_module::instance($qbank->cmid)->id]);

        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question', 'idnumber' => $questionid]);
        $embedid = new embed_id($catid, $questionid, $qbankidnumber, $courseshortname);
        $iframedescription = '';
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
        $expected = '{Q{' . $expectedembedid . '|' . $token . '}Q}';
        $actual = external::get_embed_code($course->id, $embedid->categoryidnumber,
                $embedid->questionidnumber, $iframedescription, $behaviour,
                $maxmark, $variant, $correctness, $marks, $markdp, $feedback,
                $generalfeedback, $rightanswer, $history, '', $courseshortname, $qbankidnumber);

        $this->assertEquals($expected, $actual);

        $behaviour = 'immediatefeedback';
        $expected = '{Q{' . $expectedembedid . '|behaviour=' . $behaviour . '|' . $token . '}Q}';
        $actual = external::get_embed_code($course->id, $embedid->categoryidnumber,
                $embedid->questionidnumber, $iframedescription, $behaviour,
                $maxmark, $variant, $correctness, $marks, $markdp, $feedback, $generalfeedback,
                $rightanswer, $history, '', $courseshortname, $qbankidnumber);

        $this->assertEquals($expected, $actual);
    }

    public function test_get_embed_code_working_with_random_questions(): void {

        $this->resetAfterTest();

        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $qbank = $this->getDataGenerator()->create_module('qbank', ['course' => $course->id, 'idnumber' => 'abc123']);

        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $generator->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category(
                ['name' => 'Category', 'idnumber' => 'abc123', 'contextid' => \context_module::instance($qbank->cmid)->id]);

        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question1', 'idnumber' => 'toad']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question2', 'idnumber' => 'frog']);

        $embedid = new embed_id('abc123', 'toad');
        $iframedescription = 'Embedded random question';
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

        $titlebit = '|iframedescription=' . base64_encode($iframedescription);

        $token = token::make_secret_token($embedid);
        $expected = '{Q{' . $embedid . $titlebit . '|' . $token . '}Q}';
        $actual = external::get_embed_code($course->id, $embedid->categoryidnumber,
                $embedid->questionidnumber, $iframedescription, $behaviour,
                $maxmark, $variant, $correctness, $marks, $markdp, $feedback, $generalfeedback,
                $rightanswer, $history, '');
        $this->assertEquals($expected, $actual);

        $embedid = new embed_id('abc123', 'frog');
        $token = token::make_secret_token($embedid);
        $expected = '{Q{' . $embedid . $titlebit . '|' . $token . '}Q}';
        $actual = external::get_embed_code($course->id, $embedid->categoryidnumber,
                $embedid->questionidnumber, $iframedescription, $behaviour,
                $maxmark, $variant, $correctness, $marks, $markdp, $feedback, $generalfeedback,
                $rightanswer, $history, '');
        $this->assertEquals($expected, $actual);

        // Accept '*' for $questionidnumber to indicate a random question.
        $embedid = new embed_id('abc123', '*');
        $token = token::make_secret_token($embedid);
        $expected = '{Q{' . $embedid . $titlebit . '|' . $token . '}Q}';
        $actual = external::get_embed_code($course->id, $embedid->categoryidnumber,
                $embedid->questionidnumber, $iframedescription, $behaviour,
                $maxmark, $variant, $correctness, $marks, $markdp, $feedback, $generalfeedback,
                $rightanswer, $history, '');
        $this->assertEquals($expected, $actual);

        $behaviour = 'immediatefeedback';
        $expected = '{Q{' . $embedid . $titlebit . '|behaviour=' . $behaviour . '|' . $token . '}Q}';
        $actual = external::get_embed_code($course->id, $embedid->categoryidnumber,
                $embedid->questionidnumber, $iframedescription, $behaviour,
                $maxmark, $variant, $correctness, $marks, $markdp, $feedback, $generalfeedback,
                $rightanswer, $history, '');
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test function token::is_authorized_secret_token
     *
     * @param string $catid idnumber to use for the category.
     * @param string $questionid idnumber to use for the question.
     * @dataProvider get_embed_code_cases
     */
    public function test_is_authorized_secret_token(string $catid, string $questionid): void {

        $this->resetAfterTest();

        $this->setAdminUser();

        $embedid = new embed_id($catid, $questionid);
        $token = token::make_secret_token($embedid);

        $this->assertEquals(false, token::is_authorized_secret_token("AAA", $embedid));

        $this->assertEquals(true, token::is_authorized_secret_token($token, $embedid));
    }

}
