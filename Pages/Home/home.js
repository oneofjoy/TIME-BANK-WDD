// Paths are relative to index.html at the project root.
const homepageParts = [
    ["navbar-mount", "./Shared/Navbar/navbar.html"],
    ["hero-mount", "./Pages/Home/Sections/Hero/hero.html"],
    ["about-mount", "./Pages/Home/Sections/About/about.html"],
    ["explore-mount", "./Pages/Home/Sections/Explore/explore.html"],
    ["cta-mount", "./Pages/Home/Sections/CTA/cta.html"],
    ["footer-mount", "./Shared/Footer/footer.html"]
];

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
