/** Initialize the shared fragment only after it has been inserted. */
function initNavbar() {
    const navbar = document.querySelector('.site-navbar');
    if (!navbar || navbar.dataset.initialized) return;
    navbar.dataset.initialized = 'true';
    const toggle = navbar.querySelector('.site-menu-toggle');
    const navigation = navbar.querySelector('.site-navigation');
    const desktop = matchMedia('(min-width: 1024px)');
    const profileToggle = navbar.querySelector('.site-profile-toggle');
    const profileLinks = navbar.querySelector('.site-profile-links');
    const status = navbar.querySelector('.site-auth-status');
    const retry = navbar.querySelector('.site-auth-retry');
    const setProfileOpen = (open, restore = false) => {
        profileLinks.hidden = !open;
        profileToggle.setAttribute('aria-expanded', String(open));
        if (restore) profileToggle.focus();
    };
    const setOpen = open => {
        navbar.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        navigation.inert = !desktop.matches && !open;
        if (!open) setProfileOpen(false);
    };
    toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
    profileToggle.addEventListener('click', () => setProfileOpen(profileLinks.hidden));
    profileToggle.addEventListener('keydown', event => {
        if (event.key === 'ArrowDown') { event.preventDefault(); setProfileOpen(true); profileLinks.querySelector('a').focus(); }
    });
    navbar.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            if (!profileLinks.hidden) { setProfileOpen(false, true); event.stopPropagation(); }
            else if (navbar.classList.contains('is-open')) { setOpen(false); toggle.focus(); }
        }
    });
    document.addEventListener('click', event => { if (!event.target.closest('.site-profile')) setProfileOpen(false); });
    navbar.querySelector('.site-profile').addEventListener('focusout', event => {
        if (!event.currentTarget.contains(event.relatedTarget)) setProfileOpen(false);
    });
    navigation.addEventListener('click', event => { if (event.target.closest('a')) setOpen(false); });
    desktop.addEventListener('change', () => setOpen(false));
    const updateClearance = () => {
        const gap = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--navbar-gap'));
        document.documentElement.style.setProperty('--anchor-offset', `${navbar.offsetHeight + gap + 20}px`);
    };
    new ResizeObserver(updateClearance).observe(navbar);
    const markCurrent = () => {
        navbar.querySelectorAll('.site-nav-link').forEach(link => {
            link.removeAttribute('aria-current');
            const target = new URL(link.href);
            const isHome = location.pathname === componentBaseUrl.pathname || location.pathname === new URL('index.html', componentBaseUrl).pathname;
            if (target.hash === '#hero' && isHome || !target.hash && location.pathname.startsWith(target.pathname)) link.setAttribute('aria-current', 'page');
        });
    };
    const showError = message => {
        status.hidden = false;
        navbar.querySelector('[data-auth-message]').textContent = message;
        retry.hidden = false;
    };
    let generation = 0;
    async function refreshAuth(refresh = false) {
        const requestId = ++generation;
        retry.hidden = true;
        status.hidden = false;
        navbar.querySelector('[data-auth-message]').textContent = 'Checking account…';
        navbar.setAttribute('aria-busy', 'true');
        try {
            const session = await TimeBankAuth.getSession(refresh);
            if (requestId !== generation) return;
            const member = Boolean(session.user);
            navbar.querySelector('[data-public-links]').hidden = member;
            navbar.querySelector('[data-public-actions]').hidden = member;
            navbar.querySelector('[data-member-links]').hidden = !member;
            navbar.querySelector('[data-member-actions]').hidden = !member;
            setProfileOpen(false);
            if (member) {
                navbar.querySelector('[data-user-name]').textContent = session.user.full_name;
                profileToggle.title = session.user.full_name;
                navbar.querySelector('[data-user-initial]').textContent = Array.from(session.user.full_name)[0]?.toUpperCase() || '';
                const balance = session.user.available_credits;
                navbar.querySelector('[data-wallet-balance]').textContent = balance === null ? 'Unavailable' : new Intl.NumberFormat('en', { maximumFractionDigits: 2 }).format(Number(balance));
                navbar.querySelector('.site-wallet').setAttribute('aria-label', balance === null ? 'Time Wallet: balance unavailable' : `Time Wallet: ${balance} Time Credits`);
            }
            status.hidden = true;
            markCurrent();
        } catch {
            if (requestId !== generation) return;
            navbar.querySelector('[data-public-links]').hidden = false;
            navbar.querySelector('[data-public-actions]').hidden = true;
            navbar.querySelector('[data-member-links]').hidden = true;
            navbar.querySelector('[data-member-actions]').hidden = true;
            showError('Unable to check your account.');
        } finally {
            if (requestId === generation) navbar.removeAttribute('aria-busy');
        }
    }
    retry.addEventListener('click', () => refreshAuth(true));
    navbar.querySelector('.site-logout').addEventListener('click', async event => {
        const button = event.currentTarget;
        if (button.disabled) return;
        button.disabled = true;
        button.textContent = 'Logging out…';
        try {
            await TimeBankAuth.request('logout', {});
            location.replace(TimeBankAuth.url('index.html#hero'));
        } catch (error) {
            setProfileOpen(false, true);
            showError(error.message || 'Unable to log out. Please try again.');
        } finally {
            button.disabled = false;
            button.textContent = 'Log Out';
        }
    });
    window.addEventListener('pageshow', event => { if (event.persisted) refreshAuth(true); });
    setOpen(false);
    updateClearance();
    refreshAuth();
}
