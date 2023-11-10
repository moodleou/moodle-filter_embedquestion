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

use filter_embedquestion\task\cleanup_task;

/**
 * Unit tests for the cleanup scheduled task.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \filter_embedquestion\task\cleanup_task
 */
class cleanup_task_test extends \advanced_testcase {

    public function test_cleanup_task(): void {

        $this->resetAfterTest();

        // Just a basic test that the task runs without DB errors.
        $this->expectOutputString("\n  Cleaning up old embedded question attempts...done.\n");
        $task = new cleanup_task();
        $task->execute();
    }
}
