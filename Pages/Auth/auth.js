(() => {
    document.querySelectorAll('[data-site-href]').forEach(link => { link.href = TimeBankAuth.url(link.dataset.siteHref); });
    document.querySelectorAll('[data-site-src]').forEach(img => { img.src = TimeBankAuth.url(img.dataset.siteSrc); });
    const form = document.querySelector('.auth-form');
    const message = document.querySelector('.auth-message');
    const submit = form.querySelector('[type="submit"]');
    const normalLabel = submit.textContent;
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
    async function checkSession(refresh = false) {
        try {
            const session = await TimeBankAuth.getSession(refresh);
            if (session.user) location.replace(TimeBankAuth.url('Pages/Member/Dashboard/'));
        } catch (error) { showMessage(error.message || 'Unable to connect to authentication. Please try again.'); }
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
        if (!form.reportValidity()) return;
        submit.disabled = true;
        submit.textContent = form.dataset.authForm === 'login' ? 'Logging in…' : 'Creating account…';
        form.setAttribute('aria-busy', 'true');
        try {
            const data = Object.fromEntries(new FormData(form));
            await TimeBankAuth.request(form.dataset.authForm, data);
            location.assign(TimeBankAuth.url(form.dataset.authForm === 'login' ? 'Pages/Member/Dashboard/' : 'Pages/Auth/Login/?registered=1'));
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
        } finally {
            submit.disabled = false;
            submit.textContent = normalLabel;
            form.removeAttribute('aria-busy');
        }
    });
    checkSession();
    window.addEventListener('pageshow', event => { if (event.persisted) checkSession(true); });
})();
