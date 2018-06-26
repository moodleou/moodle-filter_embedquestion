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

/*
 * The module resizes the iframe containing the embedded question to be
 * just the right size for the question.
 *
 * @module    filter_ebmedquestion/question
 * @package   filter_ebmedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {
    var t = {
        /**
         * The
         */
        currentHeight: null,

        /**
         * Initialise method.
         */
        init: function() {
            // Prevent a vertical scroll-bar in all cases.
            document.documentElement.style['overflow-y'] = 'hidden';

            // Only initialise if we are in a frame.
            if (!window.frameElement) {
                return;
            }

            // Initialise.
            t.resizeContainingFrame();
            setInterval(t.resizeContainingFrame, 100);
        },

        /**
         * Set the size of the containing frame to what we need.
         */
        resizeContainingFrame: function() {
            // Has the height changed significantly?
            if (t.currentHeight === document.body.scrollHeight) {
                return;
            }

            // Resize required. Do it.
            t.currentHeight = document.body.scrollHeight;
            // Extra height to allow for any horizontal scroll bar.
            window.frameElement.style.height = (t.currentHeight + 25) + "px";
        }
    };
    return t;
});
