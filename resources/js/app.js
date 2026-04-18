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
        pollTimer: null,
        pollInFlight: false,
        onVisBound: null,
        init() {
            this.$nextTick(() => this.scrollToEnd());
            if (!this.conversationId || !this.pollUrl) {
                return;
            }
            this.pollTimer = setInterval(() => this.poll(), 2800);
            this.onVisBound = () => {
                if (document.visibilityState === 'visible') {
                    this.poll();
                }
            };
            document.addEventListener('visibilitychange', this.onVisBound);
        },
        scrollToEnd() {
            this.$nextTick(() => {
                const el = this.$refs.thread;
                if (el) {
                    el.scrollTop = el.scrollHeight;
                }
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
                const res = await fetch(url.toString(), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    return;
                }
                const data = await res.json();
                if (data.messages_html && this.$refs.thread) {
                    const empty = this.$refs.thread.querySelector('[data-inbox-empty]');
                    if (empty) {
                        empty.remove();
                    }
                    const tpl = document.createElement('template');
                    tpl.innerHTML = data.messages_html.trim();
                    const nodes = Array.from(tpl.content.children);
                    if (nodes.length) {
                        this.$refs.thread.append(...nodes);
                    }
                    this.scrollToEnd();
                }
                if (typeof data.last_message_id === 'number') {
                    this.lastMessageId = data.last_message_id;
                }
                if (data.contact) {
                    if (this.$refs.inboxHdrTitle) {
                        this.$refs.inboxHdrTitle.textContent = data.contact.title ?? '';
                    }
                    if (this.$refs.inboxHdrSub) {
                        this.$refs.inboxHdrSub.textContent = data.contact.subtitle ?? '';
                    }
                    if (this.$refs.inboxHdrAvatar) {
                        this.$refs.inboxHdrAvatar.textContent = data.contact.avatar ?? '';
                    }
                }
            } catch {
                /* ignore */
            } finally {
                this.pollInFlight = false;
            }
        },
    }));
});

Alpine.start();

document.addEventListener('DOMContentLoaded', () => initNotifications());
