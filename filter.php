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
 * Embed question filter allows question bank questions to be used within other activities.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use filter_embedquestion\embed_id;
use filter_embedquestion\embed_location;
use filter_embedquestion\output\embed_iframe;
use filter_embedquestion\output\error_message;
use filter_embedquestion\question_options;
use filter_embedquestion\token;
use filter_embedquestion\utils;

/**
 * The filter implementation.
 *
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_embedquestion extends moodle_text_filter {
    /**
     * @var string Closing part of the embed token wrapper.
     */
    const STRING_PREFIX = '{Q{';
    /**
     * @var string Closing part of the embed token wrapper.
     */
    const STRING_SUFFIX = '}Q}';

    /**
     * @var \filter_embedquestion\output\renderer the renderer.
     */
    protected $renderer;

    /**
     * @var int the course id, derived from $this->context.
     */
    protected $courseid;

    /**
     * @var moodle_page page object
     */
    protected $page;

    public function setup($page, $context) {
        $this->page = $page;
    }

    /**
     * Get the regexp needed to extract embed codes from within some text.
     *
     * @return string the regular expression.
     */
    public static function get_filter_regexp(): string {
        return '~' . preg_quote(self::STRING_PREFIX, '~') .
                '((?:(?!' . preg_quote(self::STRING_SUFFIX, '~') . ').)*)' .
                preg_quote(self::STRING_SUFFIX, '~') . '~';
    }

    public function filter($text, array $options = []) {
        return preg_replace_callback(self::get_filter_regexp(),
                [$this, 'embed_question_callback'], $text);
    }

    /**
     * For use by the preg_replace_callback call above.
     *
     * @param array $matches the parts matched by the regular expression.
     *
     * @return string the replacement string.
     */
    public function embed_question_callback(array $matches): string {
        return $this->embed_question($matches[1]);
    }

    /**
     * Process the bit of the intput for embedding one question.
     *
     * @param string $embedcode the contents of the {Q{...}Q} delimiters.
     *
     * @return string HTML code for the iframe to display the question.
     */
    public function embed_question(string $embedcode): string {
        if ($this->renderer === null) {
            $this->renderer = $this->page->get_renderer('filter_embedquestion');
        }
        if ($this->courseid === null) {
            $this->courseid = utils::get_relevant_courseid($this->context);
        }
        if (isguestuser()) {
            return $this->display_error('noguests');
        }

        list($embedid, $params) = self::parse_embed_code($embedcode);
        if ($embedid === null) {
            return $this->display_error('invalidtoken');
        }

        $options = new question_options();
        $options->set_from_filter_options($params);

        $embedlocation = embed_location::make_from_page($this->page);

        $showquestionurl = utils::get_show_url($embedid, $embedlocation, $options);
        return $this->renderer->render(new embed_iframe($showquestionurl));
    }

    /**
     * Display an error since the question cannot be displayed.
     *
     * @param string $string the string to use for the message.
     * @param array|null $a any values needed by the strings.
     *
     * @return string HTML for the error.
     */
    protected function display_error(string $string, array $a = null): string {
        return $this->renderer->render(new error_message(
                get_string($string, 'filter_embedquestion', $a)));
    }

    /**
     * Parse an embed code, validate the token, and return the idnumbers and any options.
     * @param string $embedcode the embed code.
     * @return array an array with two elements: $embedid and $params.
     *      If the code is invalid, all elements are null.
     */
    public static function parse_embed_code(string $embedcode): array {
        $parts = explode('|', $embedcode);

        if (count($parts) < 2) {
            return [null, null];
        }

        $questioninfo = array_shift($parts);
        $token = array_pop($parts);

        if (strpos($questioninfo, '/') === false) {
            return [null, null];
        }

        list($categoryidnumber, $questionidnumber) = explode('/', $questioninfo, 2);
        $embedid = new embed_id($categoryidnumber, $questionidnumber);

        if ($token !== token::make_secret_token($embedid)) {
            return [null, null];
        }

        $params = self::parse_options($parts);
        if (!is_array($params)) {
            return [null, null];
        }

        return [$embedid, $params];
    }

    /**
     * Process the options, verifying that they are all of the form name=value.
     *
     * @param array $parts the individual 'name=options' strings.
     * @return array|null the parsed options, or false if they were malformed.
     */
    public static function parse_options(array $parts): ?array {
        $params = [];
        foreach ($parts as $part) {
            if (strpos($part, '=') === false) {
                return null;
            }
            list($name, $value) = explode('=', $part);
            $params[$name] = $value;
        }
        return $params;
    }
}
