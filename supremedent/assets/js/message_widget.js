/* Floating SupremeDent message center: conversation history on the left, thread or compose on the right. */
(() => {
    'use strict';
    const fab = document.getElementById('sdChatFab');
    const panel = document.getElementById('sdChatPanel');
    if (!fab || !panel) return;

    const api = panel.dataset.api;
    const csrf = panel.dataset.csrf;
    const role = panel.dataset.role;
    const $ = id => document.getElementById(id);
    const badge = $('sdChatBadge'), list = $('sdChatConversations'), thread = $('sdChatThread');
    const compose = $('sdChatCompose'), form = $('sdComposeForm'), recipientSelect = $('sdComposeRecipient');
    const composeTitle = $('sdComposeTitle'), composeTabs = $('sdComposeTabs'), composeError = $('sdComposeError');

    // Recipient categories the < > arrows cycle through, per portal.
    const categories = {
        patient: ['assistant'],
        assistant: ['patient', 'dentist', 'assistant'],
        dentist: ['patient', 'assistant', 'dentist'],
        superadmin: ['patient', 'assistant', 'dentist', 'superadmin']
    }[role] || ['assistant'];
    const label = r => r === 'superadmin' ? 'Admin' : r.charAt(0).toUpperCase() + r.slice(1);
    let categoryIndex = 0, recipients = null, activeUser = 0, pollTimer = null;

    const el = (tag, cls, text) => { const n = document.createElement(tag); if (cls) n.className = cls; if (text !== undefined) n.textContent = text; return n; };
    async function getJson(params) {
        const res = await fetch(api + (api.includes('?') ? '&' : '?') + new URLSearchParams(params), { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
        const data = await res.json().catch(() => ({ ok: false, message: 'Unexpected server response.' }));
        if (!data.ok) throw new Error(data.message || 'Request failed.');
        return data;
    }
    async function postJson(params) {
        const res = await fetch(api, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' }, body: new URLSearchParams({ ...params, csrf_token: csrf }) });
        const data = await res.json().catch(() => ({ ok: false, message: 'Unexpected server response.' }));
        if (!data.ok) throw new Error(data.message || 'Request failed.');
        return data;
    }
    function setBadge(n) { badge.hidden = !n; badge.textContent = n > 99 ? '99+' : String(n); }

    async function loadSummary() {
        try {
            const data = await getJson({ action: 'summary' });
            setBadge(data.unread);
            list.replaceChildren();
            if (!data.conversations.length) { list.append(el('div', 'sd-chat-empty', 'No conversations')); return; }
            data.conversations.forEach(c => {
                const item = el('button', 'sd-chat-item' + (c.unread ? ' unread' : '') + (c.user_id === activeUser ? ' active' : ''));
                item.type = 'button';
                const top = el('div', 'sd-chat-item-top');
                top.append(el('strong', null, c.name), el('small', null, c.at));
                item.append(top, el('span', 'sd-chat-item-role', c.role + (c.unread ? ' · ' + c.unread + ' new' : '')), el('span', 'sd-chat-item-preview', (c.from_me ? 'You: ' : '') + c.subject + ' — ' + c.preview));
                item.addEventListener('click', () => openThread(c.user_id));
                list.append(item);
            });
        } catch (e) {
            list.replaceChildren(el('div', 'sd-chat-empty', e.message));
        }
    }

    async function openThread(userId) {
        activeUser = userId;
        closeCompose();
        thread.replaceChildren(el('div', 'sd-chat-placeholder', 'Loading conversation…'));
        try {
            const data = await getJson({ action: 'thread', user_id: userId });
            thread.replaceChildren();
            const head = el('div', 'sd-thread-head');
            head.append(el('strong', null, data.person.name), el('span', 'badge', data.person.role));
            const scroller = el('div', 'sd-thread-messages');
            let lastSubject = '';
            data.messages.forEach(m => {
                const bubble = el('div', 'sd-bubble ' + (m.mine ? 'mine' : 'theirs'));
                bubble.append(el('b', null, m.subject), el('p', null, m.message), el('small', null, m.at));
                if (m.mine && m.status) bubble.append(el('span', 'sd-receipt ' + m.status, m.status === 'seen' ? 'Seen' + (m.seen_at ? ' · ' + m.seen_at : '') : 'Delivered'));
                scroller.append(bubble);
                lastSubject = m.subject;
            });
            thread.append(head, scroller);
            if (data.can_reply) {
                const reply = el('form', 'sd-thread-reply');
                const box = el('textarea'); box.placeholder = 'Write a reply…'; box.maxLength = 3000; box.required = true; box.setAttribute('aria-label', 'Reply');
                const send = el('button', 'btn btn-primary'); send.type = 'submit'; send.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send';
                const err = el('div', 'sd-compose-error');
                reply.append(box, send, err);
                reply.addEventListener('submit', async ev => {
                    ev.preventDefault();
                    if (!box.value.trim()) { box.focus(); return; }
                    send.disabled = true; err.textContent = '';
                    try {
                        const subject = lastSubject.toLowerCase().startsWith('re:') ? lastSubject : 'Re: ' + (lastSubject || 'Message');
                        await postJson({ action: 'send', recipient_id: userId, subject: subject.slice(0, 180), message: box.value.trim() });
                        await openThread(userId);
                    } catch (e2) { err.textContent = e2.message; }
                    finally { send.disabled = false; }
                });
                thread.append(reply);
            }
            scroller.scrollTop = scroller.scrollHeight;
            loadSummary();
        } catch (e) {
            thread.replaceChildren(el('div', 'sd-chat-placeholder', e.message));
        }
    }

    async function ensureRecipients() {
        if (recipients) return recipients;
        const data = await getJson({ action: 'recipients' });
        recipients = data.groups;
        return recipients;
    }
    function renderCategory() {
        const current = categories[categoryIndex];
        composeTitle.textContent = 'Message to ' + label(current);
        composeTabs.replaceChildren();
        if (false) categories.forEach((c, i) => {
            const tab = el('button', 'sd-compose-tab' + (i === categoryIndex ? ' active' : ''), label(c));
            tab.type = 'button';
            tab.setAttribute('aria-pressed', i === categoryIndex ? 'true' : 'false');
            tab.addEventListener('click', () => { categoryIndex = i; renderCategory(); });
            composeTabs.append(tab);
        });
        // The arrow pointing toward the active side is highlighted; with a single recipient type
        // (patients can only message the assistant) the tabs and arrows are not shown at all.
        $('sdComposePrev').classList.toggle('is-active', categoryIndex > 0);
        $('sdComposeNext').classList.toggle('is-active', categoryIndex < categories.length - 1);
        const switcher = form.querySelector('.sd-compose-switch');
        if (switcher) switcher.hidden = categories.length < 2;
        composeTabs.hidden = categories.length < 2;
        recipientSelect.replaceChildren();
        const people = (recipients && recipients[current]) || [];
        if (!people.length) {
            const o = el('option', null, 'No ' + label(current).toLowerCase() + ' available'); o.value = ''; recipientSelect.append(o);
        } else {
            people.forEach(p => { const o = el('option', null, p.label); o.value = p.id; recipientSelect.append(o); });
        }
    }
    async function openCompose() {
        compose.hidden = false;
        composeError.textContent = '';
        try { await ensureRecipients(); } catch (e) { composeError.textContent = e.message; }
        renderCategory();
        $('sdComposeSubject').focus();
    }
    function closeCompose() { compose.hidden = true; }

    function openPanel() {
        panel.hidden = false;
        fab.classList.add('is-open');
        fab.setAttribute('aria-expanded', 'true');
        loadSummary();
        clearInterval(pollTimer);
        pollTimer = setInterval(() => { if (document.hidden) return; loadSummary(); const typing = thread.querySelector('textarea'); if (activeUser && compose.hidden && !(typing && typing.value.trim())) openThread(activeUser); }, 5000);
    }
    function closePanel() {
        panel.hidden = true;
        fab.classList.remove('is-open');
        fab.setAttribute('aria-expanded', 'false');
        clearInterval(pollTimer);
    }

    fab.addEventListener('click', () => panel.hidden ? openPanel() : closePanel());
    $('sdChatClose').addEventListener('click', closePanel);
    $('sdChatNew').addEventListener('click', openCompose);
    $('sdComposeClose').addEventListener('click', closeCompose);
    $('sdComposePrev').addEventListener('click', () => { categoryIndex = (categoryIndex - 1 + categories.length) % categories.length; renderCategory(); });
    $('sdComposeNext').addEventListener('click', () => { categoryIndex = (categoryIndex + 1) % categories.length; renderCategory(); });
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape' || panel.hidden) return;
        if (!compose.hidden) closeCompose(); else closePanel();
    });
    form.addEventListener('submit', async e => {
        e.preventDefault();
        composeError.textContent = '';
        const recipient = recipientSelect.value, subject = $('sdComposeSubject').value.trim(), message = $('sdComposeMessage').value.trim();
        if (!recipient) { composeError.textContent = 'Choose a recipient.'; return; }
        if (!subject) { composeError.textContent = 'Enter a subject.'; $('sdComposeSubject').focus(); return; }
        if (!message) { composeError.textContent = 'Write your message.'; $('sdComposeMessage').focus(); return; }
        const send = $('sdComposeSend'); send.disabled = true;
        try {
            await postJson({ action: 'send', recipient_id: recipient, subject, message });
            form.reset();
            closeCompose();
            await openThread(Number(recipient));
        } catch (err) { composeError.textContent = err.message; }
        finally { send.disabled = false; }
    });

    // Old links to messages.php open the floating panel instead.
    if (/[?&]messages=open\b/.test(location.search)) openPanel();
    // Keep the unread badge current even while the panel is closed.
    setInterval(() => { if (panel.hidden && !document.hidden) getJson({ action: 'summary', poll: '1' }).then(d => setBadge(d.unread)).catch(() => {}); }, 10000);
})();
