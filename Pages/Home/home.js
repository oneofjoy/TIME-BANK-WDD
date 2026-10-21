// Paths are relative to index.html at the project root.
const homepageParts = [
    ["navbar-mount", "./Shared/Navbar/navbar.html"],
    ["hero-mount", "./Pages/Home/Sections/Hero/hero.html"],
    ["about-mount", "./Pages/Home/Sections/About/about.html"],
    ["explore-mount", "./Pages/Home/Sections/Explore/explore.html"],
    ["cta-mount", "./Pages/Home/Sections/CTA/cta.html"],
    ["footer-mount", "./Shared/Footer/footer.html"]
];

// Navbar publishes the checked PHP session. Keep the CTA in sync even if the
// session response arrives before the CTA fragment finishes mounting.
let homepageMember = false;
function updateHomepageCta() {
    const link = document.querySelector('[data-home-cta]');
    if (!link) return;
    const path = homepageMember ? 'Pages/Member/Sessions/' : 'Pages/Auth/Register/';
    link.dataset.siteHref = path;
    link.href = TimeBankAuth.url(path);
    link.querySelector('[data-home-cta-label]').textContent = homepageMember ? 'Offer a Skill' : 'Join TimeBank';
    document.querySelector('[data-home-cta-description]').textContent = homepageMember
        ? 'Keep learning and sharing with your community.'
        : 'Join a community where knowledge grows through sharing.';
}
document.addEventListener('timebank:session-change', event => {
    homepageMember = event.detail.user?.role === 'member';
    updateHomepageCta();
});

async function initHome() {
    await Promise.all(homepageParts.map(async ([mountId, path]) => {
        try {
            await loadComponent(mountId, path);
        } catch (error) {
            console.error(`Could not load component: ${path}`, error);
            document.getElementById(mountId).innerHTML = '<p class="error-placeholder">This section could not be loaded.</p>';
            return;
        }

        // An interaction failure must not replace successfully loaded content.
        try {
            if (mountId === "navbar-mount") initNavbar();
            if (mountId === "about-mount") initAbout();
            if (mountId === "cta-mount") updateHomepageCta();
            if (mountId === "footer-mount") {
                document.querySelector("[data-copyright-year]").textContent = new Date().getFullYear();
            }
        } catch (error) {
            console.error(`Could not initialize interactions: ${mountId}`, error);
        }
    }));
    // Re-apply an initial hash after asynchronously loaded sections exist.
    const target = document.getElementById(location.hash.slice(1));
    if (target) target.scrollIntoView();
}
initHome();
