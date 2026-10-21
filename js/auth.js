// PHP sessions are the only source of authentication state.
window.TimeBankAuth = (() => {
    let sessionRequest = null;
    const url = siteUrl;
    // Explicitly chosen local preview: no PHP requests or invented session state.
    const isLocalPreview = isLocalFilePreview;
    const serverUrl = path => new URL(path, 'http://localhost/TIME-BANK-WDD/').href;
    // Local Live Server cannot execute PHP. Keep the entire page and its session
    // on the configured XAMPP origin instead of requesting PHP across origins.
    const isLivePreview = ['localhost', '127.0.0.1', '[::1]'].includes(location.hostname)
        && ['5500', '5501'].includes(location.port)
        && componentBaseUrl.pathname === '/TIME-BANK-WDD/';
    if (isLivePreview) {
        const xamppPage = new URL(location.pathname + location.search + location.hash, 'http://localhost');
        location.replace(xamppPage.href);
    }
    async function readResponse(response) {
        let data;
        try { data = await response.json(); }
        catch { throw new Error('We couldn’t connect. Please try again in a moment.'); }
        if (!response.ok || data.success !== true) {
            const error = new Error(data.message || 'The request could not be completed.');
            error.status = response.status;
            error.fields = data.errors || {};
            throw error;
        }
        return data;
    }
    function getSession(refresh = false) {
        if (isLocalPreview) return Promise.resolve({ user: null, local_preview: true });
        // The replacement document will initialize authentication on Apache.
        if (isLivePreview) return new Promise(() => {});
        if (refresh || !sessionRequest) {
            sessionRequest = fetch(url('php/get-session.php'), { credentials: 'same-origin', cache: 'no-store' })
                .then(readResponse).catch(error => {
                    sessionRequest = null;
                    if (error instanceof TypeError) throw new Error('We couldn’t connect. Please check your connection and try again.');
                    throw error;
                });
        }
        return sessionRequest;
    }
    async function request(endpoint, body) {
        if (isLocalPreview) throw new Error('Open TimeBank on the local server to create an account or log in.');
        const session = await getSession();
        try {
            const response = await fetch(url(`php/${endpoint}.php`), {
                method: 'POST', credentials: 'same-origin', cache: 'no-store',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': session.csrf_token },
                body: JSON.stringify(body)
            });
            return await readResponse(response);
        } catch (error) {
            if (error instanceof TypeError) throw new Error('We couldn’t connect. Please check your connection and try again.');
            throw error;
        } finally {
            sessionRequest = null;
        }
    }
    return { getSession, request, url, isLocalPreview, serverUrl };
})();
