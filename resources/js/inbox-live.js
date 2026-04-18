/**
 * Inbox thread polling and DOM updates outside Alpine (avoids $refs / lifecycle edge cases).
 */

let pollTimer = null;
let fullPollInFlight = false;
let lightPollInFlight = false;
let onNotificationsBound = null;
let onVisBound = null;
let onFocusBound = null;
let onOnlineBound = null;

function inboxRoot() {
    return document.querySelector('[data-inbox-live]');
}

function threadEl() {
    return document.querySelector('[data-inbox-thread]');
}

function readLastMessageId() {
    const r = inboxRoot();
    const n = Number(r?.dataset.inboxLastMessageId ?? 0);
    return Number.isFinite(n) ? n : 0;
}

function writeLastMessageId(n) {
    const r = inboxRoot();
    if (!r || !Number.isFinite(n)) {
        return;
    }
    const cur = readLastMessageId();
    r.dataset.inboxLastMessageId = String(Math.max(cur, n));
}

function threadHasMessageId(thread, mid) {
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
}

function appendThreadHtml(html) {
    const thread = threadEl();
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
        if (threadHasMessageId(thread, mid)) {
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
    inboxScrollToEnd();
}

function patchHeader(contact) {
    if (!contact) {
        return;
    }
    const title = document.querySelector('[data-inbox-hdr-title]');
    const sub = document.querySelector('[data-inbox-hdr-sub]');
    const av = document.querySelector('[data-inbox-hdr-avatar]');
    if (title) {
        title.textContent = contact.title ?? '';
    }
    if (sub) {
        sub.textContent = contact.subtitle ?? '';
    }
    if (av) {
        av.textContent = contact.avatar ?? '';
    }
}

function applyListHtml(html) {
    if (html === undefined || html === null || String(html).trim() === '') {
        return;
    }
    const h = String(html).trim();
    document.querySelectorAll('.js-inbox-conversation-list').forEach((el) => {
        el.innerHTML = h;
    });
}

/**
 * Apply JSON from /inbox/poll or /inbox/{id}/reply.
 *
 * @param {Record<string, unknown>} data
 * @param {boolean} applyList Whether to replace sidebar HTML (skip for lightweight polls).
 */
export function applyInboxJsonResponse(data, applyList = true) {
    if (!data) {
        return;
    }
    if (data.messages_html) {
        appendThreadHtml(String(data.messages_html));
    }
    const raw = data.last_message_id;
    if (raw !== undefined && raw !== null && raw !== '') {
        const n = Number(raw);
        if (Number.isFinite(n)) {
            writeLastMessageId(n);
        }
    }
    patchHeader(data.contact);
    if (applyList && data.list_html) {
        applyListHtml(data.list_html);
    }
}

export function inboxScrollToEnd() {
    requestAnimationFrame(() => {
        const el = threadEl();
        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    });
}

function teardownListeners() {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
    if (onNotificationsBound) {
        document.removeEventListener('app:notifications-refreshed', onNotificationsBound);
        onNotificationsBound = null;
    }
    if (onVisBound) {
        document.removeEventListener('visibilitychange', onVisBound);
        onVisBound = null;
    }
    if (onFocusBound) {
        window.removeEventListener('focus', onFocusBound);
        onFocusBound = null;
    }
    if (onOnlineBound) {
        window.removeEventListener('online', onOnlineBound);
        onOnlineBound = null;
    }
}

async function poll(syncList) {
    const r = inboxRoot();
    if (!r) {
        return;
    }
    const cid = String(r.dataset.inboxConversationId ?? '').trim();
    const pollUrl = String(r.dataset.inboxPollUrl ?? '').trim();
    if (!cid || !pollUrl) {
        return;
    }
    if (syncList && fullPollInFlight) {
        return;
    }
    if (!syncList && lightPollInFlight) {
        return;
    }
    if (syncList) {
        fullPollInFlight = true;
    } else {
        lightPollInFlight = true;
    }
    try {
        const url = new URL(pollUrl, window.location.origin);
        url.searchParams.set('conversation', cid);
        url.searchParams.set('after', String(readLastMessageId()));
        if (syncList) {
            url.searchParams.set('sync_list', '1');
            url.searchParams.set('list_assignee', r.dataset.inboxListAssignee || 'all');
            const acc = r.dataset.inboxListAccount;
            if (acc) {
                url.searchParams.set('list_account', acc);
            }
        }
        const res = await fetch(url.toString(), {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) {
            return;
        }
        const raw = (await res.text()).replace(/^\uFEFF/, '');
        const bodyTrim = raw.trim();
        const ct = (res.headers.get('content-type') || '').toLowerCase();
        if (
            !ct.includes('application/json') &&
            !ct.includes('text/json') &&
            !bodyTrim.startsWith('{')
        ) {
            return;
        }
        let data;
        try {
            data = JSON.parse(raw);
        } catch {
            return;
        }
        applyInboxJsonResponse(data, syncList);
    } catch {
        /* ignore */
    } finally {
        if (syncList) {
            fullPollInFlight = false;
        } else {
            lightPollInFlight = false;
        }
    }
}

/**
 * Start polling when the inbox page has an open conversation (data-inbox-live + conversation id).
 */
export function initInboxLive() {
    teardownListeners();
    const r = inboxRoot();
    if (!r) {
        return;
    }
    const cid = String(r.dataset.inboxConversationId ?? '').trim();
    if (!cid) {
        return;
    }

    onNotificationsBound = () => poll(false);
    document.addEventListener('app:notifications-refreshed', onNotificationsBound);

    onVisBound = () => {
        if (document.visibilityState === 'visible') {
            poll(true);
        }
    };
    document.addEventListener('visibilitychange', onVisBound);

    onFocusBound = () => poll(true);
    window.addEventListener('focus', onFocusBound);

    onOnlineBound = () => poll(true);
    window.addEventListener('online', onOnlineBound);

    pollTimer = setInterval(() => poll(true), 2000);
    requestAnimationFrame(() => poll(true));
}
