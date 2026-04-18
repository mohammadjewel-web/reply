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
        pollLightInFlight: false,
        onVisBound: null,
        onFocusBound: null,
        onOnlineBound: null,
        onNotificationsRefreshedBound: null,
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
            this.onFocusBound = () => this.poll();
            window.addEventListener('focus', this.onFocusBound);
            this.onOnlineBound = () => this.poll();
            window.addEventListener('online', this.onOnlineBound);
            this.onNotificationsRefreshedBound = () => {
                this.poll({ syncList: false });
            };
            document.addEventListener('app:notifications-refreshed', this.onNotificationsRefreshedBound);
            this.pollTimer = setInterval(() => this.poll(), 2000);
            this.$nextTick(() => this.poll());
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
            if (this.onFocusBound) {
                window.removeEventListener('focus', this.onFocusBound);
            }
            this.onFocusBound = null;
            if (this.onOnlineBound) {
                window.removeEventListener('online', this.onOnlineBound);
            }
            this.onOnlineBound = null;
            if (this.onNotificationsRefreshedBound) {
                document.removeEventListener('app:notifications-refreshed', this.onNotificationsRefreshedBound);
            }
            this.onNotificationsRefreshedBound = null;
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
            }
            this.pollTimer = null;
        },
        scrollToEnd() {
            this.$nextTick(() => {
                const el = this.threadRoot();
                if (el) {
                    el.scrollTop = el.scrollHeight;
                }
            });
        },
        threadRoot() {
            return this.$refs.thread ?? this.$el.querySelector('[data-inbox-thread]');
        },
        threadHasMessageId(thread, mid) {
            if (!mid || !thread) {
                return false;
            }
            try {
                if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
                    return !!thread.querySelector(`[data-message-id="${CSS.escape(String(mid))}"]`);
                }
            } catch {
                /* ignore */
            }
            const safe = String(mid).replace(/["\\\n\r]/g, '');
            return !!thread.querySelector(`[data-message-id="${safe}"]`);
        },
        appendMessagesHtml(html) {
            const thread = this.threadRoot();
            if (!html || !thread) {
                return;
            }
            const trimmed = String(html).trim();
            if (!trimmed) {
                return;
            }
            const tpl = document.createElement('template');
            tpl.innerHTML = trimmed;
            const toAppend = [];
            for (const node of tpl.content.children) {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    continue;
                }
                const mid = node.getAttribute('data-message-id');
                if (this.threadHasMessageId(thread, mid)) {
                    continue;
                }
                toAppend.push(node);
            }
            if (!toAppend.length) {
                return;
            }
            const empty = thread.querySelector('[data-inbox-empty]');
            if (empty) {
                empty.remove();
            }
            for (const node of toAppend) {
                thread.appendChild(node);
            }
            this.scrollToEnd();
        },
        syncLastMessageIdFromPoll(data) {
            const raw = data?.last_message_id;
            if (raw === undefined || raw === null || raw === '') {
                return;
            }
            const n = Number(raw);
            if (!Number.isFinite(n)) {
                return;
            }
            this.lastMessageId = Math.max(this.lastMessageId, n);
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
            if (this.$refs.inboxHdrAvatarLetter) {
                this.$refs.inboxHdrAvatarLetter.textContent = contact.avatar ?? '';
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
        async poll(options = {}) {
            const syncList = options.syncList !== false;
            if (!this.conversationId || !this.pollUrl) {
                return;
            }
            if (syncList && this.pollInFlight) {
                return;
            }
            if (!syncList && this.pollLightInFlight) {
                return;
            }
            if (syncList) {
                this.pollInFlight = true;
            } else {
                this.pollLightInFlight = true;
            }
            try {
                const url = new URL(this.pollUrl, window.location.origin);
                url.searchParams.set('conversation', String(this.conversationId));
                url.searchParams.set('after', String(this.lastMessageId));
                if (syncList) {
                    url.searchParams.set('sync_list', '1');
                    url.searchParams.set('list_assignee', this.listAssignee || 'all');
                    if (this.listAccount) {
                        url.searchParams.set('list_account', String(this.listAccount));
                    }
                }
                const res = await fetch(url.toString(), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    return;
                }
                const raw = (await res.text()).replace(/^\uFEFF/, '');
                const bodyTrim = raw.trim();
                const ct = (res.headers.get('content-type') || '').toLowerCase();
                const looksJson =
                    ct.includes('application/json') ||
                    ct.includes('text/json') ||
                    bodyTrim.startsWith('{');
                if (!looksJson) {
                    return;
                }
                let data;
                try {
                    data = JSON.parse(raw);
                } catch {
                    return;
                }
                if (data.messages_html) {
                    this.appendMessagesHtml(data.messages_html);
                }
                this.syncLastMessageIdFromPoll(data);
                this.applyContactPatch(data.contact);
                if (syncList && data.list_html) {
                    this.applyListHtml(data.list_html);
                }
            } catch {
                /* ignore */
            } finally {
                if (syncList) {
                    this.pollInFlight = false;
                } else {
                    this.pollLightInFlight = false;
                }
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
                this.syncLastMessageIdFromPoll(data);
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
