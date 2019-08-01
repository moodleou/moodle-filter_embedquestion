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
 * @copyright  2019 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Unit tests for the idnumber_upgrader functions.
 *
 * @copyright  2019 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_embedquestion_idnumber_upgrader_testcase extends advanced_testcase {

    public function test_update_item() {
        $testcategory = new stdClass();
        $testcategory->name = 'Category 1 [ID:cat1]';
        $testcategory->idnumber = null;
        $testcategory->contextid = 123;

        $updater = new filter_embedquestion\idnumber_upgrader();
        $this->assertTrue($updater->update_item($testcategory, $testcategory->contextid));
        $this->assertEquals('Category 1', $testcategory->name);
        $this->assertEquals('cat1', $testcategory->idnumber);
    }

    public function test_update_item_with_duplicated_categories() {
        $testcategory1 = new stdClass();
        $testcategory1->name = 'Category 1 [ID:cat1]';
        $testcategory1->idnumber = null;
        $testcategory1->contextid = 123;

        $testcategory2 = new stdClass();
        $testcategory2->name = 'Category 2 [ID:cat1]';
        $testcategory2->idnumber = null;
        $testcategory2->contextid = 123;

        $updater = new filter_embedquestion\idnumber_upgrader();
        $this->assertTrue($updater->update_item($testcategory1, $testcategory1->contextid));
        $this->assertEquals('Category 1', $testcategory1->name);
        $this->assertEquals('cat1', $testcategory1->idnumber);

        $this->assertTrue($updater->update_item($testcategory2, $testcategory2->contextid));
        $this->assertEquals('Category 2', $testcategory2->name);
        $this->assertEquals('cat1_1', $testcategory2->idnumber);

    }

    public function test_update_does_not_overwrite_existing_idnumber() {
        $testcategory = new stdClass();
        $testcategory->name = 'Category 1 [ID:cat1]';
        $testcategory->idnumber = 'AlreadySet';
        $testcategory->contextid = 123;

        $updater = new filter_embedquestion\idnumber_upgrader();
        $this->assertFalse($updater->update_item($testcategory, $testcategory->contextid));
        $this->assertEquals('Category 1 [ID:cat1]', $testcategory->name);
        $this->assertEquals('AlreadySet', $testcategory->idnumber);
    }

    public function test_update_idnumber_in_middle() {
        $testcategory = new stdClass();
        $testcategory->name = 'Category [ID:cat1] one';
        $testcategory->idnumber = null;
        $testcategory->contextid = 123;

        $updater = new filter_embedquestion\idnumber_upgrader();
        $this->assertTrue($updater->update_item($testcategory, $testcategory->contextid));
        $this->assertEquals('Category one', $testcategory->name);
        $this->assertEquals('cat1', $testcategory->idnumber);
    }

    public function test_update_idnumber_is_trimmed() {
        $testcategory = new stdClass();
        $testcategory->name = 'Category [ID: cat1 ] one';
        $testcategory->idnumber = null;
        $testcategory->contextid = 123;

        $updater = new filter_embedquestion\idnumber_upgrader();
        $this->assertTrue($updater->update_item($testcategory, $testcategory->contextid));
        $this->assertEquals('Category one', $testcategory->name);
        $this->assertEquals('cat1', $testcategory->idnumber);
    }

    public function test_upgrade_categories() {
        global $DB;

        $this->resetAfterTest();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();

        $contextc1 = context_course::instance($c1->id);
        $contextc2 = context_course::instance($c2->id);

        $cat1 = $questiongenerator->create_question_category(
                ['name' => 'Category1 [ID:cat1]', 'contextid' => $contextc1->id]);
        $cat2 = $questiongenerator->create_question_category(
                ['name' => 'Category2', 'contextid' => $contextc1->id]);
        $cat3 = $questiongenerator->create_question_category(
                ['name' => 'Category3 [ID: cat3]', 'contextid' => $contextc1->id]);
        $cat4 = $questiongenerator->create_question_category(
                ['name' => 'Category4', 'contextid' => $contextc2->id]);
        $cat5 = $questiongenerator->create_question_category(
                ['name' => 'Category5 [ID:cat5]', 'contextid' => $contextc2->id]);
        $cat6 = $questiongenerator->create_question_category(
                ['name' => 'Category6', 'contextid' => $contextc2->id]);

        // Do the update.
        $updater = new filter_embedquestion\idnumber_upgrader();
        $updater->update_question_category_idnumbers();

        // Verify the result.
        $course1categories = $DB->get_records('question_categories',
                ['contextid' => $contextc1->id], 'name');
        $this->assertEquals('Category1', $course1categories[$cat1->id]->name);
        $this->assertEquals('cat1', $course1categories[$cat1->id]->idnumber);
        $this->assertEquals('Category2', $course1categories[$cat2->id]->name);
        $this->assertNull($course1categories[$cat2->id]->idnumber);
        $this->assertEquals('Category3', $course1categories[$cat3->id]->name);
        $this->assertEquals('cat3', $course1categories[$cat3->id]->idnumber);

        $course2categories = $DB->get_records('question_categories',
                ['contextid' => $contextc2->id], 'name');
        $this->assertEquals('Category4', $course2categories[$cat4->id]->name);
        $this->assertNull($course2categories[$cat4->id]->idnumber);
        $this->assertEquals('Category5', $course2categories[$cat5->id]->name);
        $this->assertEquals('cat5', $course2categories[$cat5->id]->idnumber);
        $this->assertEquals('Category6', $course2categories[$cat6->id]->name);
        $this->assertNull($course2categories[$cat6->id]->idnumber);
    }

    public function test_upgrade_questions() {
        global $DB;

        $this->resetAfterTest();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();

        $contextc1 = context_course::instance($c1->id);
        $contextc2 = context_course::instance($c2->id);

        $cat1 = $questiongenerator->create_question_category(
                ['name' => 'Category1 [ID:cat1]', 'contextid' => $contextc1->id]);
        $cat2 = $questiongenerator->create_question_category(
                ['name' => 'Category2', 'contextid' => $contextc1->id]);
        $cat3 = $questiongenerator->create_question_category(
                ['name' => 'Category3 [ID: cat3]', 'contextid' => $contextc1->id]);
        $cat4 = $questiongenerator->create_question_category(
                ['name' => 'Category4', 'contextid' => $contextc2->id]);
        $cat5 = $questiongenerator->create_question_category(
                ['name' => 'Category5 [ID:cat5]', 'contextid' => $contextc2->id]);
        $cat6 = $questiongenerator->create_question_category(
                ['name' => 'Category6', 'contextid' => $contextc2->id]);

        // Add some questions to the category1.
        $q1 = $questiongenerator->create_question('shortanswer', null,
                        ['category' => $cat1->id, 'name' => 'Question1 [ID:que1]']);
        $q2 = $questiongenerator->create_question('shortanswer', null,
                        ['category' => $cat1->id, 'name' => 'Question2 [ID:que2]']);
        $q3 = $questiongenerator->create_question('shortanswer', null,
                        ['category' => $cat1->id, 'name' => 'Question3 [ID:que3] for testing', 'idnumber' => null]);
        $q4 = $questiongenerator->create_question('shortanswer', null,
                        ['category' => $cat1->id, 'name' => 'Question4 [ID:que4]', 'idnumber' => 'tampered idnumber in DB']);

        $updater = new filter_embedquestion\idnumber_upgrader();
        $updater->update_question_category_idnumbers();
        $updater->update_question_idnumbers();

        $cat1questions = $DB->get_records('question',
                ['category' => $cat1->id], 'name');
        $this->assertEquals('Question1', $cat1questions[$q1->id]->name);
        $this->assertEquals('que1', $cat1questions[$q1->id]->idnumber);
        $this->assertEquals('Question2', $cat1questions[$q2->id]->name);
        $this->assertEquals('que2', $cat1questions[$q2->id]->idnumber);
        $this->assertEquals('Question3 for testing', $cat1questions[$q3->id]->name);
        $this->assertEquals('que3', $cat1questions[$q3->id]->idnumber);
        $this->assertEquals('Question4 [ID:que4]', $cat1questions[$q4->id]->name);
        $this->assertEquals('tampered idnumber in DB', $cat1questions[$q4->id]->idnumber);
    }

    public function test_update_items_with_duplicated_categories() {
        global $DB;

        $this->resetAfterTest();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();

        $contextc1 = context_course::instance($c1->id);
        $contextc2 = context_course::instance($c2->id);

        $cat1 = $questiongenerator->create_question_category(
                ['name' => 'Category1 [ID:cat]', 'contextid' => $contextc1->id]);
        $cat2 = $questiongenerator->create_question_category(
                ['name' => 'Category2 [ID:cat]', 'contextid' => $contextc1->id]);
        $cat3 = $questiongenerator->create_question_category(
                ['name' => 'Category3 [ID:cat]', 'contextid' => $contextc2->id]);

        // Do the update.
        $updater = new filter_embedquestion\idnumber_upgrader();
        $updater->update_question_category_idnumbers();

        // Verify the result.
        $course1categories = $DB->get_records('question_categories',
                ['contextid' => $contextc1->id], 'name');
        $this->assertEquals('Category1', $course1categories[$cat1->id]->name);
        $this->assertEquals('cat', $course1categories[$cat1->id]->idnumber);
        $this->assertEquals('Category2', $course1categories[$cat2->id]->name);
        $this->assertEquals('cat_1', $course1categories[$cat2->id]->idnumber);

        $course2categories = $DB->get_records('question_categories',
                ['contextid' => $contextc2->id], 'name');
        $this->assertEquals('Category3', $course2categories[$cat3->id]->name);
        $this->assertEquals('cat', $course2categories[$cat3->id]->idnumber);
    }

    public function test_update_items_with_duplicated_questions() {
        global $DB;

        $this->resetAfterTest();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $c1 = $this->getDataGenerator()->create_course();

        $contextc1 = context_course::instance($c1->id);

        $cat1 = $questiongenerator->create_question_category(
                ['name' => 'Category1 [ID:cat1]', 'contextid' => $contextc1->id]);
        $cat2 = $questiongenerator->create_question_category(
                ['name' => 'Category2 [ID:cat2]', 'contextid' => $contextc1->id]);

        // Add some questions to the Category1 and Category2.
        $q1 = $questiongenerator->create_question('shortanswer', null,
                ['category' => $cat1->id, 'name' => 'Question1 [ID:que]', 'idnumber' => null]);
        $q2 = $questiongenerator->create_question('shortanswer', null,
                ['category' => $cat1->id, 'name' => 'Question2 [ID:que]', 'idnumber' => null]);
        $q3 = $questiongenerator->create_question('shortanswer', null,
                ['category' => $cat1->id, 'name' => 'Question3 [ID:que]', 'idnumber' => null]);
        $q4 = $questiongenerator->create_question('shortanswer', null,
                ['category' => $cat2->id, 'name' => 'Question4 [ID:que]', 'idnumber' => null]);

        // Do the update (First the question categories and then the questions).
        $updater = new filter_embedquestion\idnumber_upgrader();
        $updater->update_question_category_idnumbers();
        $updater->update_question_idnumbers();

        // Verify the result.
        $cat1questions = $DB->get_records('question',
                ['category' => $cat1->id], 'name');
        $this->assertEquals('Question1', $cat1questions[$q1->id]->name);
        $this->assertEquals('que', $cat1questions[$q1->id]->idnumber);
        $this->assertEquals('Question2', $cat1questions[$q2->id]->name);
        $this->assertEquals('que_1', $cat1questions[$q2->id]->idnumber);
        $this->assertEquals('Question3', $cat1questions[$q3->id]->name);
        $this->assertEquals('que_2', $cat1questions[$q3->id]->idnumber);

        $cat2questions = $DB->get_records('question',
                ['category' => $cat2->id], 'name');
        $this->assertEquals('Question4', $cat2questions[$q4->id]->name);
        $this->assertEquals('que', $cat2questions[$q4->id]->idnumber);
    }
}
