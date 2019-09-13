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
 * Upgrade library code for the filter embed question.
 *
 * @package    filter_embedquestion
 * @copyright  2019 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_embedquestion;

defined('MOODLE_INTERNAL') || die();

class idnumber_upgrader {
    protected $usedidnumbers;

    /**
     * Move the idnumber for all categories from the name to the idnumber field.
     */
    public function update_question_category_idnumbers(): void {
        global $DB;

        $categories = $DB->get_records_sql("
                SELECT qc.*
                  FROM {question_categories} qc
                  JOIN {context} ctx ON ctx.id = qc.contextid
                 WHERE qc.name LIKE ?
                   AND ctx.contextlevel = ?
              ORDER BY qc.contextid, qc.id
            ", ['%[ID:%]%', CONTEXT_COURSE]);

        $this->usedidnumbers = [];
        foreach ($categories as $category) {
            $needsupdate = $this->update_item($category, $category->contextid);

            if ($needsupdate) {
                $DB->update_record('question_categories', $category);
            }
        }
    }

    /**
     * Move the idnumber for all questions from the name to the idnumber field.
     */
    public function update_question_idnumbers(): void {
        global $DB;

        $questions = $DB->get_records_sql("
                SELECT q.*
                  FROM {question} q
                  JOIN {question_categories} qc ON qc.id = q.category
                 WHERE q.name LIKE ?
                   AND qc.idnumber IS NOT NULL
              ORDER BY qc.id, q.id
            ", ['%[ID:%]%']);

        $this->usedidnumbers = [];
        foreach ($questions as $question) {
            $needsupdate = $this->update_item($question, $question->category);

            if ($needsupdate) {
                $DB->update_record('question', $question);
            }
        }
    }

    /**
     * Update the name and idnumber of something.
     *
     * @param \stdClass $item the item (question/category) to update the inumber/name of.
     * @param int $group scope within which idnumbers must be unique (context id/category id).
     * @return bool does the item need updating in the database.
     */
    public function update_item(\stdClass $item, int $group): bool {
        if (!preg_match('~\[ID:(.+)]~', $item->name, $matches)) {
            // Not actually an embeddable thing.
            return false;
        }

        if ($item->idnumber !== null) {
            // ID number already set, so don't overwrite.
            return false;
        }

        $item->name = trim(preg_replace('~ *\[ID:' . preg_quote($matches[1]) . '] *~',
                ' ' , $item->name));
        $item->idnumber = trim($matches[1]);

        if (isset($this->usedidnumbers[$group][$item->idnumber])) {
            $suffix = 1;
            while (isset($this->usedidnumbers[$group][$item->idnumber . '_'. $suffix])) {
                $suffix++;
            }
            $item->idnumber .= '_' . $suffix;
        }

        $this->usedidnumbers[$group][$item->idnumber] = 1;
        return true;
    }
}
