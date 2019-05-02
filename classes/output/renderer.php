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
 * The filter_embedquestion renderer.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_embedquestion\output;
defined('MOODLE_INTERNAL') || die();

use filter_embedquestion\question_options;
use plugin_renderer_base;


/**
 * The filter_embedquestion renderer.
 *
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Render an embed_iframe.
     *
     * @param embed_iframe $embediframe to render.
     * @return string HTML to output.
     */
    public function render_embed_iframe(embed_iframe $embediframe) {
        return $this->render_from_template('filter_embedquestion/embed_iframe',
                $embediframe->export_for_template($this));
    }

    /**
     * Render an error_message.
     *
     * @param error_message $errormessage to render.
     * @return string HTML to output.
     */
    public function render_error_message(error_message $errormessage) {
        return $this->render_from_template('filter_embedquestion/error_message',
                $errormessage->export_for_template($this));
    }

    /**
     * Render the question as it will appear in the iframce.
     *
     * @param \question_usage_by_activity $quba containing the question to display.
     * @param int $slot slot number of the question to display.
     * @param question_options $options the display options to use.
     * @param string $displaynumber how to display the question number.
     * @return string HTML to output.
     */
    public function embedded_question(\question_usage_by_activity $quba, int $slot,
            question_options $options, string $displaynumber): string {

        $questionhtml = $quba->render_question($slot, $options, $displaynumber);

        // We try to move the info to after the question formulation.
        if ($displaynumber === 'i') {
            // But not for information items.
            return $questionhtml;
        }

        if (!preg_match('~<div class="info">.*</div>(?=<div class="content">)~', $questionhtml, $matches)) {
            // Could not find the info div. Don't do anything.
            return $questionhtml;
        }

        // Info found.
        $info = $matches[0];

        // Remove it from its old place.
        $questionhtml = preg_replace('~<div class="info">.*</div>(?=<div class="content">)~', '', $questionhtml, 1);

        // Do not show question title within the info.
        $info = preg_replace('~<h3 class="no">.*<span class="qno">.*</span></h3>~', '', $info);

        if (preg_match('~(?<=</div>)<div class="outcome\b[^"]*">~', $questionhtml, $matches, PREG_OFFSET_CAPTURE)) {
            // If the outcome div is present, insert the info before it.
            $insertpos = $matches[0][1];
        } else {
            // Else put at the end.
            $insertpos = strlen($questionhtml) - strlen('</div></div>');
        }

        $questionhtml = substr($questionhtml, 0, $insertpos) . $info . substr($questionhtml, $insertpos);

        return $questionhtml;
    }
}
