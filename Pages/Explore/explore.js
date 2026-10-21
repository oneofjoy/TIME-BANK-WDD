(() => {
    const form = document.querySelector('.explore-filters');
    const search = document.getElementById('explore-search');
    const category = document.getElementById('explore-category');
    const clear = document.querySelector('.explore-clear');
    const retry = document.querySelector('.explore-retry');
    const grid = document.getElementById('explore-grid');
    const catalog = document.querySelector('.explore-catalog');
    const state = document.getElementById('explore-state');
    const count = document.getElementById('explore-results-count');
    const illustrations = {
        programming: 'programming', design: 'design', languages: 'languages',
        'data-analysis': 'data-analysis', business: 'business', 'other-skills': 'other-skills'
    };
    let pending;
    let timer;
    let generation = 0;
    let localOfferings;
    let currentUser = null;
    let sessionKnown = TimeBankAuth.isLocalPreview;
    const bookingDialog = document.getElementById('explore-booking');
    const bookingForm = document.getElementById('explore-booking-form');
    const bookingTime = document.getElementById('booking-time');
    const bookingSubmit = bookingForm.querySelector('[type="submit"]');
    const bookingMessage = bookingForm.querySelector('[data-booking-message]');
    const bookingRetry = bookingForm.querySelector('[data-retry-booking]');
    let bookingTimes = [];
    let bookingRequest;
    let bookingOpener;
    let bookingClass;
    let joining = false;
    const element = (tag, className, text) => {
        const node = document.createElement(tag);
        node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    };
    const asset = path => new URL(path, componentBaseUrl).href;
    function showState(title, description, failed = false) {
        grid.replaceChildren();
        state.hidden = false;
        document.getElementById('explore-state-title').textContent = title;
        document.getElementById('explore-state-description').textContent = description;
        retry.hidden = !failed;
    }
    function card(offering) {
        const tone = Object.hasOwn(illustrations, offering.category_slug) ? offering.category_slug : 'other-skills';
        const article = element('article', `explore-card explore-tone-${tone}`);
        const thumbnail = element('div', 'explore-thumbnail');
        const image = element('img', 'explore-thumbnail-icon');
        image.src = asset(`Assets/Illustrations/Skills/${illustrations[tone]}.svg`);
        image.alt = '';
        image.width = 320;
        image.height = 160;
        thumbnail.append(image);
        const body = element('div', 'explore-card-body');
        body.append(element('span', 'explore-category-tag', offering.category_slug === 'other-skills' ? 'Others' : offering.category_name));
        body.append(element('h2', '', offering.title));
        body.append(element('p', 'explore-card-description', offering.description || 'No description provided.'));
        const tutor = element('div', 'explore-tutor');
        const initials = offering.tutor_name.trim().split(/\s+/).slice(0, 2).map(part => Array.from(part)[0]).join('').toUpperCase();
        const avatar = element('span', 'explore-tutor-avatar', initials);
        avatar.setAttribute('aria-hidden', 'true');
        renderSiteAvatar(avatar, offering.tutor_avatar, initials, 'explore-avatar-image');
        tutor.append(avatar, element('span', 'explore-tutor-name', offering.tutor_name));
        const meta = element('div', 'explore-card-meta');
        const duration = element('span', 'explore-card-duration', `${offering.duration_minutes} min`);
        const durationIcon = element('img', '');
        durationIcon.src = asset('Assets/Icons/time-credit.svg');
        durationIcon.alt = '';
        durationIcon.width = durationIcon.height = 18;
        duration.prepend(durationIcon);
        duration.setAttribute('aria-label', `Duration: ${offering.duration_minutes} minutes`);
        const cost = element('span', 'explore-card-cost');
        const creditIcon = element('img', '');
        creditIcon.src = asset('Assets/Icons/credits.svg');
        creditIcon.alt = '';
        creditIcon.width = creditIcon.height = 18;
        const credits = new Intl.NumberFormat('en', { maximumFractionDigits: 2 }).format(Number(offering.credit_cost));
        cost.append(creditIcon, element('span', '', `${credits} Time ${Number(offering.credit_cost) === 1 ? 'Credit' : 'Credits'}`));
        meta.append(duration, cost);
        body.append(tutor, meta);
        const action = element('div', 'explore-card-action');
        action.dataset.classId = offering.offering_id;
        action.dataset.tutorId = offering.tutor_id ?? '';
        action.dataset.classTitle = offering.title;
        setClassAction(action);
        body.append(action);
        article.append(thumbnail, body);
        return article;
    }
    function setClassAction(container) {
        let control;
        if (!sessionKnown) {
            control = element('button', 'site-button site-button--secondary', 'Join Class');
            control.disabled = true;
        } else if (!currentUser) {
            control = element('a', 'site-button site-button--secondary', 'Log In to Join');
            control.href = TimeBankAuth.url('Pages/Auth/Login/?next=explore');
        } else if (Number(container.dataset.tutorId) === currentUser.user_id) {
            control = element('a', 'site-button site-button--secondary', 'Manage Class');
            control.href = TimeBankAuth.url('Pages/Member/Skills/#skill-' + container.dataset.classId);
        } else {
            control = element('button', 'site-button', 'Join Class');
            control.type = 'button';
            control.addEventListener('click', () => openBooking(container, control));
        }
        container.replaceChildren(control);
    }
    document.addEventListener('timebank:session-change', event => {
        currentUser = event.detail.user;
        sessionKnown = true;
        grid.querySelectorAll('.explore-card-action').forEach(setClassAction);
    });
    function bookingNotice(message) {
        bookingMessage.textContent = message;
        bookingMessage.hidden = !message;
    }
    function showCost() {
        const time = bookingTimes.find(item => String(item.availability_id) === bookingTime.value);
        document.querySelector('[data-booking-cost]').textContent = time
            ? `${time.duration_minutes} minutes · ${Number(time.credit_cost)} Time ${Number(time.credit_cost) === 1 ? 'Credit' : 'Credits'}` : '';
        bookingSubmit.disabled = joining || !time;
    }
    async function openBooking(container, opener) {
        if (joining) return;
        bookingRequest?.abort();
        const request = bookingRequest = new AbortController();
        bookingTimes = [];
        bookingOpener = opener;
        bookingClass = container;
        bookingForm.reset();
        bookingNotice('');
        bookingRetry.hidden = true;
        bookingTime.replaceChildren(new Option('Loading times…', ''));
        bookingTime.disabled = bookingSubmit.disabled = true;
        document.querySelector('[data-booking-class]').textContent = container.dataset.classTitle;
        showCost();
        if (!bookingDialog.open) bookingDialog.showModal();
        const url = new URL('php/get-class-times.php', componentBaseUrl);
        url.searchParams.set('offering_id', container.dataset.classId);
        try {
            const response = await fetch(url, { signal: request.signal, credentials: 'same-origin', cache: 'no-store' });
            const data = await response.json();
            if (!response.ok || data.success !== true || !Array.isArray(data.times)) throw new Error('Could not load class times.');
            if (request !== bookingRequest || !bookingDialog.open) return;
            bookingTimes = data.times;
            bookingTime.replaceChildren(new Option(bookingTimes.length ? 'Choose a time' : 'No times available', ''));
            const formatter = new Intl.DateTimeFormat('en-GB', {dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta'});
            const endFormatter = new Intl.DateTimeFormat('en-GB', {hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta'});
            bookingTimes.forEach(time => bookingTime.add(new Option(`${formatter.format(new Date(time.start_at))}–${endFormatter.format(new Date(time.end_at))} WIB`, time.availability_id)));
            bookingTime.disabled = !bookingTimes.length;
            if (!bookingTimes.length) { bookingNotice('No teaching times are available right now. Please check back soon.'); bookingRetry.hidden = false; }
            else bookingTime.focus();
        } catch (error) {
            if (error.name !== 'AbortError' && request === bookingRequest && bookingDialog.open) {
                bookingTime.replaceChildren(new Option('Times could not be loaded', ''));
                bookingNotice('We couldn’t load the times. Please try again.');
                bookingRetry.hidden = false;
            }
        }
    }
    bookingRetry.addEventListener('click', () => openBooking(bookingClass, bookingOpener));
    bookingTime.addEventListener('change', showCost);
    document.querySelectorAll('[data-close-booking]').forEach(button => button.addEventListener('click', () => {
        if (bookingForm.getAttribute('aria-busy') !== 'true') bookingDialog.close();
    }));
    bookingDialog.addEventListener('cancel', event => { if (bookingForm.getAttribute('aria-busy') === 'true') event.preventDefault(); });
    bookingDialog.addEventListener('close', () => { bookingRequest?.abort(); bookingOpener?.focus(); });
    bookingForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (joining || bookingSubmit.disabled || !bookingForm.reportValidity()) return;
        joining = true;
        bookingSubmit.disabled = true;
        bookingTime.disabled = true;
        bookingRetry.hidden = true;
        bookingDialog.querySelectorAll('[data-close-booking]').forEach(button => { button.disabled = true; });
        bookingSubmit.textContent = 'Joining…';
        bookingForm.setAttribute('aria-busy', 'true');
        bookingNotice('');
        let joined = false;
        try {
            await TimeBankAuth.request('join-class', { availability_id: Number(bookingTime.value) });
            joined = true;
            location.assign(TimeBankAuth.url('Pages/Member/Sessions/'));
        } catch (error) {
            bookingNotice(error.message || 'We couldn’t join the class. Please try again.');
            if (error.status === 401) location.assign(TimeBankAuth.url('Pages/Auth/Login/?next=explore'));
            if (error.status === 409) {
                // Recheck availability before retrying a time that may have been booked.
                bookingTimes = [];
                bookingTime.replaceChildren(new Option('Refresh times to try again', ''));
                bookingRetry.hidden = false;
            }
        } finally {
            if (!joined) {
                joining = false;
                bookingForm.removeAttribute('aria-busy');
                bookingSubmit.textContent = 'Join Class';
                bookingTime.disabled = !bookingTimes.length;
                bookingDialog.querySelectorAll('[data-close-booking]').forEach(button => { button.disabled = false; });
                showCost();
            }
        }
    });
    function updateClear() { clear.disabled = !search.value && !category.value; }
    function renderOfferings(offerings, filtered) {
        count.textContent = `${offerings.length} ${offerings.length === 1 ? 'class' : 'classes'} found`;
        if (offerings.length) {
            state.hidden = true;
            grid.replaceChildren(...offerings.map(card));
        } else if (filtered) {
            showState('No classes match your search.', 'Try another search or category, or clear the filters to see all classes.');
        } else {
            showState('No classes yet.', 'New classes will appear here when someone shares a skill. Check back soon.');
        }
    }
    function filterLocalOfferings(keyword) {
        // The snapshot contains only offerings returned by the public catalog API.
        // Local files use the bundled catalog; HTTP always reads current database data.
        if (!localOfferings) {
            const snapshot = JSON.parse(document.getElementById('explore-local-catalog').textContent);
            if (!Array.isArray(snapshot.offerings)) throw new Error('Invalid catalog snapshot.');
            localOfferings = snapshot.offerings;
        }
        const normalize = value => String(value || '').normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();
        const query = normalize(keyword);
        return localOfferings.filter(offering =>
            (!category.value || offering.category_slug === category.value)
            && (!query || [offering.title, offering.skill_name, offering.description].some(value => normalize(value).includes(query)))
        );
    }
    async function loadSkills() {
        clearTimeout(timer);
        pending?.abort();
        pending = new AbortController();
        const requestId = ++generation;
        const url = new URL('php/get-skills.php', componentBaseUrl);
        const keyword = search.value.trim();
        if (keyword) url.searchParams.set('q', keyword);
        if (category.value) url.searchParams.set('category', category.value);
        updateClear();
        catalog.setAttribute('aria-busy', 'true');
        count.textContent = 'Loading classes…';
        showState('Loading classes…', 'Getting the latest classes.');
        try {
            if (TimeBankAuth.isLocalPreview) {
                renderOfferings(filterLocalOfferings(keyword), Boolean(keyword || category.value));
                return;
            }
            const response = await fetch(url, { signal: pending.signal, credentials: 'same-origin', cache: 'no-store' });
            const data = await response.json();
            if (!response.ok || data.success !== true || !Array.isArray(data.offerings)) {
                throw new Error('Catalog request failed.');
            }
            if (requestId !== generation) return;
            renderOfferings(data.offerings, Boolean(keyword || category.value));
        } catch (error) {
            if (error.name === 'AbortError' || requestId !== generation) return;
            count.textContent = 'Classes could not be loaded';
            showState('We couldn’t load the classes.', 'Please check your connection and try again in a moment.', true);
        } finally {
            if (requestId === generation) catalog.setAttribute('aria-busy', 'false');
        }
    }
    // Cancel stale requests as soon as a filter changes, before the debounce ends.
    function scheduleSearch() {
        pending?.abort();
        ++generation;
        clearTimeout(timer);
        updateClear();
        timer = setTimeout(loadSkills, 300);
    }
    async function mountShared(mountId, path) {
        try {
            await loadComponent(mountId, asset(path));
            if (mountId === 'navbar-mount') initNavbar();
            else document.querySelector('[data-copyright-year]').textContent = new Date().getFullYear();
        } catch {
            const notice = element('p', 'error-placeholder', 'Part of this page couldn’t load. Please refresh and try again.');
            document.getElementById(mountId).replaceChildren(notice);
        }
    }
    mountShared('navbar-mount', 'Shared/Navbar/navbar.html');
    mountShared('footer-mount', 'Shared/Footer/footer.html');
    form.addEventListener('submit', event => { event.preventDefault(); loadSkills(); });
    search.addEventListener('input', scheduleSearch);
    category.addEventListener('change', loadSkills);
    clear.addEventListener('click', () => {
        search.value = '';
        category.value = '';
        search.focus();
        loadSkills();
    });
    retry.addEventListener('click', loadSkills);
    loadSkills();
    window.addEventListener('pageshow', event => { if (event.persisted) loadSkills(); });
})();
