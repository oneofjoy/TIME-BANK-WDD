(() => {
    document.querySelectorAll('[data-site-href]').forEach(link => { link.href = TimeBankAuth.url(link.dataset.siteHref); });
    document.querySelectorAll('[data-site-src]').forEach(img => { img.src = TimeBankAuth.url(img.dataset.siteSrc); });
    const form = document.querySelector('.auth-form');
    const message = document.querySelector('.auth-message');
    const submit = form.querySelector('[type="submit"]');
    const normalLabel = submit.textContent;
    const username = form.elements.namedItem('username');
    form.addEventListener('input', event => {
        event.target.removeAttribute('aria-invalid');
        const error = document.getElementById(`${event.target.name}-error`);
        if (error) { error.hidden = true; error.textContent = ''; }
    });
    // Only a fixed, project-owned return target is accepted after login.
    const afterLogin = new URLSearchParams(location.search).get('next') === 'explore'
        ? 'Pages/Explore/explore.html' : 'Pages/Member/Dashboard/';
    if (username) {
        username.addEventListener('input', () => {
            username.setCustomValidity('');
            if (username.value && !/^[A-Za-z0-9_]{3,30}$/.test(username.value.trim())) {
                username.setCustomValidity('Use 3–30 letters, numbers, or underscores.');
            }
        });
    }
    form.querySelectorAll('.auth-password input').forEach(input => {
        input.addEventListener('input', () => input.setCustomValidity(''));
    });
    const showMessage = (text, success = false) => {
        message.textContent = text;
        message.classList.toggle('is-success', success);
        message.hidden = false;
    };
    document.querySelectorAll('[data-password-toggle]').forEach(toggle => {
        toggle.addEventListener('click', () => {
            const input = document.getElementById(toggle.dataset.passwordToggle);
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            toggle.textContent = visible ? 'Hide' : 'Show';
            toggle.setAttribute('aria-pressed', String(visible));
            toggle.setAttribute('aria-label', `${visible ? 'Hide' : 'Show'} ${input.id === 'confirm_password' ? 'confirmation password' : 'password'}`);
        });
    });
    if (TimeBankAuth.isLocalPreview) {
        message.classList.add('is-info');
        const notice = document.createElement('p');
        notice.textContent = 'Open TimeBank on the local server to create an account or log in.';
        const link = document.createElement('a');
        link.className = 'auth-server-link';
        link.href = TimeBankAuth.serverUrl(`Pages/Auth/${form.dataset.authForm === 'login' ? 'Login' : 'Register'}/`);
        link.textContent = 'Open TimeBank';
        message.replaceChildren(notice, link);
        message.hidden = false;
        submit.disabled = true;
        submit.setAttribute('aria-describedby', 'auth-local-notice');
        message.id = 'auth-local-notice';
        form.addEventListener('submit', event => event.preventDefault());
        return;
    }
    async function checkSession(refresh = false) {
        try {
            const session = await TimeBankAuth.getSession(refresh);
            if (session.user) location.replace(TimeBankAuth.url(afterLogin));
        } catch (error) { showMessage(error.message || 'We couldn’t connect. Please try again.'); }
    }
    if (form.dataset.authForm === 'login' && new URLSearchParams(location.search).get('registered') === '1') {
        showMessage('Your account has been created. Log in to get started.', true);
        history.replaceState(null, '', location.pathname);
    }
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (submit.disabled) return;
        message.hidden = true;
        form.querySelectorAll('[aria-invalid]').forEach(input => input.removeAttribute('aria-invalid'));
        form.querySelectorAll('.auth-error').forEach(error => { error.textContent = ''; error.hidden = true; });
        if (username) username.value = username.value.trim();
        for (const name of ['full_name', 'email', 'identifier']) {
            const input = form.elements.namedItem(name);
            if (input) input.value = input.value.trim();
        }
        for (const input of form.querySelectorAll('.auth-password input')) {
            input.setCustomValidity(new TextEncoder().encode(input.value).length > 72
                ? 'This password is too long. Please use a shorter one.' : '');
        }
        const confirmation = form.elements.namedItem('confirm_password');
        if (confirmation && confirmation.value !== form.elements.password.value) {
            confirmation.setCustomValidity('The passwords don’t match. Please try again.');
        }
        if (!form.reportValidity()) return;
        submit.disabled = true;
        submit.textContent = form.dataset.authForm === 'login' ? 'Logging in…' : 'Creating account…';
        form.setAttribute('aria-busy', 'true');
        let navigating = false;
        try {
            const data = Object.fromEntries(new FormData(form));
            await TimeBankAuth.request(form.dataset.authForm, data);
            navigating = true;
            location.assign(TimeBankAuth.url(form.dataset.authForm === 'login' ? afterLogin : 'Pages/Auth/Login/?registered=1'));
        } catch (error) {
            showMessage(error.message || 'Unable to connect. Please try again.');
            let first = null;
            Object.entries(error.fields || {}).forEach(([name, text]) => {
                const input = form.elements.namedItem(name);
                const fieldError = document.getElementById(`${name}-error`);
                if (input && fieldError) {
                    input.setAttribute('aria-invalid', 'true');
                    fieldError.textContent = text;
                    fieldError.hidden = false;
                    first ||= input;
                }
            });
            if (first) first.focus();
            else { message.tabIndex = -1; message.focus(); }
        } finally {
            if (!navigating) {
                submit.disabled = false;
                submit.textContent = normalLabel;
                form.removeAttribute('aria-busy');
            }
        }
    });
    checkSession();
    window.addEventListener('pageshow', event => { if (event.persisted) checkSession(true); });
})();
