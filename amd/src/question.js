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
 * JavaScript for question in iframe.
 *
 * @package filter_ebmedquestion
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {
    var t = {
        height: 0,

        init: function() {
            var iframe = $('#filter-embedquestion');
            iframe.addClass('no-forced-vertical-scroll');
            if (iframe.length === 0) {
                return;
            }
            iframe.css('height', iframe[0].contentWindow.screen.height + 'px');
            if (t.height !== iframe.css('height')) {
                 t.height = iframe.css('height');
            }
            iframe.css(
                {
                    'overflow' : 'auto',
                    'vspace' : '0',
                    'hspace' : '0',
                    'frameborder' : '0',
                    'border' : '0',
                    'cellspacing' : '0'
                }
            );
        }
    };
   return t;
});
