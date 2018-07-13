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
 * The module provides autocomplete for the question idnumber form field.
 *
 * @module    filter_embedquestion/questionid_choice_updater
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax'], function($, Ajax) {
    var t = {
        /**
         * Initialise the handling.
         */
        init: function() {
            $('select#id_categoryidnumber').on('change', t.categoryChanged);
            t.lastCategory = null;
        },

        /**
         * Used to track when the category really changes.
         */
        lastCategory: null,

        /**
         * Source of data for Ajax element.
         */
        categoryChanged: function() {
            if ($('select#id_categoryidnumber').val() === t.lastCategory) {
                return;
            }

            M.util.js_pending('filter_embedquestion-get_questions');
            t.lastCategory = $('select#id_categoryidnumber').val();

            if (t.lastCategory === '') {
                t.updateChoices([]);
            } else {
                Ajax.call([{
                    methodname: 'filter_embedquestion_get_sharable_question_choices',
                    args: {courseid: $('input[name=courseid]').val(), categoryidnumber: t.lastCategory}
                }])[0].done(t.updateChoices);
            }
        },

        /**
         * Update the contents of the Question select with the results of the AJAX call.
         *
         * @param {Array} response - array of options, each has fields value and label.
         */
        updateChoices: function(response) {
            var select = $('select#id_questionidnumber');

            select.empty();
            $(response).each(function(index, option) {
                select.append('<option value="' + option.value + '">' + option.label + '</option>');
            });
            M.util.js_complete('filter_embedquestion-get_questions');
        }
    };
    return t;
});
