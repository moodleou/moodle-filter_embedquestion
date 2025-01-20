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

namespace filter_embedquestion\output;

use filter_embedquestion\question_options;
use filter_embedquestion\utils;
use report_embedquestion;

/**
 * The filter_embedquestion renderer.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @method string header() inherited from core_renderer.
 * @method string footer() inherited from core_renderer.
 */
class renderer extends \plugin_renderer_base {
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

        if ($options->showquestionbank) {
            $output = $this->add_questionbank_link($quba, $slot, $output);
        }

        if ($options->fillwithcorrect) {
            $output = $this->add_fill_with_correct_link($output);
        }

        if (class_exists(report_embedquestion\attempt_summary_table::class)) {
            $output = $this->add_embedded_question_report_link($quba, $slot, $output);
        }

        return $output;
    }

    /**
     * Add a link to navigate to the embedded question progress report.
     *
     * @param \question_usage_by_activity $quba Containing the question to display.
     * @param int $slot Slot number of the question to display.
     * @param string $output Template string.
     * @return string Updated question rendering.
     */
    protected function add_embedded_question_report_link(\question_usage_by_activity $quba, int $slot,
            string $output): string {

        $reportlink = $this->render_embedded_question_report_link($quba, $slot);
        return $this->insert_html_into_info_section($output, $reportlink);
    }

    /**
     * Render embedded question report link.
     *
     * @param \question_usage_by_activity $quba containing the question to display.
     * @param int $slot slot number of the question to display.
     * @return string Embedded question report link HTML string.
     */
    public function render_embedded_question_report_link(\question_usage_by_activity $quba, int $slot): string {
        global $USER;

        $displayoption = new \report_embedquestion\report_display_options($this->page->course->id, $this->page->cm);
        $url = $displayoption->get_url();
        $url->params([
            'userid' => $USER->id,
            'usageid' => $quba->get_question_attempt($slot)->get_usage_id(),
        ]);

        return \html_writer::div(
            \html_writer::link(
                $url,
                \html_writer::span(get_string('previousattempts', 'filter_embedquestion')),
                ['target' => '_top']
            ),
            'link-wrapper-class'
        );
    }

    /**
     * Render view the question bank element and append to info class.
     *
     * @param \question_usage_by_activity $quba Containing the question to display.
     * @param int $slot Slot number of the question to display.
     * @param string $output Template string.
     * @return string New template string.
     */
    protected function add_questionbank_link(\question_usage_by_activity $quba, int $slot, string $output): string {

        $questionbank = $this->render_questionbank_link($quba, $slot);
        return $this->insert_html_into_info_section($output, $questionbank);
    }

    /**
     * Actually insert the Fill with correct link into the HTML.
     *
     * @param string $questionhtml the basic rendered output of the question.
     * @return string Updated question rendering.
     */
    protected function add_fill_with_correct_link(string $questionhtml): string {

        $fillbutton = $this->render_fill_with_correct();
        return $this->insert_html_into_info_section($questionhtml, $fillbutton);
    }

    /**
     * Render the Fill with correct button.
     *
     * @return string HTML string
     */
    public function render_fill_with_correct(): string {
        return \html_writer::div(
            \html_writer::tag(
                'button',
                $this->pix_icon('e/tick', '', 'moodle', ['class' => 'iconsmall']) .
                        \html_writer::span(get_string('fillcorrect', 'mod_quiz')),
                [
                    'type' => 'submit',
                    'name' => 'fillwithcorrect', 'value' => 1,
                    'class' => 'btn btn-link',
                ],
            ),
            'filter_embedquestion-fill-link',
        );
    }

    /**
     * Render question bank link.
     *
     * @param \question_usage_by_activity $quba Containing the question to display.
     * @param int $slot Slot number of the question to display.
     * @return string Question bank HTML string.
     */
    public function render_questionbank_link(\question_usage_by_activity $quba, int $slot): string {
        $question = $quba->get_question_attempt($slot)->get_question(false);

        return \html_writer::tag('div',
            \html_writer::link(
                utils::get_question_bank_url($question),
                $this->pix_icon('qbank', '', 'filter_embedquestion',
                    ['class' => 'iconsmall']) . get_string('questionbank', 'filter_embedquestion'),
                ['target' => '_top']
            ),
            ['class' => 'filter_embedquestion-viewquestionbank']
        );
    }

    /**
     * Insert a template element into info element.
     *
     * If the info div is not found, the content is just added at the end.
     *
     * @param string $template HTML of the rendered question, containing at least the info <div>.
     * @param string $childtemplate HTML to insert at the end of, and inside, the info div.
     * @return string The combined template.
     */
    protected function insert_html_into_info_section(string $template, string $childtemplate): string {
        // If we can, insert at the end of the info section.
        if (preg_match('~<div class="info">.*</div><div class="content">~', $template)) {
            $template = preg_replace('~(<div class="info">.*)(</div><div class="content">)~',
                '$1' . $childtemplate . '$2', $template, 1);
        } else {
            // Otherwise, just add at the end.
            $template .= $childtemplate;
        }
        return $template;
    }
}
