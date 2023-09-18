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

namespace filter_embedquestion;

/**
 * Helper methods for getting the secure tokens.
 *
 * @package   filter_embedquestion
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class token {

    /**
     * Compute the security token used to validate the embedding code.
     *
     * @param embed_id $embedid the embed code.
     * @return string the security token.
     */
    public static function make_secret_token(embed_id $embedid): string {
        $secret = get_config('filter_embedquestion', 'secret');
        return hash('sha256', $embedid . '#embed#' . $secret);
    }

    /**
     * Helper used by {@see add_iframe_token_to_url()}.
     *
     * @param string $otherurlparams the URL params to 'sign'.
     * @return string the security token.
     */
    protected static function make_iframe_token(string $otherurlparams): string {
        $secret = get_config('filter_embedquestion', 'secret');
        return hash('sha256', $otherurlparams . '#iframe#' . $secret);
    }

    /**
     * Do not call this directly. It is expected that this will only be called by {@see utils::get_show_url()}.
     *
     * @param \moodle_url $url The URL to add the token to.
     */
    public static function add_iframe_token_to_url(\moodle_url $url): void {
        $url->param('token', self::make_iframe_token($url->get_query_string(false)));
    }

    /**
     * Check whether a token matches using any of the authorised keys.
     *
     * @param string $token the security token to be verified.
     * @param embed_id $embedid the embed code.
     * @return bool if authorized then true, otherwise false.
     */
    public static function is_authorized_secret_token($token, embed_id $embedid): bool {
        $authorizedsecrets = get_config('filter_embedquestion', 'authorizedsecrets');
        $authorizedsecrets = preg_split('~\s+~', $authorizedsecrets, -1, PREG_SPLIT_NO_EMPTY);
        array_unshift($authorizedsecrets, get_config('filter_embedquestion', 'secret'));

        foreach ($authorizedsecrets as $item) {
            if ($token === hash('sha256', $embedid . '#embed#' . $item)) {
                return true;
            }
        }

        return false;
    }
}
