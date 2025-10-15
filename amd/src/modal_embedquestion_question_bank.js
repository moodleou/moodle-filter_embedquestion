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
 * Contain the logic for the question bank modal.
 *
 * @module     filter_embedquestion/modal_embedquestion_question_bank
 * @copyright  2025  The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Modal from 'mod_quiz/add_question_modal';
import * as Fragment from 'core/fragment';
import {getString} from 'core/str';
import AutoComplete from 'core/form-autocomplete';

const SELECTORS = {
    SWITCH_TO_OTHER_BANK: 'button[data-action="switch-question-bank"]',
    BANK_SEARCH: '#searchbanks',
    NEW_BANKMOD_ID: 'data-newmodid',
    ANCHOR: 'a[href]',
    SORTERS: '.sorters',
    GO_BACK_BUTTON: 'button[data-action="go-back"]',
};

/**
 * Class representing a modal for selecting a question bank to embed questions from.
 */
export class ModalEmbedQuestionQuestionBank extends Modal {
    static TYPE = 'filter_embedquestion-question-bank';

    configure(modalConfig) {
        // Add question modals are always large.
        modalConfig.large = true;

        // Always show on creation.
        modalConfig.show = true;
        modalConfig.removeOnClose = true;

        // Apply question modal configuration.
        this.setContextId(modalConfig.contextId);
        this.setAddOnPageId(modalConfig.addOnPage);
        this.courseId = modalConfig.courseId;
        this.bankCmId = modalConfig.bankCmId;
        // Store the original title of the modal, so we can revert back to it once we have switched to another bank.
        this.originalTitle = modalConfig.title;
        this.currentEditor = modalConfig.editor;
        // Apply standard configuration.
        super.configure(modalConfig);
    }

    /**
     * Show the modal and load the content for switching question banks.
     *
     * @method show
     */
    show() {
        this.handleSwitchBankContentReload(SELECTORS.BANK_SEARCH);
        return super.show(this);
    }

    /**
     * Switch to the embed question modal for a specific question bank.
     * This will destroy the current modal and dispatch an event to switch to the new modal.
     *
     * @param {String} bankCmid - The course module ID of the question bank to switch to.
     * @method fireQbankSelectedEvent
     */
    fireQbankSelectedEvent(bankCmid) {
        this.destroy();
        const event = new CustomEvent('filter_embedquestion:qbank_selected', {
            detail: {bankCmid: bankCmid, editor: this.currentEditor},
        });
        document.dispatchEvent(event);
    }

    /**
     * Set up all the event handling for the modal.
     *
     * @method registerEventListeners
     */
    registerEventListeners() {
        // Apply parent event listeners.
        super.registerEventListeners(this);

        this.getModal().on('click', SELECTORS.ANCHOR, (e) => {
            const anchorElement = e.currentTarget;
            e.preventDefault();
            this.fireQbankSelectedEvent(anchorElement.getAttribute(SELECTORS.NEW_BANKMOD_ID));
        });

        this.getModal().on('click', SELECTORS.GO_BACK_BUTTON, (e) => {
            e.preventDefault();
            this.fireQbankSelectedEvent(e.currentTarget.value);
        });
    }

    /**
     * Update the modal with a list of banks to switch to and enhance the standard selects to Autocomplete fields.
     *
     * @param {String} Selector for the original select element.
     * @return {Promise} Modal.
     */
    async handleSwitchBankContentReload(Selector) {
        this.setTitle(getString('selectquestionbank', 'mod_quiz'));

        // Create a 'Go back' button and set it in the footer.
        const el = document.createElement('button');
        el.classList.add('btn', 'btn-primary');
        el.textContent = await getString('gobacktoquiz', 'mod_quiz');
        el.setAttribute('data-action', 'go-back');
        el.setAttribute('value', this.bankCmId);
        this.setFooter(el);

        this.setBody(
            Fragment.loadFragment(
                'filter_embedquestion',
                'switch_question_bank',
                this.getContextId(),
                {
                    'courseid': this.courseId,
                })
        );
        const placeholder = await getString('searchbyname', 'mod_quiz');
        await this.getBodyPromise();
        await AutoComplete.enhance(
            Selector,
            false,
            'core_question/question_banks_datasource',
            placeholder,
            false,
            true,
            '',
            true
        );

        // Hide the selection element as we don't need it.
        document.querySelector('.search-banks .form-autocomplete-selection')?.classList.add('d-none');
        // Add a change listener to get the selected value.
        const bankSearchEl = document.querySelector(Selector);
        if (bankSearchEl) {
            bankSearchEl.addEventListener('change', (e) => {
                // This will be the chosen qbankCmid.
                const selectedValue = e.target.value;
                if (selectedValue > 0) {
                    this.fireQbankSelectedEvent(selectedValue);
                }
            });
        }
        return this;
    }
}

export default {
    ModalEmbedQuestionQuestionBank,
};
ModalEmbedQuestionQuestionBank.registerModalType();