/* Native WP Store Locator 3 search; native Elementor Loop Grid rows. */
(function (hooks) {
    'use strict';
    if (!hooks) {
        return;
    }
    var autoload = true;
    var nativeGrid = null;
    var nativeRows = [];
    function wrapper() {
        return document.querySelector('#wpsl-wrap.mcp-wpsl-elementor');
    }
    function normalize(value) {
        return value.replace(/^(\d{3,10})(\p{L}[\p{L}\p{M}\s.'-]*)$/u, '$1 $2');
    }
    function normalizeInput(event) {
        var input = document.getElementById('wpsl-search-input');
        if (!input || !input.closest('.mcp-wpsl-columns, .mcp-wpsl-elementor')) {
            return;
        }
        if (event.type === 'submit' || (event.type === 'click' && event.target.closest('#wpsl-search-btn')) || (event.type === 'keydown' && event.key === 'Enter' && event.target === input)) {
            input.value = normalize(input.value.trim());
        }
    }
    document.addEventListener('submit', normalizeInput, true);
    document.addEventListener('click', normalizeInput, true);
    document.addEventListener('keydown', normalizeInput, true);
    document.addEventListener('click', function (event) {
        var link = event.target.closest && event.target.closest('.wpsl-skip-to-results');
        var directory = document.getElementById('mcp-wpsl-directory-results');
        if (!wrapper() || !link || !link.closest('.mcp-wpsl-elementor') || !directory) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        directory.focus({preventScroll: true});
        directory.scrollIntoView({block: 'start'});
    }, true);
    function synchronize(response) {
        if (!wrapper()) {
            return;
        }
        var grid = document.querySelector('.mcp-wpsl-directory .elementor-loop-container');
        if (!grid) {
            return;
        }
        if (nativeGrid !== grid) {
            nativeGrid = grid;
            nativeRows = Array.from(grid.children).filter(function (row) {
                return row.classList.contains('e-loop-item');
            });
        }
        var ids = (Array.isArray(response) ? response : []).map(function (store) {
            var data = store.properties || store;
            return String(data.id);
        });
        var byId = new Map();
        nativeRows.forEach(function (row) {
            var match = row.className.match(/(?:^|\s)e-loop-item-(\d+)(?:\s|$)/);
            if (match) {
                byId.set(match[1], row);
                if (!ids.includes(match[1])) {
                    row.remove();
                }
            }
        });
        if (autoload) {
            nativeRows.forEach(function (row) {
                var match = row.className.match(/(?:^|\s)e-loop-item-(\d+)(?:\s|$)/);
                if (match && ids.includes(match[1])) {
                    grid.appendChild(row);
                }
            });
        } else {
            ids.forEach(function (id) {
                if (byId.has(id)) {
                    grid.appendChild(byId.get(id));
                }
            });
        }
    }
    hooks.addFilter('wpslAjaxData', 'mcp-wpsl/elementor-directory', function (data) {
        if (wrapper() && data && data.action === 'store_search') {
            autoload = Boolean(data.autoload);
        }
        return data;
    });
    hooks.addAction('wpslAjaxResultsFound', 'mcp-wpsl/elementor-directory', synchronize);
    hooks.addAction('wpslAjaxNoResultsFound', 'mcp-wpsl/elementor-directory', function () {
        synchronize([]);
    });
    function observeStatus() {
        var ownWrapper = wrapper();
        if (!ownWrapper) {
            return;
        }
        var status = ownWrapper.querySelector('#wpsl-result-list');
        var list = ownWrapper.querySelector('#wpsl-stores ul');
        if (!status || !list) {
            return;
        }
        var update = function () {
            status.hidden = !list.textContent.trim();
            if (list.querySelector('.wpsl-no-results-msg')) {
                synchronize([]);
            }
        };
        new MutationObserver(update).observe(list, {childList: true, subtree: true});
        update();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', observeStatus, {once: true});
    } else {
        observeStatus();
    }
}(window.wp && window.wp.hooks));
