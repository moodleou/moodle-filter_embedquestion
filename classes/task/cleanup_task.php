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
 * A scheduled task to ensure that old attempt data is cleaned up.
 *
 * @package   filter_embedquestion
 * @category  task
 * @copyright 2018 the Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace filter_embedquestion\task;
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/questionlib.php');
use core\task\scheduled_task;


/**
 * A scheduled task to ensure that old attempt data is cleaned up.
 *
 * @copyright 2018 the Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_task extends scheduled_task {
    /**
     * @var int time after which to delete attempts. We delete attempts that have not been touched for 24 hours.
     */
    const MAX_AGE = 24 * 60 * 60;

    public function get_name() {
        return get_string('taskcleanup', 'filter_embedquestion');
    }

    public function execute() {
        $lastmodifiedcutoff = time() - self::MAX_AGE;

        mtrace("\n  Cleaning up old embedded question attempts...", '');
        $oldattempts = new \qubaid_join('{question_usages} quba', 'quba.id',
                'quba.component = :qubacomponent
                    AND NOT EXISTS (
                        SELECT 1
                          FROM {question_attempts}      subq_qa
                          JOIN {question_attempt_steps} subq_qas ON subq_qas.questionattemptid = subq_qa.id
                          JOIN {question_usages}        subq_qu  ON subq_qu.id = subq_qa.questionusageid
                         WHERE subq_qa.questionusageid = quba.id
                           AND subq_qu.component = :qubacomponent2
                           AND (subq_qa.timemodified > :qamodifiedcutoff
                                    OR subq_qas.timecreated > :stepcreatedcutoff)
                    )
            ',
                array('qubacomponent' => 'filter_embedquestion', 'qubacomponent2' => 'filter_embedquestion',
                        'qamodifiedcutoff' => $lastmodifiedcutoff, 'stepcreatedcutoff' => $lastmodifiedcutoff));

        \question_engine::delete_questions_usage_by_activities($oldattempts);
        mtrace('done.');
    }
}
