<script>
(function() {
    function initAutoFilter() {
        const forms = document.querySelectorAll('form[data-auto-filter="true"]');
        if (!forms.length) return;

        forms.forEach(function(form) {
            if (form.__autoFilterInitialized) return;
            form.__autoFilterInitialized = true;

            const searchInput = form.querySelector('input[name="search"]');
            let debounceTimer = null;
            let abortController = null;

            // Autofocus cursor at end of search text if search parameter is present on load
            if (searchInput) {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('search') && urlParams.get('search') !== '') {
                    searchInput.focus();
                    const valLength = searchInput.value.length;
                    try {
                        searchInput.setSelectionRange(valLength, valLength);
                    } catch (e) {
                        // ignore unsupported input types
                    }
                }
            }

            // Hide non-noscript submit buttons for a clean look
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitButtons.forEach(function(btn) {
                if (!btn.closest('noscript')) {
                    btn.classList.add('hidden');
                }
            });

            function setLoading(isLoading) {
                const metricsEl = document.getElementById('task-metrics-container');
                const tableEl = document.getElementById('task-table-container');
                const spinner = form.querySelector('.search-spinner');
                const searchIcon = form.querySelector('.search-icon');

                if (isLoading) {
                    if (metricsEl) metricsEl.classList.add('opacity-50', 'pointer-events-none', 'transition-opacity', 'duration-200');
                    if (tableEl) tableEl.classList.add('opacity-50', 'pointer-events-none', 'transition-opacity', 'duration-200');
                    if (spinner) spinner.classList.remove('hidden');
                    if (searchIcon) searchIcon.classList.add('opacity-0');
                } else {
                    if (metricsEl) metricsEl.classList.remove('opacity-50', 'pointer-events-none', 'transition-opacity', 'duration-200');
                    if (tableEl) tableEl.classList.remove('opacity-50', 'pointer-events-none', 'transition-opacity', 'duration-200');
                    if (spinner) spinner.classList.add('hidden');
                    if (searchIcon) searchIcon.classList.remove('opacity-0');
                }
            }

            async function applyFilter(resetPage = true) {
                const formData = new FormData(form);
                const action = form.getAttribute('action') || window.location.pathname;
                const url = new URL(action, window.location.origin);
                const params = new URLSearchParams();

                for (const [key, val] of formData.entries()) {
                    const trimmed = typeof val === 'string' ? val.trim() : val;
                    if (key === 'page') {
                        if (!resetPage && trimmed !== '') {
                            params.set(key, trimmed);
                        }
                        continue;
                    }
                    if (trimmed !== '') {
                        params.set(key, trimmed);
                    }
                }

                // Preserve back_to if existing in URL
                if (!params.has('back_to')) {
                    const currentUrlParams = new URLSearchParams(window.location.search);
                    if (currentUrlParams.has('back_to') && currentUrlParams.get('back_to') !== '') {
                        params.set('back_to', currentUrlParams.get('back_to'));
                    }
                }

                url.search = params.toString();
                const targetUrl = url.toString();

                // Abort any ongoing request to avoid double submits
                if (abortController) {
                    abortController.abort();
                }
                abortController = new AbortController();

                setLoading(true);

                try {
                    const response = await fetch(targetUrl, {
                        signal: abortController.signal,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html, application/xhtml+xml',
                        }
                    });

                    if (!response.ok) {
                        form.submit();
                        return;
                    }

                    const html = await response.text();
                    const parser = new DOMParser();
                    const newDoc = parser.parseFromString(html, 'text/html');

                    // Replace metrics container if present
                    const oldMetrics = document.getElementById('task-metrics-container');
                    const newMetrics = newDoc.getElementById('task-metrics-container');
                    if (oldMetrics && newMetrics) {
                        oldMetrics.innerHTML = newMetrics.innerHTML;
                    }

                    // Replace table container if present
                    const oldTable = document.getElementById('task-table-container');
                    const newTable = newDoc.getElementById('task-table-container');
                    if (oldTable && newTable) {
                        oldTable.innerHTML = newTable.innerHTML;
                    }

                    // Replace total count badge if present
                    const oldBadge = document.getElementById('task-total-badge');
                    const newBadge = newDoc.getElementById('task-total-badge');
                    if (oldBadge && newBadge) {
                        oldBadge.innerHTML = newBadge.innerHTML;
                    }

                    // Update URL bar without full page reload
                    window.history.replaceState({ url: targetUrl }, '', targetUrl);
                } catch (err) {
                    if (err.name === 'AbortError') {
                        return;
                    }
                    form.submit();
                } finally {
                    setLoading(false);
                }
            }

            // Dropdowns auto-apply on change
            form.querySelectorAll('select').forEach(function(select) {
                select.addEventListener('change', function() {
                    applyFilter(true);
                });
            });

            // Month / Year hidden inputs auto-apply on change
            form.querySelectorAll('input[type="hidden"]').forEach(function(hiddenInput) {
                if (hiddenInput.name === 'month' || hiddenInput.name === 'year') {
                    hiddenInput.addEventListener('change', function() {
                        applyFilter(true);
                    });
                }
            });

            // Search input with 500ms debounce & immediate on Enter
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        applyFilter(true);
                    }, 500);
                });

                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        clearTimeout(debounceTimer);
                        applyFilter(true);
                    }
                });
            }

            // Intercept form submit event (e.g. from Enter or requestSubmit)
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                clearTimeout(debounceTimer);
                applyFilter(true);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAutoFilter);
    } else {
        initAutoFilter();
    }
})();
</script>

