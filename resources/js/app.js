import './bootstrap';

import Alpine from 'alpinejs';
import { initNotifications } from './notifications';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.data('inboxPage', (config) => ({
        mobileListOpen: config.mobileListOpen,
        lastMessageId: config.lastMessageId,
        conversationId: config.conversationId,
        pollUrl: config.pollUrl,
        listAssignee: config.listAssignee ?? 'all',
        listAccount: config.listAccount ?? '',
        replyError: '',
        pollTimer: null,
        pollInFlight: false,
        onVisBound: null,
        listClickBound: null,
        /** Do not name this `init` — Alpine reserves `init` and behavior differs from x-init. */
        inboxStart() {
            this.$nextTick(() => this.scrollToEnd());
            this.listClickBound = (e) => {
                if (e.target.closest('a.js-inbox-thread-link')) {
                    this.mobileListOpen = false;
                }
            };
            this.$el.addEventListener('click', this.listClickBound);
            if (!this.conversationId || !this.pollUrl) {
                return;
            }
            this.onVisBound = () => {
                if (document.visibilityState === 'visible') {
                    this.poll();
                }
            };
            document.addEventListener('visibilitychange', this.onVisBound);
            this.pollTimer = setInterval(() => this.poll(), 2500);
            setTimeout(() => this.poll(), 400);
        },
        destroy() {
            if (this.listClickBound && this.$el) {
                this.$el.removeEventListener('click', this.listClickBound);
            }
            this.listClickBound = null;
            if (this.onVisBound) {
                document.removeEventListener('visibilitychange', this.onVisBound);
            }
            this.onVisBound = null;
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
            }
            this.pollTimer = null;
        },
        scrollToEnd() {
            this.$nextTick(() => {
                const el = this.$refs.thread;
                if (el) {
                    el.scrollTop = el.scrollHeight;
                }
            });
        },
        appendMessagesHtml(html) {
            if (!html || !this.$refs.thread) {
                return;
            }
            const empty = this.$refs.thread.querySelector('[data-inbox-empty]');
            if (empty) {
                empty.remove();
            }
            const tpl = document.createElement('template');
            tpl.innerHTML = String(html).trim();
            const nodes = Array.from(tpl.content.children);
            if (nodes.length) {
                this.$refs.thread.append(...nodes);
            }
            this.scrollToEnd();
        },
        applyContactPatch(contact) {
            if (!contact) {
                return;
            }
            if (this.$refs.inboxHdrTitle) {
                this.$refs.inboxHdrTitle.textContent = contact.title ?? '';
            }
            if (this.$refs.inboxHdrSub) {
                this.$refs.inboxHdrSub.textContent = contact.subtitle ?? '';
            }
            if (this.$refs.inboxHdrAvatar) {
                this.$refs.inboxHdrAvatar.textContent = contact.avatar ?? '';
            }
        },
        applyListHtml(html) {
            if (html === undefined || html === null || String(html).trim() === '') {
                return;
            }
            const h = String(html).trim();
            this.$el.querySelectorAll('.js-inbox-conversation-list').forEach((el) => {
                el.innerHTML = h;
            });
        },
        async poll() {
            if (!this.conversationId || !this.pollUrl || document.visibilityState !== 'visible' || this.pollInFlight) {
                return;
            }
            this.pollInFlight = true;
            try {
                const url = new URL(this.pollUrl, window.location.origin);
                url.searchParams.set('conversation', String(this.conversationId));
                url.searchParams.set('after', String(this.lastMessageId));
                url.searchParams.set('sync_list', '1');
                url.searchParams.set('list_assignee', this.listAssignee || 'all');
                if (this.listAccount) {
                    url.searchParams.set('list_account', String(this.listAccount));
                }
                const res = await fetch(url.toString(), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    return;
                }
                const data = await res.json();
                if (data.messages_html) {
                    this.appendMessagesHtml(data.messages_html);
                }
                if (typeof data.last_message_id === 'number') {
                    this.lastMessageId = data.last_message_id;
                }
                this.applyContactPatch(data.contact);
                if (data.list_html) {
                    this.applyListHtml(data.list_html);
                }
            } catch {
                /* ignore */
            } finally {
                this.pollInFlight = false;
            }
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
                if (data.messages_html) {
                    this.appendMessagesHtml(data.messages_html);
                }
                if (typeof data.last_message_id === 'number') {
                    this.lastMessageId = data.last_message_id;
                }
                this.applyContactPatch(data.contact);
                if (data.list_html) {
                    this.applyListHtml(data.list_html);
                }
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

document.addEventListener('DOMContentLoaded', () => initNotifications());
