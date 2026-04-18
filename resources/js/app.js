import './bootstrap';

import Alpine from 'alpinejs';
import { applyInboxJsonResponse, inboxScrollToEnd, initInboxLive } from './inbox-live';
import { initNotifications } from './notifications';

window.Alpine = Alpine;

const CHAT_EMOJIS = [
    '😀', '😃', '😄', '😁', '😅', '😂', '🤣', '😊', '😇', '🙂', '😉', '😍', '🥰', '😘', '😋', '😛', '😜', '🤪',
    '😎', '🤩', '🥳', '😏', '😌', '😢', '😭', '😤', '😠', '🤝', '👍', '👎', '👏', '🙏', '🔥', '✨', '❤️', '💯', '✅',
    '⭐', '🎉', '🙌', '💬', '📷', '🎤', '🎵', '☀️', '🌙', '⚡', '📎',
];

document.addEventListener('alpine:init', () => {
    Alpine.store('layout', {
        sidebarOpen: false,
    });

    Alpine.data('inboxPage', (config) => ({
        mobileListOpen: config.mobileListOpen,
        listAssignee: config.listAssignee ?? 'all',
        listAccount: config.listAccount ?? '',
        replyError: '',
        emojiOpen: false,
        recording: false,
        pendingVoiceBlob: null,
        hasPendingFile: false,
        chatEmojis: CHAT_EMOJIS,
        _mediaRecorder: null,
        _recordStream: null,
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
            this.stopMicTracks();
            if (this.listClickBound && this.$el) {
                this.$el.removeEventListener('click', this.listClickBound);
            }
            this.listClickBound = null;
        },
        scrollToEnd() {
            inboxScrollToEnd();
        },
        attachmentEl() {
            return this.$refs.fileAttachment ?? document.getElementById('inbox-attachment-input');
        },
        toggleEmoji() {
            this.emojiOpen = !this.emojiOpen;
        },
        insertEmoji(ch) {
            const ta = this.$refs.chatBody;
            if (!ta) {
                return;
            }
            const s = ta.selectionStart ?? ta.value.length;
            const e = ta.selectionEnd ?? ta.value.length;
            ta.value = ta.value.slice(0, s) + ch + ta.value.slice(e);
            ta.focus();
            ta.selectionStart = ta.selectionEnd = s + ch.length;
            ta.dispatchEvent(new Event('input', { bubbles: true }));
        },
        pickPhoto() {
            const el = this.attachmentEl();
            if (!el) {
                return;
            }
            el.accept = 'image/*';
            el.value = '';
            this.hasPendingFile = false;
            el.click();
        },
        pickVideo() {
            const el = this.attachmentEl();
            if (!el) {
                return;
            }
            el.accept = 'video/*';
            el.value = '';
            this.hasPendingFile = false;
            el.click();
        },
        clearAttachment() {
            const el = this.attachmentEl();
            if (el) {
                el.value = '';
            }
            this.hasPendingFile = false;
            this.pendingVoiceBlob = null;
        },
        stopMicTracks() {
            if (this._recordStream) {
                this._recordStream.getTracks().forEach((t) => t.stop());
            }
            this._recordStream = null;
            this._mediaRecorder = null;
        },
        async toggleVoiceRecord() {
            if (this.recording) {
                const mr = this._mediaRecorder;
                if (mr && mr.state === 'recording') {
                    await new Promise((resolve) => {
                        mr.addEventListener('stop', () => resolve(), { once: true });
                        mr.stop();
                    });
                }
                this.recording = false;
                return;
            }
            if (!navigator.mediaDevices?.getUserMedia) {
                this.replyError = 'Voice recording is not supported in this browser.';
                return;
            }
            this.replyError = '';
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.stopMicTracks();
                this._recordStream = stream;
                const mime = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
                    ? 'audio/webm;codecs=opus'
                    : MediaRecorder.isTypeSupported('audio/webm')
                      ? 'audio/webm'
                      : '';
                const rec = mime ? new MediaRecorder(stream, { mimeType: mime }) : new MediaRecorder(stream);
                const chunks = [];
                rec.ondataavailable = (ev) => {
                    if (ev.data.size) {
                        chunks.push(ev.data);
                    }
                };
                rec.onstop = () => {
                    const blob = new Blob(chunks, { type: rec.mimeType || 'audio/webm' });
                    if (blob.size > 0) {
                        this.pendingVoiceBlob = blob;
                    }
                    this.stopMicTracks();
                };
                rec.start();
                this._mediaRecorder = rec;
                this.recording = true;
            } catch {
                this.replyError = 'Microphone permission denied or unavailable.';
                this.recording = false;
                this.stopMicTracks();
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
            const fileEl = this.attachmentEl();
            const hasFile = fileEl?.files?.length > 0;
            const body = (ta?.value ?? '').trim();
            if (!hasFile && body === '' && !this.pendingVoiceBlob) {
                this.replyError = 'Add text, an emoji, a photo, video, or voice note.';
                return;
            }
            const fd = new FormData(form);
            if (this.pendingVoiceBlob) {
                fd.set('attachment', this.pendingVoiceBlob, 'voice.webm');
                fd.set('voice_note', '1');
            }
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
                    const attErr = data.errors?.attachment;
                    if (Array.isArray(bodyErr) && bodyErr.length) {
                        this.replyError = bodyErr[0];
                    } else if (typeof bodyErr === 'string') {
                        this.replyError = bodyErr;
                    } else if (Array.isArray(attErr) && attErr.length) {
                        this.replyError = attErr[0];
                    } else if (typeof attErr === 'string') {
                        this.replyError = attErr;
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
                this.pendingVoiceBlob = null;
                this.hasPendingFile = false;
                if (fileEl) {
                    fileEl.value = '';
                }
                this.emojiOpen = false;
            } catch {
                this.replyError = 'Send failed';
            }
        },
    }));
});

/**
 * Inbox composer actions: delegate via [data-inbox-act] so clicks work even if Alpine
 * scope on the button differs. (Layout sidebar uses $store.layout, not nested x-data.)
 */
function inboxRootData(el) {
    const root = el?.closest?.('[data-inbox-page]');
    if (!root || !window.Alpine || typeof window.Alpine.$data !== 'function') {
        return null;
    }
    try {
        return window.Alpine.$data(root);
    } catch {
        return null;
    }
}

function initInboxActionDelegation() {
    document.addEventListener('click', (event) => {
        const el = event.target.closest?.('[data-inbox-act]');
        if (!el) {
            return;
        }
        const d = inboxRootData(el);
        if (!d) {
            return;
        }
        const act = el.dataset.inboxAct;
        if (!act) {
            return;
        }
        if (act === 'insertEmoji') {
            const ch = el.dataset.inboxEmoji ?? '';
            if (ch && typeof d.insertEmoji === 'function') {
                d.insertEmoji(ch);
                d.emojiOpen = false;
            }
            return;
        }
        if (typeof d[act] !== 'function') {
            return;
        }
        d[act]();
    });

    document.addEventListener('change', (event) => {
        const t = event.target;
        if (!t || t.name !== 'attachment' || !t.closest?.('[data-inbox-page]')) {
            return;
        }
        const d = inboxRootData(t);
        if (d) {
            d.hasPendingFile = !!(t.files && t.files.length > 0);
        }
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!form || form.tagName !== 'FORM' || !form.hasAttribute('data-inbox-reply')) {
            return;
        }
        const d = inboxRootData(form);
        if (!d || typeof d.sendReply !== 'function') {
            return;
        }
        d.sendReply(event);
    });
}

Alpine.start();
initInboxActionDelegation();
initInboxLive();

document.addEventListener('DOMContentLoaded', () => initNotifications());
