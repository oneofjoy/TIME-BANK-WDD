/** Injects an HTML partial into a mount element. */
async function loadComponent(mountId, filePath) {
    const mount = document.getElementById(mountId);
    if (!mount) throw new Error(`Mount element not found: ${mountId}`);

    const response = await fetch(filePath);
    if (!response.ok) {
        throw new Error(`Could not load ${filePath} (HTTP ${response.status})`);
    }

    mount.innerHTML = await response.text();
}
