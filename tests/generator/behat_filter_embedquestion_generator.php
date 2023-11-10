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
 * Behat data generator for filter_embedquestion.
 *
 * @package   filter_embedquestion
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_filter_embedquestion_generator extends behat_generator_base {

    /**
     * Get a list of the entities that can be created for this component.
     *
     * @return array[] entity name => information about how to generate.
     */
    protected function get_creatable_entities(): array {
        $entities = [
            'Pages with embedded question' => [
                'singular' => 'Page with embedded question',
                'datagenerator' => 'embeddedpage',
                'required' => ['name', 'idnumber', 'course', 'question'],
                'switchids' => ['course' => 'course'],
            ],
        ];

        return $entities;
    }

    /**
     * Add an page with embedded question in the content
     *
     * @param array $data
     * @return void
     */
    protected function process_embeddedpage($data) {
        /** @var filter_embedquestion_generator $filterembedquestiongenerator */
        $filterembedquestiongenerator = behat_util::get_data_generator()->get_plugin_generator('filter_embedquestion');
        /** @var mod_page_generator $modpagegenerator */
        $modpagegenerator = behat_util::get_data_generator()->get_plugin_generator('mod_page');

        $question = $filterembedquestiongenerator->get_question_from_embed_id($data['question']);
        $embedcode = $filterembedquestiongenerator->get_embed_code($question);

        $data['content'] = $embedcode;
        $modpagegenerator->create_instance($data);
    }
}
