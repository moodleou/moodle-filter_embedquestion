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

namespace filter_embedquestion\output;

use filter_embedquestion\embed_id;
use renderer_base;

/**
 * Represents the iframe that embeds a question.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class embed_iframe implements \renderable, \templatable {
    /** @var \moodle_url for the iframe src attribute. */
    public $showquestionurl;

    /** @var string Accessibility text for the iframe. */
    public $iframedescription = '';

    /**
     * The error_message constructor.
     *
     * @param \moodle_url $showquestionurl The URL of the script to show in the iframe.
     * @param string $iframedescription The contents of iframe description.
     */
    public function __construct(\moodle_url $showquestionurl, string $iframedescription) {
        $this->showquestionurl = $showquestionurl;
        $this->iframedescription = $iframedescription;
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $data = [
            'showquestionurl' => $this->showquestionurl,
            'name' => null,
            'iframedescription' => format_string($this->iframedescription),
            'embedid' => (new embed_id($this->showquestionurl->param('catid'),
                    $this->showquestionurl->param('qid'),
                    $this->showquestionurl->param('questionbankidnumber'),
                    $this->showquestionurl->param('courseshortname')))->to_html_id(),
        ];
        if (defined('BEHAT_SITE_RUNNING')) {
            $data['name'] = 'filter_embedquestion-iframe';
        }
        return $data;
    }
}
