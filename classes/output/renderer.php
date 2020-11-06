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

use filter_embedquestion\question_options,
    plugin_renderer_base;


/**
 * The filter_embedquestion renderer.
 *
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @method string header() inherited from core_renderer.
 * @method string footer() inherited from core_renderer.
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
     * Render the question as it will appear in the iframe.
     *
     * @param \question_usage_by_activity $quba containing the question to display.
     * @param int $slot slot number of the question to display.
     * @param question_options $options the display options to use.
     * @param string $displaynumber how to display the question number.
     * @return string HTML to output.
     */
    public function embedded_question(\question_usage_by_activity $quba, int $slot,
            question_options $options, string $displaynumber): string {

        $this->page->requires->js_module('core_question_engine');
        $output = $quba->render_question($slot, $options, $displaynumber);

        if ($options->fillwithcorrect) {
            $output = $this->add_fill_with_correct_link($output);
        }

        return $output;
    }

    /**
     * Actually insert the Fill with correct link into the HTML.
     *
     * @param string $questionhtml the basic rendered output of the question.
     * @return string Updated question rendering.
     */
    protected function add_fill_with_correct_link(string $questionhtml): string {

        $fillbutton = $this->render_fill_with_correct();

        // If we can, insert at the end of the info section.
        if (preg_match('~<div class="info">.*</div><div class="content">~', $questionhtml)) {
            $questionhtml = preg_replace('~(<div class="info">.*)(</div><div class="content">)~',
                    '$1' . $fillbutton . '$2', $questionhtml, 1);

        } else {
            // Otherwise, just add at the end.
            $questionhtml .= $fillbutton;
        }

        return $questionhtml;
    }

    /**
     * Render the Fill with correct button.
     *
     * @return string HTML string
     */
    public function render_fill_with_correct(): string {
        return \html_writer::div(\html_writer::tag('button',
                    $this->pix_icon('e/tick', '', 'moodle', ['class' => 'iconsmall']) .
                            \html_writer::span(get_string('fillcorrect', 'mod_quiz')),
                    ['type' => 'submit', 'name' => 'fillwithcorrect', 'value' => 1,
                            'class' => 'btn btn-link']),
                'filter_embedquestion-fill-link');
    }
}
