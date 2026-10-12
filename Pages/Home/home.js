// Paths are relative to index.html at the project root.
const homepageParts = [
    ["navbar-mount", "./shared/navbar/navbar.html"],
    ["hero-mount", "./pages/home/sections/hero/hero.html"],
    ["about-mount", "./pages/home/sections/about/about.html"],
    ["explore-mount", "./pages/home/sections/explore/explore.html"],
    ["cta-mount", "./pages/home/sections/cta/cta.html"],
    ["footer-mount", "./shared/footer/footer.html"]
];

async function initHome() {
    await Promise.all(homepageParts.map(async ([mountId, path]) => {
        try {
            await loadComponent(mountId, path);
        } catch (error) {
            console.error(error);
            document.getElementById(mountId).innerHTML = '<p class="error-placeholder">This section could not be loaded.</p>';
        }
    }));
}
initHome();
