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
 * Embedquestion filter language strings.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


$string['attemptoptions'] = 'Attempt options';
$string['corruptattempt'] = 'Your previous attempt at a question here has stopped working. If you click continue, it will be removed and a new attempt created.';
$string['defaultsheading'] = 'Default options for embedding questions';
$string['defaultsheading_desc'] = 'These are the defaults for the options that control how embedded questions display and function. These are the values that will be used if a particular option is not set when the question is embedded.';
$string['defaultx'] = 'Default ({$a})';
$string['displayoptions'] = 'Display options';
$string['embedquestion'] = 'Embed question';
$string['errormaxmarknumber'] = 'The maximum mark must be a number.';
$string['errornopermissions'] = 'You do not have permission to embed this question.';
$string['errorunknownquestion'] = 'Unknown, or unsharable question.';
$string['errorvariantoutofrange'] = 'Variant number must be a positive integer at most {$a}.';
$string['errorvariantformat'] = 'Variant number must be a positive integer.';
$string['filtername'] = 'Embed questions';
$string['generalfeedback_desc'] = 'Whether the general feedback should be shown by default in embedded questions.';
$string['howquestionbehaves'] = 'How the question behaves';
$string['howquestionbehaves_desc'] = 'The default behaviour to use for embedded questions.';
$string['iframetitle'] = 'Embedded question';
$string['invalidcategory'] = 'The category with idnumber "{$a->catid}" does not exist in "{$a->contextname}".';
$string['invalidemptycategory'] = 'The category "{$a->catname}" in "{$a->contextname}" does not contain any embeddable questions.';
$string['invalidquestion'] = 'The question with idnumber "{$a->qid}" does not exist in category "{$a->catname}".';
$string['invalidrandomquestion'] = 'Cannot generate a random question from the question category "{$a}".';
$string['invalidtoken'] = 'This question may not be embedded here.';
$string['markdp_desc'] = 'The default number of digits that should be shown after the decimal point when displaying grades in embedded questions.';
$string['markedoutof'] = 'Marked out of';
$string['marks_desc'] = 'Whether numerical mark information should be shown by default in embedded questions.';
$string['nameandcount'] = '{$a->name} ({$a->count})';
$string['nameandidnumber'] = '{$a->name} [{$a->idnumber}]';
$string['nameidnumberandcount'] = '{$a->name} [{$a->idnumber}] ({$a->count})';
$string['noguests'] = 'Guest users do not have permission to interact with embedded questions.';
$string['notyourattempt'] = 'This is not your attempt.';
$string['pluginname'] = 'Embed questions';
$string['privacy:metadata'] = 'The Embed questions filter does not store any personal data.';
$string['questionidnumber'] = 'Question id number';
$string['responsehistory_desc'] = 'Whether the response history table should be shown by default for embedded questions.';
$string['restart'] = 'Start again';
$string['rightanswer_desc'] = 'Whether the automatically generated display of the right answer is shown by default for embedded questions. We recommend that you do not used this, but instead encoruage question authors to explain the right answer in the general feedback.';
$string['specificfeedback_desc'] = 'Whether the feedback specific to the student\'s response should be shown by default in embedded questions.';
$string['chooserandomly'] = 'Choose an embeddable question from this category randomly';
$string['taskcleanup'] = 'Clean up old embedded question attempts';
$string['title'] = 'Embedded question';
$string['warningfilteroffglobally'] = 'Warning: the embed question filter is disabled in the site-wide filter settings.';
$string['warningfilteroffhere'] = 'Warning: the the embed question filter is turned off in this course.';
$string['whethercorrect_desc'] = 'Whether students should be given indications of whether their response was correct in embedded questions. This covers both the textual description \'Correct\', \'Partially correct\' or \'Incorrect\', and any coloured highlighting that conveys the same information.';
$string['whichquestion'] = 'Which question';
