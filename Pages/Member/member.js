(() => {
    const status = document.querySelector('.member-status');
    const main = document.querySelector('.member-main');
    const showError = message => { status.textContent = message; status.hidden = false; };
    const starter = document.querySelector('[data-starter-pack]');
    if (starter) {
        let state = JSON.parse(starter.dataset.starterState);
        let checking = false;
        let claiming = false;
        const button = starter.querySelector('[data-claim-starter]');
        const retry = starter.querySelector('[data-starter-retry]');
        const message = starter.querySelector('[data-starter-message]');
        const render = () => {
            button.disabled = claiming || !state.can_claim;
            button.textContent = !state.wallet_available ? 'Unavailable' : state.received ? 'Received' : 'Claim Starter Pack';
            starter.querySelector('[data-starter-description]').textContent = !state.wallet_available
                ? 'Your Time Wallet couldn’t be loaded. Please try again later.'
                : state.received ? 'Earn more credits by sharing a skill with someone else.' : 'Claim your welcome credits to book your first lesson.';
        };
        async function refreshStarter() {
            if (checking || claiming || document.hidden) return;
            checking = true;
            try {
                const response = await fetch(TimeBankAuth.url('php/get-starter-pack.php'), { credentials: 'same-origin', cache: 'no-store' });
                const data = await response.json();
                if (response.status === 401) { location.replace(TimeBankAuth.url('Pages/Auth/Login/')); return; }
                if (!response.ok || !data.success || !data.starter_pack) throw new Error('Starter pack status could not be loaded.');
                const changed = data.starter_pack.received !== state.received || data.starter_pack.available_credits !== state.available_credits;
                state = data.starter_pack;
                message.hidden = retry.hidden = true;
                if (changed) { location.reload(); return; }
                render();
            } catch {
                button.disabled = true;
                message.textContent = 'We couldn’t check your starter pack. Please try again.';
                message.hidden = retry.hidden = false;
            } finally { checking = false; }
        }
        button.addEventListener('click', async () => {
            if (button.disabled || claiming) return;
            claiming = true;
            button.disabled = true;
            button.textContent = 'Claiming…';
            message.hidden = retry.hidden = true;
            try {
                await TimeBankAuth.request('claim-starter-pack', {});
                location.reload();
            } catch (error) {
                if (error.status === 409) { claiming = false; await refreshStarter(); }
                else if (error.status === 401) location.replace(TimeBankAuth.url('Pages/Auth/Login/'));
                else {
                    message.textContent = error.message || 'We couldn’t add your starter pack. Please try again.';
                    message.hidden = retry.hidden = false;
                }
            } finally {
                claiming = false;
                if (!message.hidden) button.textContent = state.received ? 'Received' : 'Claim Starter Pack';
            }
        });
        retry.addEventListener('click', refreshStarter);
        window.addEventListener('focus', refreshStarter);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshStarter(); });
        render();
    }
    // Enable Start Session when its server-rendered time is reached, and disable
    // it after the time has passed. The endpoint independently checks the clock.
    const serverAtLoad = Date.parse(main.dataset.serverTime);
    const loadedAt = performance.now();
    let startTimer;
    const updateStartButtons = () => {
        const now = serverAtLoad + performance.now() - loadedAt;
        let next = Infinity;
        document.querySelectorAll('[data-session-start]').forEach(button => {
            const start = Date.parse(button.dataset.sessionStart), end = Date.parse(button.dataset.sessionEnd);
            button.disabled = now < start || now >= end;
            if (now < start) next = Math.min(next, start - now);
            else if (now < end) next = Math.min(next, end - now);
            else button.title = 'This time has passed. Cancel this session to return the credits.';
        });
        clearTimeout(startTimer);
        if (Number.isFinite(next)) startTimer = setTimeout(updateStartButtons, Math.min(next + 1200, 2147483647));
    };
    updateStartButtons();
    window.addEventListener('focus', updateStartButtons);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) updateStartButtons(); });

    document.querySelectorAll('[data-member-avatar]').forEach(image => {
        const fallback = () => {
            const initials = document.createElement('span');
            initials.className = 'member-avatar';
            initials.textContent = image.dataset.avatarInitial;
            image.replaceWith(initials);
        };
        image.addEventListener('error', fallback, { once: true });
        if (image.complete && !image.naturalWidth) fallback();
    });
    // Re-enter PHP guards and read current data, including after switching accounts.
    window.addEventListener('pagehide', event => { if (event.persisted) document.body.style.visibility = 'hidden'; });
    window.addEventListener('pageshow', event => { if (event.persisted) location.reload(); });
    document.querySelectorAll('[data-page-retry]').forEach(button => button.addEventListener('click', () => location.reload()));

    const openers = new WeakMap();
    const openDialog = (dialog, opener) => {
        if (!dialog || dialog.open) return;
        openers.set(dialog, opener);
        dialog.showModal();
        const first = dialog.querySelector('form input[type="radio"]:checked')
            || dialog.querySelector('form input:not([type="hidden"]), form select, form textarea');
        first?.focus();
    };
    document.querySelectorAll('dialog').forEach(dialog => {
        dialog.addEventListener('close', () => openers.get(dialog)?.focus());
        dialog.addEventListener('cancel', event => {
            if (dialog.querySelector('form[aria-busy="true"]')) event.preventDefault();
        });
    });
    document.querySelectorAll('[data-open-dialog]').forEach(button => button.addEventListener('click', () => openDialog(document.getElementById(button.dataset.openDialog), button)));
    document.querySelectorAll('[data-close-dialog]').forEach(button => button.addEventListener('click', () => {
        const dialog = button.closest('dialog');
        if (!dialog.querySelector('form[aria-busy="true"]')) dialog.close();
    }));
    const clearFormErrors = form => {
        const message = form.querySelector('[data-form-message]');
        if (message) message.hidden = true;
        form.querySelectorAll('[data-error-for]').forEach(error => { error.textContent = ''; error.hidden = true; });
        form.querySelectorAll('input,select,textarea').forEach(input => {
            input.removeAttribute('aria-invalid');
            input.setCustomValidity('');
        });
    };
    const profileDialog = document.getElementById('edit-profile');
    if (profileDialog) {
        const form = profileDialog.querySelector('form');
        const preview = profileDialog.querySelector('[data-avatar-preview]');
        const previewAvatar = () => {
            const selected = form.querySelector('[name="avatar_id"]:checked');
            renderSiteAvatar(preview, selected?.dataset.avatarSrc, preview.dataset.avatarInitial, 'member-avatar');
        };
        form.querySelectorAll('[name="avatar_id"]').forEach(radio => radio.addEventListener('change', previewAvatar));
        profileDialog.addEventListener('close', () => { form.reset(); clearFormErrors(form); previewAvatar(); });
    }
    const skillDialog = document.getElementById('skill-editor');
    document.querySelectorAll('[data-cancel-teaching-time]').forEach(button => button.addEventListener('click', () => {
        const dialog = document.getElementById('cancel-teaching-time');
        const form = dialog.querySelector('form');
        clearFormErrors(form);
        form.elements.availability_id.value = button.dataset.cancelTeachingTime;
        const when = button.closest('[data-teaching-time]').querySelector('time').textContent;
        dialog.querySelector('[data-cancel-time-description]').textContent = 'Cancel “' + button.dataset.classTitle + '” on ' + when + '?';
        openDialog(dialog, button);
    }));
    document.querySelectorAll('[data-add-class-time]').forEach(button => button.addEventListener('click', () => {
        const dialog = document.getElementById('class-time-editor');
        const form = dialog.querySelector('form');
        form.reset();
        clearFormErrors(form);
        form.elements.offering_id.value = button.dataset.addClassTime;
        const nextMinuteWib = new Date(serverAtLoad + performance.now() - loadedAt + 7 * 3600000 + 60000);
        form.elements.start_at.min = nextMinuteWib.toISOString().slice(0, 16);
        dialog.querySelector('[data-time-class-title]').textContent = button.dataset.classTitle;
        openDialog(dialog, button);
    }));
    document.querySelectorAll('[data-session-action]').forEach(button => button.addEventListener('click', () => {
        const dialog = document.getElementById('session-action-dialog');
        const form = dialog.querySelector('form');
        clearFormErrors(form);
        form.elements.session_id.value = button.dataset.sessionId;
        form.elements.action.value = button.dataset.sessionAction;
        const copy = {
            start: ['Start this session?', 'Start when you and your learner are ready.', 'Start Session'],
            end: ['End this session?', 'Your learner will be asked to confirm it is complete before credits are sent to you.', 'End Session'],
            confirm: ['Confirm this session is complete?', 'The reserved Time Credits will be sent to your tutor.', 'Confirm Complete'],
            cancel: ['Cancel this session?', 'The reserved Time Credits will be returned to the learner.', 'Cancel Session']
        }[button.dataset.sessionAction];
        dialog.querySelector('h2').textContent = copy[0];
        dialog.querySelector('[data-session-action-description]').textContent = copy[1];
        form.querySelector('[type="submit"]').textContent = copy[2];
        openDialog(dialog, button);
    }));
    const skillForm = skillDialog?.querySelector('form');
    const updateCost = () => {
        if (!skillForm) return;
        const cost = Number(skillForm.elements.duration_minutes.value) / 60;
        document.querySelector('[data-skill-cost]').textContent = cost + (cost === 1 ? ' Time Credit' : ' Time Credits') + ' · 1 hour = 1 credit.';
    };
    skillForm?.elements.duration_minutes.addEventListener('change', updateCost);
    document.querySelector('[data-new-skill]')?.addEventListener('click', event => {
        skillForm.reset();
        clearFormErrors(skillForm);
        skillForm.elements.offering_id.value = '';
        document.getElementById('skill-editor-title').textContent = 'Add Skill';
        updateCost();
        openDialog(skillDialog, event.currentTarget);
    });
    document.querySelectorAll('[data-edit-skill]').forEach(button => button.addEventListener('click', () => {
        const offering = JSON.parse(button.dataset.offering);
        skillForm.reset();
        clearFormErrors(skillForm);
        for (const name of ['offering_id', 'skill_id', 'title', 'description', 'duration_minutes']) skillForm.elements.namedItem(name).value = offering[name] ?? '';
        skillForm.elements.is_active.checked = Number(offering.is_active) === 1;
        document.getElementById('skill-editor-title').textContent = 'Edit Skill';
        updateCost();
        openDialog(skillDialog, button);
    }));
    document.querySelectorAll('[data-deactivate-skill]').forEach(button => button.addEventListener('click', () => {
        const dialog = document.getElementById('deactivate-skill');
        const form = dialog.querySelector('form');
        clearFormErrors(form);
        form.elements.offering_id.value = button.dataset.deactivateSkill;
        dialog.querySelector('[data-deactivate-description]').textContent = 'Hide “' + button.dataset.offeringTitle + '”?';
        openDialog(dialog, button);
    }));
    document.querySelectorAll('[data-password-toggle]').forEach(toggle => toggle.addEventListener('click', () => {
        const input = document.getElementById(toggle.dataset.passwordToggle);
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        toggle.textContent = visible ? 'Hide' : 'Show';
        toggle.setAttribute('aria-pressed', String(visible));
        toggle.setAttribute('aria-label', (visible ? 'Hide ' : 'Show ') + input.labels[0].textContent.toLowerCase());
    }));
    document.querySelectorAll('[data-member-form]').forEach(form => {
        form.addEventListener('input', event => {
            event.target.setCustomValidity?.('');
            event.target.removeAttribute('aria-invalid');
            form.querySelectorAll('[data-error-for]').forEach(error => {
                if (error.dataset.errorFor === event.target.name) { error.hidden = true; error.textContent = ''; }
            });
        });
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const submit = form.querySelector('[type="submit"]');
            if (submit.disabled) return;
            clearFormErrors(form);
            for (const name of ['full_name', 'username', 'email', 'location', 'title']) {
                const input = form.elements.namedItem(name);
                if (input) input.value = input.value.trim();
            }
            const newPassword = form.elements.namedItem('new_password');
            const confirmation = form.elements.namedItem('confirm_password');
            const username = form.elements.namedItem('username');
            if (username && !/^[A-Za-z0-9_]{3,30}$/.test(username.value)) username.setCustomValidity('Use 3–30 letters, numbers, or underscores.');
            if (newPassword && newPassword.value !== confirmation.value) confirmation.setCustomValidity('The passwords don’t match. Please try again.');
            for (const input of form.querySelectorAll('input[type="password"], .member-password input')) {
                if (new TextEncoder().encode(input.value).length > 72) input.setCustomValidity('This password is too long. Please use a shorter one.');
            }
            if (!form.reportValidity()) return;
            const data = Object.fromEntries(new FormData(form));
            if (form.dataset.memberForm === 'save-skill') {
                data.duration_minutes = Number(data.duration_minutes);
                data.is_active = form.elements.is_active.checked;
            }
            const label = submit.textContent;
            submit.disabled = true;
            const action = form.dataset.memberForm;
            submit.textContent = action === 'session-action'
                ? ({ start: 'Starting…', end: 'Ending…', confirm: 'Confirming…', cancel: 'Cancelling…' }[data.action] || 'Please wait…')
                : action === 'cancel-teaching-time' ? 'Cancelling…' : action === 'deactivate-skill' ? 'Hiding…' : action === 'add-class-time' ? 'Adding time…' : 'Saving…';
            form.setAttribute('aria-busy', 'true');
            try {
                await TimeBankAuth.request(form.dataset.memberForm, data);
                location.reload(); // Fresh PHP data and a server-created success message.
            } catch (error) {
                const message = form.querySelector('[data-form-message]');
                message.textContent = error.message || 'We couldn’t save your changes. Please try again.';
                message.hidden = false;
                let first;
                Object.entries(error.fields || {}).forEach(([name, text]) => {
                    const field = form.elements.namedItem(name);
                    const input = field instanceof RadioNodeList ? [...field].find(radio => radio.checked) || field[0] : field;
                    const fieldError = [...form.querySelectorAll('[data-error-for]')].find(node => node.dataset.errorFor === name);
                    if (input && fieldError) {
                        input.setAttribute('aria-invalid', 'true');
                        fieldError.textContent = text;
                        fieldError.hidden = false;
                        first ||= input;
                    }
                });
                if (first) first.focus();
                else { message.tabIndex = -1; message.focus(); }
                submit.disabled = false;
                submit.textContent = label;
                form.removeAttribute('aria-busy');
                if (error.status === 401) location.replace(TimeBankAuth.url('Pages/Auth/Login/'));
            }
        });
    });
    const tabs = [...document.querySelectorAll('[data-role-tab]')];
    const filterSessions = () => {
        const filter = document.querySelector('[data-session-status]')?.value || 'all';
        document.querySelectorAll('[data-role-panel]').forEach(panel => {
            const booked = panel.querySelector('[data-booked-sessions]');
            const rows = [...booked.querySelectorAll('[data-session-row]')];
            rows.forEach(row => { row.hidden = filter !== 'all' && row.dataset.filterState !== filter; });
            booked.querySelector('[data-filter-empty]').hidden = !rows.length || rows.some(row => !row.hidden);
        });
    };
    const selectTab = tab => {
        tabs.forEach(button => { const selected = button === tab; button.setAttribute('aria-selected', String(selected)); button.tabIndex = selected ? 0 : -1; });
        document.querySelectorAll('[data-role-panel]').forEach(panel => { panel.hidden = panel.dataset.rolePanel !== tab.dataset.roleTab; });
        filterSessions();
        const url = new URL(location.href);
        if (tab.dataset.roleTab === 'teaching') url.searchParams.set('view', 'teaching');
        else url.searchParams.delete('view');
        history.replaceState(null, '', url);
    };
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => selectTab(tab));
        tab.addEventListener('keydown', event => {
            let next;
            if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') next = tabs[(index + 1) % tabs.length];
            if (event.key === 'Home') next = tabs[0];
            if (event.key === 'End') next = tabs[tabs.length - 1];
            if (next) { event.preventDefault(); selectTab(next); next.focus(); }
        });
    });
    document.querySelector('[data-session-status]')?.addEventListener('change', filterSessions);
    const initialTab = tabs.find(tab => tab.getAttribute('aria-selected') === 'true');
    if (initialTab) selectTab(initialTab);
    const revealTeachingTime = () => {
        const target = document.getElementById(location.hash.slice(1));
        const history = target?.closest('[data-teaching-history]');
        if (history) { history.open = true; target.scrollIntoView(); }
    };
    revealTeachingTime();
    window.addEventListener('hashchange', revealTeachingTime);
    document.querySelector('[data-member-logout]')?.addEventListener('click', async event => {
        const button = event.currentTarget;
        if (button.disabled) return;
        button.disabled = true;
        try {
            await TimeBankAuth.request('logout', {});
            main.style.visibility = 'hidden';
            location.replace(TimeBankAuth.url('index.html#hero'));
        } catch (error) { showError(error.message || 'Unable to log out. Please try again.'); button.disabled = false; }
    });
    Promise.all([
        (async () => {
            try { await loadComponent('navbar-mount', TimeBankAuth.url('Shared/Navbar/navbar.html')); initNavbar(); }
            catch { showError('Navigation could not be loaded. Refresh the page to try again.'); }
        })(),
        (async () => {
            try {
                await loadComponent('footer-mount', TimeBankAuth.url('Shared/Footer/footer.html'));
                document.querySelector('[data-copyright-year]').textContent = new Date().getFullYear();
            } catch { showError('The footer could not be loaded. Refresh the page to try again.'); }
        })()
    ]);
})();
