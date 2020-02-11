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
 * @module    filter_embedquestion/question
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    var t = {
        /**
         * The last height we set on the iframe, so we only try to change it when it changes.
         */
        currentHeight: null,

        /**
         * Initialise method.
         */
        init: function() {
            // Only initialise if we are in a frame.
            if (!window.frameElement) {
                return;
            }

            // Initialise the resize logic.
            t.resizeContainingFrame();

            // If something like an error causes junk to be output before the open <html> tag,
            // then that puts the document into BackCompat rendering mode, and the iframe keeps
            // getting bigger and bigger. So, only do the continual resize logic if the browser
            // is in standards compliant rendering mode.
            if (typeof document.compatMode !== 'undefined' && document.compatMode !== "BackCompat") {
                setInterval(t.resizeContainingFrame, 100);
            }

            // Prevent a vertical scroll-bar in all cases.
            // We can't do this in the CSS, because there is no suitable class on
            // the <html> tag.
            document.documentElement.style['overflow-y'] = 'hidden';
            document.documentElement.style.height = 'auto';

            // Make the edit question link (if present) open in the full window.
            document.querySelectorAll('.editquestion a').forEach(function(element) {
                element.setAttribute('target', '_top');
            });

            Y.use('moodle-core-formchangechecker', function() {
                M.core_formchangechecker.init({formid: 'responseform'});
            });
        },

        /**
         * Set the size of the containing frame to what we need.
         */
        resizeContainingFrame: function() {
            // It seems sensible to use scrollHeight in this function, but that is
            // buggy in Safari. https://bugs.webkit.org/show_bug.cgi?id=25240 has
            // a useful table showing that body.offsetHeight is reliable.

            // Has the height changed?
            if (t.currentHeight === document.body.offsetHeight) {
                return; // No.
            }

            // Resize required. Do it.
            t.currentHeight = document.body.offsetHeight;
            // Extra height to allow for any horizontal scroll bar.
            window.frameElement.style.height = (t.currentHeight + 25) + "px";
        }
    };
    return t;
});
