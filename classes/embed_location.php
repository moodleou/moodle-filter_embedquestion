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
 * Simple class to represent a place where a question is embedded.
 *
 * @package   filter_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_embedquestion;
defined('MOODLE_INTERNAL') || die();


/**
 * Simple class to represent a place where a question is embedded.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class embed_location {

    /**
     * @var \context the context in which ths question is being shown.
     */
    public $context;

    /**
     * @var \moodle_url URL of the page where the question was shown.
     */
    public $pageurl;

    /**
     * @var string the name of the page where the question was shown, for information.
     */
    public $pagetitle;

    /**
     * Private constructor. Use one of the make methods to get an instance.
     *
     * @param \context $context location context.
     * @param \moodle_url $pageurl location url.
     * @param string $pagetitle location name.
     */
    private function __construct(\context $context, \moodle_url $pageurl, string $pagetitle) {
        $this->context = $context;
        $this->pageurl = $pageurl;
        $this->pagetitle = $pagetitle;
    }

    /**
     * Create an instance of this class from a Moodle page.
     *
     * This is what is used by the filter.
     *
     * @param \moodle_page $page that page we are in.
     * @return embed_location new instance.
     */
    public static function make_from_page(\moodle_page $page): embed_location {
        $title = (string) $page->title;
        if ($title === '') {
            $title = $page->context->get_context_name(false);
        }
        return new self($page->context, $page->url, $title);
    }

    /**
     * Create an instance of this class from URL parameters.
     *
     * This is what is used by by showquestion.php.
     *
     * @return embed_location new instance.
     */
    public static function make_from_url_params(): embed_location {
        return new self(
                \context::instance_by_id(required_param('contextid', PARAM_INT)),
                new \moodle_url(required_param('pageurl', PARAM_LOCALURL)),
                required_param('pagetitle', PARAM_TEXT));
    }

    /**
     * Should only be used by test code (@link report_embedquestion_generator}.
     *
     * Make an instance with specific properies.
     *
     * @param \context $context location context.
     * @param \moodle_url $pageurl location url.
     * @param string $pagetitle location name.
     * @return embed_location new instance.
     */
    public static function make_for_test(\context $context, \moodle_url $pageurl, string $pagetitle) {
        return new self($context, $pageurl, $pagetitle);
    }

    /**
     * Add parameters representing this location to a URL.
     *
     * @param \moodle_url $url the URL to add to.
     */
    public function add_params_to_url(\moodle_url $url): void {
        $url->param('contextid', $this->context->id);
        $url->param('pageurl', $this->pageurl->out_as_local_url(false));
        $url->param('pagetitle', $this->pagetitle);
    }

    /**
     * Get a description of where we are looking for questions, for use in error messages.
     *
     * @return string context name.
     */
    public function context_name_for_errors(): string {
        return \context_course::instance(
                utils::get_relevant_courseid($this->context))->get_context_name(false, true);
    }
}
