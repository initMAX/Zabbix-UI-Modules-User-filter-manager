<?php

/**
 * @var CView $this
 */

?>//<script>
(() => window.user_filter_manager_form = new class {
    #csrf

    init({csrf}) {
        this.overlay = overlays_stack.end();
        this.dialogue = this.overlay.$dialogue[0];
        this.form = this.overlay.$dialogue.$body[0].querySelector('form');
        this.field = null;
        this.#csrf = csrf;

        this.initForm();
        this.initEvents();
        this.updateFieldsVisibility();
    }

    async run() {
        const response = await this.#doRequest('mod.userfiltermax.run');

        if (response !== null) {
            const data = this.getFormFields();
            const _response = await this.#doRequest(
                'id' in data ? 'mod.userfiltermax.update' : 'mod.userfiltermax.create'
            );

            if (_response !== null) {
                overlayDialogueDestroy(this.overlay.dialogueid);
                this.dialogue.dispatchEvent(new CustomEvent('dialogue.submit', {detail: response}));
            }
        }
    }

    async create() {
        const response = await this.#doRequest('mod.userfiltermax.create');

        if (response !== null) {
            overlayDialogueDestroy(this.overlay.dialogueid);
            this.dialogue.dispatchEvent(new CustomEvent('dialogue.submit', {detail: response}));
        }

        return response;
    }

    async update() {
        const response = await this.#doRequest('mod.userfiltermax.update');

        if (response !== null) {
            overlayDialogueDestroy(this.overlay.dialogueid);
            this.dialogue.dispatchEvent(new CustomEvent('dialogue.submit', {detail: response}));
        }

        return response;
    }

    async delete() {
        const response = await this.#doRequest('mod.userfiltermax.delete');

        if (response !== null) {
            overlayDialogueDestroy(this.overlay.dialogueid);
            this.dialogue.dispatchEvent(new CustomEvent('dialogue.submit', {detail: response}));
        }

        return response;
    }

    async #doRequest(action) {
        const fields = {...this.getFormFields(), [CSRF_TOKEN_NAME]: this.#csrf[action]};
        let json;

        this.overlay.setLoading();

        try {
            const response = await fetch(`?action=${action}`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(fields)
            });

            if (!response.ok) {
                throw new Error(response.statusText);
            }

            json = await response.json();

            if ('error' in json) {
                throw json;
            }
        }
        catch (exception) {
            json = null;
            this.clearFormMessages();

            let title, messages;

            if (typeof exception === 'object' && 'error' in exception) {
                title = exception.error.title;
                messages = exception.error.messages;
            }
            else {
                messages = [<?= json_encode(_('Unexpected server error.')) ?>];
            }

            this.form.parentNode.insertBefore(makeMessageBox('bad', messages, title)[0], this.form);
        }
        finally {
            this.overlay.unsetLoading();
        };

        return json;
    }

    initForm() {
        this.field = {
            source_user: this.form.querySelector('#source_userid'),
            source_filters: this.form.querySelector('#source_filters_'),
            target_usergroups: this.form.querySelector('#target_usergroupids_'),
            target_users: this.form.querySelector('#target_userids_')
        }
    }

    initEvents() {
        $(this.field.source_user).on('change', this.updateFieldsVisibility.bind(this));
        $(this.field.source_filters)
            .on('change', this.updateFieldsVisibility.bind(this))
            .multiSelect('getSelectButton')
            .addEventListener('click', this.openSourceUserFiltersPopup.bind(this));
    }

    getFormFields() {
        return getFormFields(this.form);
    }

    clearFormMessages() {
        for (const element of this.form.parentNode.querySelectorAll('.msg-good,.msg-bad,.msg-warning')) {
            element.remove();
        }
    }

    updateFieldsVisibility() {
        const user_set = $(this.field.source_user).multiSelect('getData').length > 0;
        const filters_set = $(this.field.source_filters).multiSelect('getData').length > 0;

        $(this.field.source_filters).multiSelect(user_set ? 'enable' : 'disable');
        $(this.field.target_usergroups).multiSelect(user_set && filters_set ? 'enable' : 'disable');
        $(this.field.target_users).multiSelect(user_set && filters_set? 'enable' : 'disable');
    }

    openSourceUserFiltersPopup() {
        const overlay = PopUp('mod.userfiltermax.popup.sourcefilter', {
            source_userid: $(this.field.source_user).multiSelect('getData')[0].id,
            source_filters: $(this.field.source_filters).multiSelect('getData').map(f => f.id)
        }, {dialogue_class: 'modal-popup-generic'});

        overlay.$dialogue[0].addEventListener('dialogue.submit', e => {
            const selectedids = e.detail;
            $(this.field.source_filters).multiSelect('addData', selectedids);
            this.updateFieldsVisibility();
        });
    }
})();
