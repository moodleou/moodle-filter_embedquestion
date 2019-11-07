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
 * Unit test for the filter_embedquestion util methods.
 *
 * @package    filter_embedquestion
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/embedquestion/filter.php');
use filter_embedquestion\utils;


/**
 * Unit tests for the util methods.
 *
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_embedquestion_utils_testcase extends advanced_testcase {

    public function test_get_category_by_idnumber() {
        $this->resetAfterTest();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $catwithidnumber = $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber', 'idnumber' => 'abc123']);
        $questiongenerator->create_question_category();

        $this->assertEquals($catwithidnumber->id,
                utils::get_category_by_idnumber(
                        context_system::instance(), 'abc123')->id);
    }

    public function test_get_category_by_idnumber_not_existing() {

        $this->assertSame(null,
                utils::get_category_by_idnumber(
                        context_system::instance(), 'abc123'));
    }

    public function test_get_question_by_idnumber() {
        $this->resetAfterTest();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $catwithidnumber = $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber', 'idnumber' => 'abc123']);
        $q = $questiongenerator->create_question('shortanswer', null,
                ['category' => $catwithidnumber->id, 'name' => 'Question', 'idnumber' => 'frog']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $catwithidnumber->id]);

        $this->assertEquals($q->id,
                utils::get_question_by_idnumber(
                        $catwithidnumber->id, 'frog')->id);
    }

    public function test_get_question_by_idnumber_not_existing() {
        $this->resetAfterTest();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $catwithidnumber = $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber', 'idnumber' => 'abc123']);

        $this->assertSame(null,
                utils::get_question_by_idnumber(
                        $catwithidnumber->id, 'frog'));
    }

    public function test_get_categories_with_sharable_question_choices() {
        $this->resetAfterTest();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber', 'idnumber' => 'abc123']);
        $catwithid2 = $questiongenerator->create_question_category(
                ['name' => 'Second category', 'idnumber' => 'pqr789']);
        $questiongenerator->create_question_category();

        $questiongenerator->create_question('shortanswer', null,
                ['category' => $catwithid2->id, 'name' => 'Question', 'idnumber' => 'frog']);

        $this->assertEquals([
                '' => 'Choose...',
                'pqr789' => 'Second category [pqr789] (1)'],
                utils::get_categories_with_sharable_question_choices(
                        context_system::instance()));
    }

    public function test_get_categories_with_sharable_question_choices_only_user() {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $catwithid1 = $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber', 'idnumber' => 'abc123']);
        $catwithid2 = $questiongenerator->create_question_category(
                ['name' => 'Second category with', 'idnumber' => 'pqr789']);

        $questiongenerator->create_question('shortanswer', null,
                ['category' => $catwithid1->id, 'name' => 'Question', 'idnumber' => 'toad']);
        $this->setGuestUser();
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $catwithid2->id, 'name' => 'Question', 'idnumber' => 'frog']);
        $this->setAdminUser();

        $this->assertEquals([
                '' => 'Choose...',
                'abc123' => 'Category with idnumber [abc123] (1)'],
                utils::get_categories_with_sharable_question_choices(
                        context_system::instance(), $USER->id));
    }

    public function test_get_sharable_question_choices() {
        $this->resetAfterTest();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber', 'idnumber' => 'abc123']);

        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 2', 'idnumber' => 'toad']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 1', 'idnumber' => 'frog']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id]);

        $this->assertEquals([
                '' => 'Choose...',
                'frog' => 'Question 1 [frog]',
                'toad' => 'Question 2 [toad]',
                '*' => get_string('chooserandomly', 'filter_embedquestion')],
                utils::get_sharable_question_choices(
                        $category->id));
    }

    public function test_get_sharable_question_choices_only_user() {
        global $USER;

        $this->resetAfterTest();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber', 'idnumber' => 'abc123']);

        $this->setGuestUser();
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 2', 'idnumber' => 'toad']);
        $this->setAdminUser();
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 1', 'idnumber' => 'frog']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id]);

        $this->assertEquals([
                '' => 'Choose...',
                'frog' => 'Question 1 [frog]'],
                utils::get_sharable_question_choices(
                        $category->id, $USER->id));
    }

    public function test_get_sharable_question_choices_should_not_include_random() {
        $this->resetAfterTest();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber', 'idnumber' => 'abc123']);

        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 1', 'idnumber' => 'frog']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id]);

        // Now create a random question in that category.
        $form = new stdClass();
        $form->category = $category->id . ',' . $category->contextid;
        $form->includesubcategories = false;
        $form->questiontext = ['text' => '0', 'format' => 0]; // Needed for the test to pass in Moodle 3.4.
        $form->defaultmark = 1;
        $form->hidden = 1;
        $form->stamp = make_unique_id_code(); // Set the unique code (not to be changed).
        $question = new stdClass();
        $question->qtype = 'random';
        question_bank::get_qtype('random')->save_question($question, $form);

        // The random question should not appear in the list.
        $this->assertEquals([
                '' => 'Choose...',
                'frog' => 'Question 1 [frog]'],
                utils::get_sharable_question_choices(
                        $category->id));
    }

    public function test_get_categories_with_sharable_question_choices_should_not_include_random() {
        $this->resetAfterTest();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber', 'idnumber' => 'abc123']);
        $catwithid2 = $questiongenerator->create_question_category(
                ['name' => 'Second category with', 'idnumber' => 'pqr789']);
        $questiongenerator->create_question_category();

        // Now create a random question in that category.
        $form = new stdClass();
        $form->category = $catwithid2->id . ',' . $catwithid2->contextid;
        $form->includesubcategories = false;
        $form->questiontext = ['text' => '0', 'format' => 0]; // Needed for the test to pass in Moodle 3.4.
        $form->defaultmark = 1;
        $form->hidden = 1;
        $form->stamp = make_unique_id_code(); // Set the unique code (not to be changed).
        $question = new stdClass();
        $question->qtype = 'random';
        question_bank::get_qtype('random')->save_question($question, $form);

        $questiongenerator->create_question('shortanswer', null,
                ['category' => $catwithid2->id, 'name' => 'Question', 'idnumber' => 'frog']);

        // The random question should not appear in the counts.
        $this->assertEquals([
                '' => 'Choose...',
                'pqr789' => 'Second category with [pqr789] (1)'],
                utils::get_categories_with_sharable_question_choices(
                        context_system::instance()));
    }

    public function test_get_sharable_question_choices_should_not_include_hidden() {
        global $DB;
        $this->resetAfterTest();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber', 'idnumber' => 'abc123']);

        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question', 'idnumber' => 'frog']);
        $hiddenq = $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question (hidden)', 'idnumber' => 'toad']);
        $DB->set_field('question', 'hidden', 1, ['id' => $hiddenq->id]);

        // The hidden question should not appear in the list.
        $this->assertEquals([
                '' => 'Choose...',
                'frog' => 'Question [frog]'],
                utils::get_sharable_question_choices(
                        $category->id));
    }

    public function test_get_categories_with_sharable_question_choices_should_not_include_hidden() {
        global $DB;
        $this->resetAfterTest();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber', 'idnumber' => 'abc123']);
        $catwithid2 = $questiongenerator->create_question_category(
                ['name' => 'Second category with', 'idnumber' => 'pqr789']);
        $questiongenerator->create_question_category();

        $questiongenerator->create_question('shortanswer', null,
                ['category' => $catwithid2->id, 'name' => 'Question', 'idnumber' => 'frog']);
        $hiddenq = $questiongenerator->create_question('shortanswer', null,
                ['category' => $catwithid2->id, 'name' => 'Question (hidden)', 'idnumber' => 'toad']);
        $DB->set_field('question', 'hidden', 1, ['id' => $hiddenq->id]);

        // The hidden question should not appear in the counts.
        $this->assertEquals([
                '' => 'Choose...',
                'pqr789' => 'Second category with [pqr789] (1)'],
                utils::get_categories_with_sharable_question_choices(
                        context_system::instance()));
    }

    public function test_behaviour_choices() {
        // This test is written in a way that will work even if extra behaviours are installed.
        $choices = utils::behaviour_choices();
        $this->assertArrayHasKey('interactive', $choices);
        $this->assertArrayHasKey('adaptive', $choices);
        $this->assertArrayHasKey('immediatefeedback', $choices);
        $this->assertArrayHasKey('immediatecbm', $choices);
        $this->assertArrayNotHasKey('deferredfeedback', $choices);
    }
}
