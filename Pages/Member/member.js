(async () => {
    const status = document.querySelector('.member-status');
    await Promise.all([
        (async () => {
            try { await loadComponent('navbar-mount', TimeBankAuth.url('Shared/Navbar/navbar.html')); initNavbar(); }
            catch { status.textContent = 'Navigation could not be loaded. Refresh the page to try again.'; status.hidden = false; }
        })(),
        (async () => {
            try {
                await loadComponent('footer-mount', TimeBankAuth.url('Shared/Footer/footer.html'));
                document.querySelector('[data-copyright-year]').textContent = new Date().getFullYear();
            } catch { status.textContent = 'The footer could not be loaded. Refresh the page to try again.'; status.hidden = false; }
        })()
    ]);
    window.addEventListener('pageshow', async event => {
        if (!event.persisted) return;
        try {
            const session = await TimeBankAuth.getSession(true);
            if (!session.user) location.replace(TimeBankAuth.url('Pages/Auth/Login/'));
        } catch {
            location.reload(); // Re-enter the PHP guard rather than retain a stale protected page.
        }
    });
})();
