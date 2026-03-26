function rssslRenderThemeVulnerabilities() {
    if (
        typeof window.rssslVulnerabilities === 'undefined' ||
        !Array.isArray(window.rssslVulnerabilities.themes)
    ) {
        return;
    }

    const vulnerableThemes = window.rssslVulnerabilities.themes;

    vulnerableThemes.forEach((theme) => {
        if (
            !theme ||
            typeof theme.slug !== 'string' ||
            typeof theme.url !== 'string' ||
            typeof theme.label !== 'string' ||
            typeof theme.info !== 'string' ||
            typeof theme.severity !== 'string'
        ) {
            return;
        }
        const selectors = [
            ".theme[data-slug='" + theme.slug + "']",
            ".theme[data-theme='" + theme.slug + "']"
        ];

        let themeElement = null;
        for (const selector of selectors) {
            themeElement = document.querySelector(selector);
            if (themeElement) {
                break;
            }
        }

        // console.log('[rsssl] found theme element', themeElement);

        if (!themeElement) {
            return;
        }
        themeElement.classList.add('rsssl-vulnerable');

        const actionsContainer =
            themeElement.querySelector('.theme-id-container') ||
            themeElement;

        if (actionsContainer.querySelector('.rsssl-theme-vuln-block')) {
            return;
        }

        const block = document.createElement('div');
        block.classList.add('rsssl-theme-vuln-block', 'rsssl-' + theme.severity);

        const link = document.createElement('a');
        link.href = theme.url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.classList.add('rsssl-btn-vulnerable', 'rsssl-' + theme.severity);
        link.textContent = theme.label;
        link.title = theme.label;

        const text = document.createElement('span');
        text.classList.add('rsssl-theme-vuln-text');
        text.textContent = theme.info;

        block.appendChild(link);
        block.appendChild(text);

        actionsContainer.appendChild(block);
    });
}

// Try multiple times in case the themes grid is rendered asynchronously.
(function attachRssslThemeVulnerabilities() {
    let attempts = 0;
    const maxAttempts = 10;
    const delay = 400;

    function tryRender() {
        attempts += 1;
        rssslRenderThemeVulnerabilities();

        if (attempts < maxAttempts) {
            setTimeout(tryRender, delay);
        }
    }

    // Prefer WordPress' themes events when available.
    if (window.wp?.themes?.bind) {
        window.wp.themes.bind('ready', tryRender);
        window.wp.themes.bind('render', tryRender);
    } else {
        // Fallback for cases where wp.themes is not present.
        document.addEventListener('DOMContentLoaded', function () {
            tryRender();
        });
    }
})();
