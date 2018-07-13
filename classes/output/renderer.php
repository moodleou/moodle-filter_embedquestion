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
}
