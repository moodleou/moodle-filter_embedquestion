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
                ['name' => 'Category with idnumber [ID:abc123]']);
        $questiongenerator->create_question_category();

        $this->assertEquals($catwithidnumber->id,
                \filter_embedquestion\utils::get_category_by_idnumber(
                        context_system::instance(), 'abc123')->id);
    }

    public function test_get_category_by_idnumber_not_existing() {

        $this->assertSame(false,
                \filter_embedquestion\utils::get_category_by_idnumber(
                        context_system::instance(), 'abc123'));
    }

    public function test_get_question_by_idnumber() {

        $this->resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $catwithidnumber = $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber [ID:abc123]']);
        $q = $questiongenerator->create_question('shortanswer', null,
                ['category' => $catwithidnumber->id, 'name' => 'Question [ID:frog]']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $catwithidnumber->id]);

        $this->assertEquals($q->id,
                \filter_embedquestion\utils::get_question_by_idnumber(
                        $catwithidnumber->id, 'frog')->id);
    }

    public function test_get_question_by_idnumber_not_existing() {

        $this->resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $catwithidnumber = $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber [ID:abc123]']);

        $this->assertSame(false,
                \filter_embedquestion\utils::get_question_by_idnumber(
                        $catwithidnumber->id, 'frog'));
    }

    public function test_get_categories_with_sharable_question_choices() {

        $this->resetAfterTest();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $catwithid1 = $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber [ID:abc123]']);
        $catwithid2 = $questiongenerator->create_question_category(
                ['name' => 'Second category with [ID:pqr789]']);
        $catnoidnumber = $questiongenerator->create_question_category();

        $questiongenerator->create_question('shortanswer', null,
                ['category' => $catwithid2->id, 'name' => 'Question [ID:frog]']);

        $this->assertEquals([
                '' => 'Choose...',
                'abc123' => 'Category with idnumber [ID:abc123] (0)',
                'pqr789' => 'Second category with [ID:pqr789] (1)'],
                \filter_embedquestion\utils::get_categories_with_sharable_question_choices(
                        context_system::instance()));
    }

    public function test_get_categories_with_sharable_question_choices_only_user() {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $catwithid1 = $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber [ID:abc123]']);
        $catwithid2 = $questiongenerator->create_question_category(
                ['name' => 'Second category with [ID:pqr789]']);

        $q1 = $questiongenerator->create_question('shortanswer', null,
                ['category' => $catwithid1->id, 'name' => 'Question [ID:toad]']);
        $this->setGuestUser();
        $q2 = $questiongenerator->create_question('shortanswer', null,
                ['category' => $catwithid2->id, 'name' => 'Question [ID:frog]']);
        $this->setAdminUser();

        $this->assertEquals([
                '' => 'Choose...',
                'abc123' => 'Category with idnumber [ID:abc123] (1)',
                'pqr789' => 'Second category with [ID:pqr789] (0)'],
                \filter_embedquestion\utils::get_categories_with_sharable_question_choices(
                        context_system::instance(), $USER->id));
    }

    public function test_get_sharable_question_choices() {

        $this->resetAfterTest();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber [ID:abc123]']);

        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 2 [ID:toad]']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 1 [ID:frog]']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id]);

        $this->assertEquals([
                '' => 'Choose...',
                'frog' => 'Question 1 [ID:frog]',
                'toad' => 'Question 2 [ID:toad]'],
                \filter_embedquestion\utils::get_sharable_question_choices(
                        $category->id));
    }

    public function test_get_sharable_question_choices_only_user() {
        global $USER;

        $this->resetAfterTest();

        /** @var core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category(
                ['name' => 'Category with idnumber [ID:abc123]']);

        $this->setGuestUser();
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 2 [ID:toad]']);
        $this->setAdminUser();
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id, 'name' => 'Question 1 [ID:frog]']);
        $questiongenerator->create_question('shortanswer', null,
                ['category' => $category->id]);

        $this->assertEquals([
                '' => 'Choose...',
                'frog' => 'Question 1 [ID:frog]'],
                \filter_embedquestion\utils::get_sharable_question_choices(
                        $category->id, $USER->id));
    }
}
