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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/modinfolib.php');

use filter_embedquestion\form\embed_options_form;

/**
 * Unit tests for embed_options_form.
 *
 * @package   filter_embedquestion
 * @copyright 2026 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \filter_embedquestion\form\embed_options_form
 */
final class embed_options_form_test extends \advanced_testcase {
    /**
     * Set up a minimal page context so form rendering (js_call_amd, render_from_template) works.
     *
     * @param int $courseid the course to set the page URL for.
     */
    protected function setup_page_context(int $courseid): void {
        global $PAGE;
        $PAGE->set_url('/filter/embedquestion/testhelper.php', ['courseid' => $courseid]);
    }

    /**
     * Test that the form loads without throwing when the qbankcmid refers to a
     * completely non-existent course module (e.g. a stale user preference pointing
     * to a qbank that was already deleted from the database).
     */
    public function test_definition_does_not_throw_when_qbank_not_found(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $this->setup_page_context($course->id);

        // Use a cmid that has never existed — simulates a stale saved preference
        // pointing to a qbank that was deleted from the database.
        $nonexistentcmid = 999999;

        // Must not throw "Can't find data record in database".
        $form = new embed_options_form(null, [
            'context'   => $context,
            'qbankcmid' => $nonexistentcmid,
        ]);

        $this->assertInstanceOf(embed_options_form::class, $form);
    }

    /**
     * Test that the form loads without throwing when the qbankcmid refers to a course
     * module that has deletioninprogress = 1 (scheduled for async deletion).
     */
    public function test_definition_does_not_throw_when_qbank_deletion_in_progress(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $qbank = $this->getDataGenerator()->create_module('qbank', ['course' => $course->id]);
        $context = \context_course::instance($course->id);
        $this->setup_page_context($course->id);

        // Simulate the qbank being scheduled for async deletion
        // (what Moodle sets when course_delete_module() is called with $async = true).
        $DB->set_field('course_modules', 'deletioninprogress', 1, ['id' => $qbank->cmid]);

        // Must not throw "Can't find data record in database".
        $form = new embed_options_form(null, [
            'context'   => $context,
            'qbankcmid' => $qbank->cmid,
        ]);

        $this->assertInstanceOf(embed_options_form::class, $form);
    }
}
