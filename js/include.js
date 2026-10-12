// Resolve fragment links from the project root, even on nested host pages.
const componentBaseUrl = new URL("../", document.currentScript.src);

/** Injects an HTML partial into a mount element. */
async function loadComponent(mountId, filePath) {
    const mount = document.getElementById(mountId);
    if (!mount) throw new Error(`Mount element not found: ${mountId}`);

    const response = await fetch(filePath, { cache: "no-cache" });
    if (!response.ok) {
        throw new Error(`Could not load ${filePath} (HTTP ${response.status})`);
    }

    mount.innerHTML = await response.text();
    mount.querySelectorAll("[data-site-href]").forEach(element => {
        element.href = new URL(element.dataset.siteHref, componentBaseUrl).href;
    });
    mount.querySelectorAll("[data-site-src]").forEach(element => {
        element.src = new URL(element.dataset.siteSrc, componentBaseUrl).href;
    });
}
