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

use filter_embedquestion\attempt;
use filter_embedquestion\embed_id;
use filter_embedquestion\embed_location;
use filter_embedquestion\question_options;
use filter_embedquestion\utils;

/**
 *  Embed question filter test data generator.
 *
 * @package   filter_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class filter_embedquestion_generator extends component_generator_base {

    /**
     * @var core_question_generator convenient reference to the question generator.
     */
    protected $questiongenerator;

    /**
     * @var int used to generate unique ids.
     */
    protected static $uniqueid = 1;

    /**
     * Constructor.
     *
     * @param testing_data_generator $datagenerator The data generator.
     */
    public function __construct(testing_data_generator $datagenerator) {
        parent::__construct($datagenerator);
        $this->questiongenerator = $this->datagenerator->get_plugin_generator('core_question');
    }

    /**
     * Use core question generator to create a question that is embeddable.
     *
     * That is, we ensure that the question has an idnumber, and that it is
     * in a category with an idnumber.
     *
     * Do not specify both isset($overrides['category'] and $categoryrecord.
     * (Generally, you don't want to specify either.)
     *
     * @param string $qtype as for {@see core_question_generator::create_question()}
     * @param string|null $which as for {@see core_question_generator::create_question()}
     * @param array|null $overrides as for {@see core_question_generator::create_question()}.
     * @param array $categoryrecord as for {@see core_question_generator::create_question_category()}.
     * @return stdClass the data for the newly created question.
     */
    public function create_embeddable_question(string $qtype, string|null $which = null,
            array|null $overrides = null, array $categoryrecord = []): stdClass {

        // Create the category, if one is not specified.
        if (!isset($overrides['category'])) {
            if (!isset($categoryrecord['idnumber'])) {
                $categoryrecord['idnumber'] = 'embeddablecat' . (self::$uniqueid++);
            }
            if (isset($categoryrecord['contextid'])) {
                if (context::instance_by_id($categoryrecord['contextid'])->contextlevel !== CONTEXT_MODULE) {
                    throw new coding_exception('Categorycontextid must refer to a module context.');
                }
            }
            $category = $this->questiongenerator->create_question_category($categoryrecord);
            $overrides['category'] = $category->id;
        } else if (!empty($categoryrecord)) {
            // Both this combination not allowed.
            throw new coding_exception('You cannot sepecify both the question category, ' .
                    'and details of a category to create.');
        }

        // Create the question.
        if (!isset($overrides['idnumber'])) {
            $overrides['idnumber'] = 'embeddableq' . (self::$uniqueid++);
        }
        return $this->questiongenerator->create_question($qtype, $which, $overrides);
    }

    /**
     * Get the embed id corresponding to a question.
     *
     * @param stdClass $question the question.
     * @return array embed_id and context.
     */
    public function get_embed_id_and_context(stdClass $question): array {
        global $DB;

        if ($question->idnumber === null || $question->idnumber === '') {
            throw new coding_exception('$question->idnumber must be set.');
        }

        $category = $DB->get_record('question_categories', ['id' => $question->category], '*', MUST_EXIST);
        if ($category->idnumber === null || $category->idnumber === '') {
            throw new coding_exception('Category idnumber must be set.');
        }

        $context = context::instance_by_id($category->contextid);

        if ($context->contextlevel !== CONTEXT_MODULE) {
            throw new coding_exception('Categorycontextid must refer to a module context.');
        }

        return [new embed_id($category->idnumber, $question->idnumber), $context];
    }

    /**
     * Get an embeddable question from its id.
     *
     * @param string $embedid string like 'catid/qid'.
     * @return stdClass $question the question object.
     */
    public function get_question_from_embed_id(string $embedid): stdClass {
        global $DB;
        [$categoryidnumber, $questionidnumber] = explode('/', $embedid);
        $categoryid = $DB->get_field('question_categories', 'id', ['idnumber' => $categoryidnumber], MUST_EXIST);
        return utils::get_question_by_idnumber($categoryid, $questionidnumber);
    }

    /**
     * Get the embed code that would be used to embed a question with default options.
     *
     * @param stdClass $question the question to embed.
     * @return string the embed code.
     */
    public function get_embed_code(stdClass $question) {
        [$embedid] = $this->get_embed_id_and_context($question);

        $fakeformdata = (object) [
            'categoryidnumber' => $embedid->categoryidnumber,
            'questionidnumber' => $embedid->questionidnumber,
            'questionbankidnumber' => $embedid->questionbankidnumber,
            'courseshortname' => $embedid->courseshortname,
        ];
        return question_options::get_embed_from_form_options($fakeformdata);
    }

    /**
     * Create an attempt at a given question by a given user.
     *
     * @param stdClass $question the question to attempt.
     * @param stdClass $user the user making the attempt.
     * @param string $response Response to submit. (Sent to the
     *      un_summarise_response method of the correspnoding question type).
     * @param context|null $attemptcontext the context in which the attempt should be created.
     * @param null $pagename Page name
     * @param int $slot Slot no
     * @param bool $isfinish Finish the attempt or not.
     * @return attempt the newly generated attempt.
     */
    public function create_attempt_at_embedded_question(stdClass $question,
            stdClass $user, string $response, context|null $attemptcontext = null, $pagename = null, $slot = 1,
            $isfinish = true): attempt {
        global $USER, $CFG;

        [$embedid, $qbankcontext] = $this->get_embed_id_and_context($question);

        if ($attemptcontext) {
            $context = $attemptcontext;
        } else {
            $context = $qbankcontext;
        }
        if ($pagename) {
            $pn = explode(':', $pagename);
            if (count($pn) !== 2) {
                throw new coding_exception('The pagename must consist of two part in P1:P2 format: ' .
                    'In course context: P1 is the word \'Course\' and P2 is the course full name. ' .
                    'In activity context: P1 is the course shortname and P2 is the activity name. ');
            }
        }

        if ($pagename === null) {
            $embedlocation = embed_location::make_for_test($context, $context->get_url(), 'Test embed location');
        } else {
            $embedlocation = embed_location::make_for_test($context, $context->get_url(), $pagename);
        }
        $options = new question_options();
        $options->behaviour = 'immediatefeedback';

        $attempt = new attempt($embedid, $embedlocation, $user, $options);
        $this->verify_attempt_valid($attempt);

        /* Nasty hack to make the question_attempt run correctly, because question/engine/questionattempt.php->start()
        will use the current $USER if the $userid param will not be provided. */
        $currentuser = $USER;
        $USER = $user;
        // End nasty hack.

        $attempt->find_or_create_attempt();
        $this->verify_attempt_valid($attempt);

        if ($slot > 1) {
            // Create a new slot for current attempt.
            $attempt->start_new_attempt_at_question($attempt->get_question_usage());
        }

        if ($isfinish) {
            if ($question->qtype == 'recordrtc') {
                $postdata = $this->get_simulated_post_data_for_recordrtc_qtype($attempt->get_question_usage(), $slot);
            } else if ($question->qtype == 'essay') {
                $postdata = $this->get_simulated_post_data_for_essay_qtype($attempt->get_question_usage(), $slot, $response);
            } else {
                $postdata = $this->questiongenerator->get_simulated_post_data_for_questions_in_usage($attempt->get_question_usage(),
                        [$slot => $response], true);
            }
            // Only submit the attempt if needed.
            $attempt->process_submitted_actions($postdata);
        }

        // Set the current user back to the original.
        $USER = $currentuser;

        return $attempt;
    }

    /**
     * Helper: throw an exception if attempt is not valid.
     *
     * @param attempt $attempt the attempt to check.
     */
    protected function verify_attempt_valid(attempt $attempt): void {
        if (!$attempt->is_valid()) {
            throw new coding_exception($attempt->get_problem_description());
        }
    }

    /**
     * Helper: Convert an array of data destined for one question to the equivalent POST data.
     *
     * @param question_usage_by_activity $quba Question usage by activity
     * @param int $slot Slot
     * @param array $data Data to process
     * @return array
     */
    protected function process_response_data_to_post(question_usage_by_activity $quba, int $slot, array $data): array {
        $prefix = $quba->get_field_prefix($slot);

        $fulldata = [
            'slots' => $slot,
            $prefix . ':sequencecheck' => $quba->get_question_attempt($slot)->get_sequence_check_count(),
        ];

        foreach ($data as $name => $value) {
            $fulldata[$prefix . $name] = $value;
        }

        return $fulldata;
    }

    /**
     * Helper: Store a test file with a given name and contents in a draft file area.
     *
     * @param int $usercontextid User context id.
     * @param int $draftitemid Draft item id.
     * @param string $filename Filename.
     * @param string $contents File contents.
     */
    protected function save_file_to_draft_area(int $usercontextid, int $draftitemid, string $filename, string $contents): void {
        $fs = get_file_storage();

        $filerecord = new stdClass();
        $filerecord->contextid = $usercontextid;
        $filerecord->component = 'user';
        $filerecord->filearea = 'draft';
        $filerecord->itemid = $draftitemid;
        $filerecord->filepath = '/';
        $filerecord->filename = $filename;

        $fs->create_file_from_string($filerecord, $contents);
    }

    /**
     * Helper: This method can construct what the post data would be to simulate a user submitting responses to essay question type
     * within a question usage.
     *
     * @param question_usage_by_activity $quba Question usage by activity
     * @param int $slot Slot
     * @param string $response Response data.
     * @return array
     */
    protected function get_simulated_post_data_for_essay_qtype(question_usage_by_activity $quba, int $slot,
            string $response): array {
        global $USER, $PAGE;

        // Required to init a text editor.
        $PAGE->set_url('/');

        $usercontextid = context_user::instance($USER->id)->id;
        $currentoutput = $quba->render_question($slot, new question_display_options());

        if (!preg_match('/env=editor&amp;.*?itemid=(\d+)&amp;/', $currentoutput, $matches)) {
            throw new coding_exception('Editor draft item id not found.');
        }

        $editordraftid = $matches[1];

        if (!preg_match('/env=filemanager&amp;action=browse&amp;.*?itemid=(\d+)&amp;/', $currentoutput, $matches)) {
            throw new coding_exception('File manager draft item id not found.');
        }
        $attachementsdraftid = $matches[1];

        $this->save_file_to_draft_area($usercontextid, $attachementsdraftid, 'greeting.txt', $response);

        $userresponse = [
            'answer' => $response,
            'answerformat' => FORMAT_HTML,
            'answer:itemid' => $editordraftid,
            'attachments' => $attachementsdraftid,
        ];

        return $this->process_response_data_to_post($quba, $slot, $userresponse);
    }

    /**
     * Helper: This method can construct what the post data would be to simulate a user submitting responses to a/v recording
     * question type within a question usage.
     *
     * @param question_usage_by_activity $quba Question usage by activity
     * @param int $slot Slot
     * @return array
     */
    protected function get_simulated_post_data_for_recordrtc_qtype(question_usage_by_activity $quba, int $slot): array {
        $currentoutput = $quba->render_question($slot, new question_display_options());

        if (!preg_match('/name="' . preg_quote($quba->get_question_attempt($slot)->get_qt_field_name('recording')) .
                '" value="(\d+)"/', $currentoutput, $matches)) {
            throw new coding_exception('Draft item id not found.');
        }
        $userresponse = [
            'recording' => $matches[1],
            '-submit' => '1',
            '-selfcomment' => 'Sounds OK',
            '-selfcommentformat' => FORMAT_HTML,
            '-stars' => '4',
            '-rate' => '1',
        ];

        qtype_recordrtc_test_helper::add_recording_to_draft_area($userresponse['recording'], 'moodle-tim.ogg', 'recording.ogg');

        return $this->process_response_data_to_post($quba, $slot, $userresponse);
    }
}
