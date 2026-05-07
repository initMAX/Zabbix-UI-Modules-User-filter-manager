<script type="text/javascript">const view = new class {

    #csrf
    #messages

    init({messages, csrf}) {
        this.#messages = messages;
        this.#csrf = csrf;
        this.#initEventListeners();
    }

    #initEventListeners() {
        document.querySelector('.js-user-filter-create').addEventListener('click',
            e => this.#openCreateFilterModal(e.target)
        );

        for (const link of document.querySelectorAll('.js-user-filter-edit')) {
            link.addEventListener('click', e => this.#openEditFilterModal(e.target));
        }

        document.querySelector('[type="checkbox"][name="all_userfilters"]').addEventListener('click', e => {
            checkAll('userfilters', 'all_userfilters', 'userfilters');
        });

        document.querySelector('button.js-selected-delete').addEventListener('click', e => {
            const ids = Object.keys(chkbxRange.getSelectedIds());
            const confirm_message = this.#messages['confirm.delete'][ids.length > 1 ? 1 : 0];

            if (!window.confirm(confirm_message)) {
                return;
            }

            fetch(`?action=mod.userfiltermax.delete`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ids, [CSRF_TOKEN_NAME]: this.#csrf['mod.userfiltermax.delete']})
            })
                .then(response => response.json())
                .then(response => this.#onSuccess({detail: response}));
        });

        document.querySelector('button.js-selected-run').addEventListener('click', e => {
            const ids = Object.keys(chkbxRange.getSelectedIds());
            const confirm_message = this.#messages['confirm.run'][ids.length > 1 ? 1 : 0];

            if (!window.confirm(confirm_message)) {
                return;
            }

            fetch(`?action=mod.userfiltermax.run`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ids, [CSRF_TOKEN_NAME]: this.#csrf['mod.userfiltermax.run']})
            })
                .then(response => response.json())
                .then(response => this.#onSuccess({detail: response}));
        });
    }

    #openCreateFilterModal(target) {
        const overlay = PopUp('mod.userfiltermax.form', {}, {
            dialogueid: 'user-filter-manager-form',
            dialogue_class: 'modal-popup-static',
            trigger_element: target
        });

        overlay.$dialogue[0].addEventListener('dialogue.submit', e => this.#onSuccess(e));
    }

    #openEditFilterModal(target) {
        const overlay = PopUp('mod.userfiltermax.form', {id: target.getAttribute('data-id')}, {
            dialogueid: 'user-filter-manager-form',
            dialogue_class: 'modal-popup-static',
            trigger_element: target
        });

        overlay.$dialogue[0].addEventListener('dialogue.submit', e => this.#onSuccess(e));
    }

    #onSuccess(e) {
        let new_href = location.href;
        const response = e.detail;

        if ('error' in response) {
            if ('title' in response.error) {
                postMessageError(response.error.title);
            }

            postMessageDetails('error', response.error.messages);
        }
        else if ('success' in response) {
            chkbxRange.clearSelectedOnFilterChange();
            postMessageOk(response.success.title);

            if ('messages' in response.success) {
                postMessageDetails('success', response.success.messages);
            }
        }

        location.href = new_href;
    }
}
</script>
