/**
 * Instant Students ↔ Faculty & Staff tab switching via fetch + cache.
 */
(function () {
    const ROOT_SEL = '[data-patrons-root]';
    const TAB_SEL = '[data-patrons-tab]';
    const cache = new Map();
    let bound = false;

    function currentRoot() {
        return document.querySelector(ROOT_SEL);
    }

    function samePath(a, b) {
        try {
            const left = new URL(a, window.location.origin);
            const right = new URL(b, window.location.origin);
            return left.pathname === right.pathname;
        } catch (e) {
            return a === b;
        }
    }

    async function fetchPanel(url) {
        if (cache.has(url)) {
            return cache.get(url);
        }

        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'text/html',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Failed to load patrons panel');
        }

        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const panel = doc.querySelector(ROOT_SEL);

        if (!panel) {
            throw new Error('Patrons panel missing in response');
        }

        const markup = panel.outerHTML;
        cache.set(url, markup);
        return markup;
    }

    function swapPanel(markup, url) {
        const root = currentRoot();
        if (!root) {
            window.location.href = url;
            return;
        }

        const apply = () => {
            root.outerHTML = markup;
            history.pushState({ patronsTab: url }, '', url);
            bindTabs();
            prefetchOtherTab();
        };

        if (typeof document.startViewTransition === 'function') {
            document.startViewTransition(apply);
        } else {
            apply();
        }
    }

    async function navigateTo(url, { push = true } = {}) {
        const root = currentRoot();
        if (!root) {
            window.location.href = url;
            return;
        }

        if (samePath(url, window.location.href) && push) {
            return;
        }

        root.setAttribute('aria-busy', 'true');
        root.classList.add('is-switching');

        try {
            const markup = await fetchPanel(url);
            if (!push) {
                const apply = () => {
                    currentRoot().outerHTML = markup;
                    bindTabs();
                    prefetchOtherTab();
                };
                if (typeof document.startViewTransition === 'function') {
                    document.startViewTransition(apply);
                } else {
                    apply();
                }
                return;
            }
            swapPanel(markup, url);
        } catch (e) {
            window.location.href = url;
        }
    }

    function prefetchOtherTab() {
        document.querySelectorAll(TAB_SEL).forEach((tab) => {
            if (tab.classList.contains('is-active') || tab.getAttribute('aria-current') === 'page') {
                return;
            }
            const url = tab.href;
            if (!url || cache.has(url)) {
                return;
            }
            fetchPanel(url).catch(() => {});
        });
    }

    function onTabClick(event) {
        const tab = event.currentTarget;
        if (
            event.defaultPrevented ||
            event.button !== 0 ||
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey
        ) {
            return;
        }

        event.preventDefault();
        navigateTo(tab.href);
    }

    function bindTabs() {
        document.querySelectorAll(TAB_SEL).forEach((tab) => {
            tab.removeEventListener('click', onTabClick);
            tab.addEventListener('click', onTabClick);
        });
    }

    function onPopState() {
        navigateTo(window.location.href, { push: false });
    }

    function init() {
        if (!currentRoot()) {
            return;
        }

        const root = currentRoot();
        cache.set(window.location.href, root.outerHTML);

        bindTabs();
        prefetchOtherTab();

        if (!bound) {
            window.addEventListener('popstate', onPopState);
            bound = true;
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
