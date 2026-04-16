function playNotificationSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.type = 'sine';
        o.frequency.value = 880;
        g.gain.value = 0.08;
        o.connect(g);
        g.connect(ctx.destination);
        o.start();
        setTimeout(() => {
            o.stop();
            ctx.close();
        }, 160);
    } catch {
        // ignore
    }
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function renderList(container, items, emptyText) {
    if (!items.length) {
        container.innerHTML = `<li class="px-3 py-6 text-center text-[color:var(--app-text-muted)]">${emptyText}</li>`;
        return;
    }
    container.innerHTML = items
        .map(
            (n) => `
        <li class="border-b border-[color:var(--app-card-border)]/60 last:border-0">
            <a href="${n.href || '#'}" data-notification-id="${n.id}" class="notification-item block px-3 py-2.5 transition hover:bg-slate-50 ${n.read_at ? 'opacity-70' : ''}">
                <span class="block font-medium text-[color:var(--app-text)]">${escapeHtml(n.title)}</span>
                <span class="mt-0.5 block line-clamp-2 text-xs text-[color:var(--app-text-muted)]">${escapeHtml(n.body)}</span>
            </a>
        </li>`,
        )
        .join('');
}

function updateBadges(roots, unread) {
    roots.forEach((root) => {
        const badge = root.querySelector('.js-notification-badge');
        if (!badge) return;
        if (unread > 0) {
            badge.textContent = unread > 99 ? '99+' : String(unread);
            badge.classList.remove('hidden');
            badge.classList.add('flex');
        } else {
            badge.classList.add('hidden');
            badge.classList.remove('flex');
        }
    });
}

export function initNotifications() {
    const roots = document.querySelectorAll('.js-notification-root');
    if (!roots.length) return;

    const first = roots[0];
    const pollUrl = first.dataset.notificationsUrl;
    const readAllUrl = first.dataset.readAllUrl;
    const readTemplate = first.dataset.readTemplate || '';
    const emptyText = first.dataset.emptyText || 'No notifications yet.';
    const csrf = first.dataset.csrf;
    if (!pollUrl) return;

    let prevSnapshot = null;

    async function poll() {
        try {
            const res = await fetch(pollUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const data = await res.json();

            if (prevSnapshot !== null) {
                const prevIds = new Set((prevSnapshot.notifications || []).map((n) => n.id));
                const hasNew = (data.notifications || []).some((n) => !prevIds.has(n.id));
                if (hasNew) {
                    const muteSound = data.preferences?.mute_sound;
                    const muteDesktop = data.preferences?.mute_desktop;
                    if (!muteSound) {
                        playNotificationSound();
                    }
                    if (!muteDesktop && typeof Notification !== 'undefined' && Notification.permission === 'granted') {
                        const newest = data.notifications?.[0];
                        if (newest) {
                            try {
                                new Notification(newest.title || 'Notification', {
                                    body: newest.body || '',
                                    silent: muteSound,
                                });
                            } catch {
                                // ignore
                            }
                        }
                    }
                }
            }
            prevSnapshot = data;

            const unread = data.unread_count ?? 0;
            updateBadges(roots, unread);

            roots.forEach((root) => {
                const list = root.querySelector('.js-notification-list');
                if (list) renderList(list, data.notifications || [], emptyText);
            });
        } catch {
            // ignore network errors
        }
    }

    poll();
    setInterval(poll, 15000);

    roots.forEach((root) => {
        const list = root.querySelector('.js-notification-list');
        if (!list) return;
        list.addEventListener('click', async (e) => {
            const a = e.target.closest('a.notification-item');
            if (!a) return;
            const id = a.dataset.notificationId;
            if (!id) return;
            const href = a.getAttribute('href');
            e.preventDefault();
            const readUrl = readTemplate.replace('__ID__', encodeURIComponent(id));
            try {
                await fetch(readUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
            } catch {
                // ignore
            }
            if (href && href !== '#') {
                window.location.href = href;
            }
        });

        const markAllBtn = root.querySelector('.js-notification-mark-all');
        if (markAllBtn && readAllUrl) {
            markAllBtn.addEventListener('click', async () => {
                try {
                    await fetch(readAllUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    await poll();
                } catch {
                    // ignore
                }
            });
        }
    });
}
