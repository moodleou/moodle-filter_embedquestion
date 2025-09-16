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

define(['jquery', 'core/ajax', 'core/str', 'core/notification', 'core_user/repository'],
        function($, Ajax, Str, Notification, UserRepository) {
    var t = {
        /**
         * Initialise the handling.
         *
         * @param {string} defaultQbank - The default question bank to select, if any.
         */
        init: function(defaultQbank) {
            $('select#id_qbankcmid').on('change', t.qbankChanged);
            $('select#id_categoryidnumber').on('change', t.categoryChanged);

            t.lastQbank = $('select#id_qbankcmid').val();
            t.lastCategory = $('select#id_categoryidnumber').val();
            var selectedText = $('#id_qbankcmid option:selected').text();
            Str.get_string('currentbank', 'mod_quiz', selectedText)
                .then(function(string) {
                    $('#id_questionheadercontainer h5').text(string);
                    return;
                }).catch(Notification.exception);
            if (defaultQbank) {
                // If a default question bank is set, we need to trigger the change event to load the categories.
                $('select#id_qbankcmid').val(defaultQbank).trigger('change');
            }
        },

        /**
         * Used to track when the category really changes.
         */
        lastCategory: null,
        /**
         * Used to track when the question bank really changes.
         */
        lastQbank: null,

        /**
         * Source of data for Ajax element.
         */
        categoryChanged: function() {
            M.util.js_pending('filter_embedquestion-get_questions');
            t.lastCategory = $('select#id_categoryidnumber').val();
            if (t.lastCategory === '') {
                t.updateChoices([]);
            } else {
                Ajax.call([{
                    methodname: 'filter_embedquestion_get_sharable_question_choices',
                    args: {cmid: t.lastQbank, categoryidnumber: t.lastCategory},
                }])[0].done(t.updateChoices);
                $('select#id_questionidnumber').attr('disabled', false);
            }
        },

        /**
         * Source of data for Ajax element.
         */
        qbankChanged: function() {
            if ($('select#id_qbankcmid').val() === t.lastQbank) {
                return;
            }
            M.util.js_pending('filter_embedquestion-get_categories');
            t.lastQbank = $('select#id_qbankcmid').val();
            // Update the heading immediately when selection changes.
            var selectedText = $('#id_qbankcmid option:selected').text();
            Str.get_string('currentbank', 'mod_quiz', selectedText)
                .then(function(string) {
                    $('#id_questionheadercontainer h5').text(string);
                    return;
                }).catch(Notification.exception);
            var prefKey = 'filter_embedquestion_userdefaultqbank';
            var courseId = document.querySelector('input[name="courseid"]').value;
            var courseShortname = document.querySelector('input[name="courseshortname"]').value;
            if (courseShortname === '' || courseShortname === null) {
                UserRepository.getUserPreference(prefKey).then(current => {
                    let prefs = current ? JSON.parse(current) : {};
                    prefs[courseId] = t.lastQbank;
                    return UserRepository.setUserPreference(prefKey, JSON.stringify(prefs));
                }).catch(Notification.exception);
            }
            if ($('select#id_qbankcmid').val() === '') {
                t.updateCategories([]);
                M.util.js_pending('filter_embedquestion-get_questions');
                t.updateChoices([]);
            } else {
                Ajax.call([{
                    methodname: 'filter_embedquestion_get_sharable_categories_choices',
                    args: {cmid: t.lastQbank}
                }])[0].done(t.updateCategories);
                M.util.js_pending('filter_embedquestion-get_questions');
                t.updateChoices([]);
            }
        },

        /**
         * Update the contents of the Question select with the results of the AJAX call.
         *
         * @param {Array} response - array of options, each has fields value and label.
         */
        updateCategories: function(response) {
            var select = $('select#id_categoryidnumber');

            select.empty();
            $(response).each(function(index, option) {
                select.append('<option value="' + option.value + '">' + option.label + '</option>');
            });
            M.util.js_complete('filter_embedquestion-get_categories');
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
