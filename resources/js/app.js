import './bootstrap';

import Alpine from 'alpinejs';
import { applyInboxJsonResponse, inboxScrollToEnd, initInboxLive } from './inbox-live';
import { initNotifications } from './notifications';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.data('inboxPage', (config) => ({
        mobileListOpen: config.mobileListOpen,
        listAssignee: config.listAssignee ?? 'all',
        listAccount: config.listAccount ?? '',
        replyError: '',
        listClickBound: null,
        /** Do not name this `init` — Alpine reserves `init` and behavior differs from x-init. */
        inboxStart() {
            inboxScrollToEnd();
            this.listClickBound = (e) => {
                if (e.target.closest('a.js-inbox-thread-link')) {
                    this.mobileListOpen = false;
                }
            };
            this.$el.addEventListener('click', this.listClickBound);
        },
        destroy() {
            if (this.listClickBound && this.$el) {
                this.$el.removeEventListener('click', this.listClickBound);
            }
            this.listClickBound = null;
        },
        scrollToEnd() {
            inboxScrollToEnd();
        },
        async sendReply(event) {
            const form = event.target;
            if (!form || form.tagName !== 'FORM') {
                return;
            }
            event.preventDefault();
            this.replyError = '';
            const ta = form.querySelector('textarea[name="body"]');
            const fd = new FormData(form);
            fd.append('sync_list', '1');
            fd.append('list_assignee', this.listAssignee || 'all');
            if (this.listAccount) {
                fd.append('list_account', String(this.listAccount));
            }
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: fd,
                    cache: 'no-store',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                let data = {};
                try {
                    data = await res.json();
                } catch {
                    data = {};
                }
                if (!res.ok) {
                    const bodyErr = data.errors?.body;
                    if (Array.isArray(bodyErr) && bodyErr.length) {
                        this.replyError = bodyErr[0];
                    } else if (typeof bodyErr === 'string') {
                        this.replyError = bodyErr;
                    } else if (data.message) {
                        this.replyError = data.message;
                    } else {
                        this.replyError = 'Send failed';
                    }
                    return;
                }
                applyInboxJsonResponse(data, true);
                if (ta) {
                    ta.value = '';
                    ta.style.height = 'auto';
                }
            } catch {
                this.replyError = 'Send failed';
            }
        },
    }));
});

Alpine.start();
initInboxLive();

document.addEventListener('DOMContentLoaded', () => initNotifications());
