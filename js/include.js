// Resolve fragment links from the project root, even on nested host pages.
const componentBaseUrl = new URL("../", document.currentScript.src);
const isLocalFilePreview = componentBaseUrl.protocol === "file:";

// Use explicit page filenames so navigation also works when opening local files.
function siteUrl(path) {
    const target = new URL(path, componentBaseUrl);
    const explorePath = new URL("Pages/Explore/", componentBaseUrl).pathname;
    if ([explorePath, `${explorePath}index.php`, `${explorePath}index.html`].includes(target.pathname)) {
        target.pathname = `${explorePath}explore.html`;
    }
    // A fresh document URL also avoids reusing the older registration markup.
    const registerPath = new URL("Pages/Auth/Register/", componentBaseUrl).pathname;
    if ([registerPath, `${registerPath}index.html`].includes(target.pathname)) {
        target.searchParams.set("v", "20261021-starter-cancel-1");
    }
    if (isLocalFilePreview) {
        // Protected pages need PHP even when reached from a double-click preview.
        const memberPath = new URL("Pages/Member/", componentBaseUrl).pathname;
        if (target.pathname.startsWith(memberPath)) {
            return new URL(target.pathname.slice(componentBaseUrl.pathname.length) + target.search + target.hash,
                "http://localhost/TIME-BANK-WDD/").href;
        }
        for (const page of ["Login", "Register"]) {
            const authPath = new URL(`Pages/Auth/${page}/`, componentBaseUrl).pathname;
            if (target.pathname === authPath) target.pathname += "index.html";
        }
    }
    return target.href;
}

// Portraits come only from the built-in library; never load an arbitrary URL.
function renderSiteAvatar(container, path, initials, imageClass = '') {
    container.textContent = initials;
    if (typeof path !== 'string' || !/^Assets\/Avatars\/avatar-0[1-8]\.svg$/.test(path)) return;
    const portrait = document.createElement('img');
    portrait.src = siteUrl(path);
    portrait.alt = '';
    portrait.className = imageClass;
    portrait.width = portrait.height = 88;
    portrait.addEventListener('error', () => { container.textContent = initials; }, { once: true });
    container.replaceChildren(portrait);
}

// Remote fonts are optional and must never block offline first paint.
document.querySelectorAll("[data-site-fonts]").forEach(sheet => {
    if (isLocalFilePreview) {
        sheet.remove();
        return;
    }
    const apply = () => { sheet.media = "all"; };
    sheet.addEventListener("load", apply, { once: true });
    if (sheet.sheet) apply();
});

let localComponentsRequest;
function loadLocalComponents() {
    if (!localComponentsRequest) {
        localComponentsRequest = new Promise((resolve, reject) => {
            const script = document.createElement("script");
            script.src = new URL("js/local-components.js?v=20261021-daily-avatar-1", componentBaseUrl).href;
            script.onload = () => {
                if (window.TimeBankLocalComponents) resolve(window.TimeBankLocalComponents);
                else reject(new Error("Local component snapshot is missing."));
            };
            script.onerror = () => reject(new Error("Could not load the local component snapshot."));
            document.head.append(script);
        });
    }
    return localComponentsRequest;
}

/** Injects an HTML partial into a mount element. */
async function loadComponent(mountId, filePath) {
    const mount = document.getElementById(mountId);
    if (!mount) throw new Error(`Mount element not found: ${mountId}`);

    let html;
    if (isLocalFilePreview) {
        const target = new URL(filePath, document.baseURI);
        if (target.protocol !== "file:" || !target.pathname.startsWith(componentBaseUrl.pathname)) {
            throw new Error(`Component is outside the project: ${filePath}`);
        }
        const key = decodeURIComponent(target.pathname.slice(componentBaseUrl.pathname.length));
        const components = await loadLocalComponents();
        if (!Object.hasOwn(components, key)) throw new Error(`Local component not found: ${key}`);
        html = components[key];
    } else {
        const response = await fetch(filePath, { cache: "no-cache" });
        if (!response.ok) {
            throw new Error(`Could not load ${filePath} (HTTP ${response.status})`);
        }
        html = await response.text();
    }

    // Only project-owned HTML fragments are mounted here, never user/database data.
    mount.innerHTML = html;
    mount.querySelectorAll("[data-site-href]").forEach(element => {
        element.href = siteUrl(element.dataset.siteHref);
    });
    mount.querySelectorAll("[data-site-src]").forEach(element => {
        element.src = new URL(element.dataset.siteSrc, componentBaseUrl).href;
    });
}
