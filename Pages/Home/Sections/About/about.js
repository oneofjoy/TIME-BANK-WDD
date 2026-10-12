/** Motion stays in CSS; this initializer only handles the persistent pause control. */
function initAbout() {
    const visual = document.querySelector('.about-visual');
    if (!visual || visual.dataset.initialized) return;
    const toggle = visual.querySelector('.about-motion-toggle');
    if (!toggle) return;
    const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
    let paused = false;
    const update = () => {
        visual.classList.toggle('is-paused', paused);
        toggle.disabled = reducedMotion.matches;
        toggle.setAttribute('aria-pressed', String(paused));
        toggle.textContent = reducedMotion.matches ? 'Reduced motion enabled' : paused ? 'Resume animation' : 'Pause animation';
        toggle.setAttribute('aria-label', reducedMotion.matches ? 'Reduced motion enabled' : paused ? 'Resume cycle animation' : 'Pause cycle animation');
    };
    toggle.addEventListener('click', () => { paused = !paused; update(); });
    reducedMotion.addEventListener('change', update);
    update();
    toggle.hidden = false;
    visual.dataset.initialized = 'true';
}
