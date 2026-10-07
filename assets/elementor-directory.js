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
    /* Fit the native Leaflet popup to its owning map without replacing its links. */
    var popupDefaults = new WeakMap();
    var popupMaps = new WeakMap();
    function popupPoint(value) {
        return Array.isArray(value) ? {x: value[0], y: value[1]} : value;
    }
    function fitPopup(map, popup) {
        if (!popup || !popup.isOpen()) {
            return;
        }
        var element = popup.getElement();
        var content = element && element.querySelector('.leaflet-popup-content');
        var info = content && content.querySelector('.wpsl-info-window');
        if (!info || content.children.length !== 1) {
            return;
        }
        var defaults = popupDefaults.get(popup);
        if (!defaults) {
            defaults = {
                maxWidth: popup.options.maxWidth,
                topLeft: popup.options.autoPanPaddingTopLeft,
                content: popup.getContent()
            };
            popupDefaults.set(popup, defaults);
            popup.on('remove', function () {
                popup.options.maxWidth = defaults.maxWidth;
                popup.options.autoPanPaddingTopLeft = defaults.topLeft;
                popup.setContent(defaults.content);
            });
        }
        var frame = map.getContainer();
        var frameBox = frame.getBoundingClientRect();
        var box = element.getBoundingClientRect();
        var padding = popupPoint(popup.options.autoPanPadding);
        var left = popupPoint(defaults.topLeft || padding);
        var right = popupPoint(popup.options.autoPanPaddingBottomRight || padding);
        var available = frame.clientWidth - left.x - right.x;
        var chrome = element.offsetWidth - content.offsetWidth;
        // Leaflet adds one pixel when applying its native content width.
        var maxWidth = Math.floor(available - chrome - 1);
        if (maxWidth < popup.options.minWidth) {
            return;
        }
        popup.options.maxWidth = Math.min(defaults.maxWidth, maxWidth);
        popup.options.autoPanPaddingTopLeft = defaults.topLeft;
        var zoom = frame.querySelector('.leaflet-control-zoom');
        if (zoom) {
            var zoomBox = zoom.getBoundingClientRect();
            var collision = Array.from(info.querySelectorAll('p, a')).some(function (part) {
                var partBox = part.getBoundingClientRect();
                return partBox.left < zoomBox.right && partBox.right > zoomBox.left &&
                    partBox.top < zoomBox.bottom && partBox.bottom > zoomBox.top;
            });
            if (box.width > available || collision) {
                popup.options.autoPanPaddingTopLeft = [left.x, Math.max(left.y, zoomBox.bottom - frameBox.top + padding.y)];
            }
        }
        var focus = document.activeElement;
        var retainFocus = info.contains(focus);
        // The public HTMLElement mode retains native link identity and handlers.
        popup.setContent(info);
        if (retainFocus && document.activeElement !== focus) {
            focus.focus({preventScroll: true});
        }
    }
    hooks.addAction('wpslMarkerClicked', 'mcp-wpsl/elementor-popup', function (marker, data, map) {
        var ownWrapper = wrapper();
        if (!ownWrapper || !map || typeof map.getContainer !== 'function' || !marker ||
            typeof marker.getPopup !== 'function' || !ownWrapper.contains(map.getContainer())) {
            return;
        }
        if (!popupMaps.has(map)) {
            var state = {popup: null};
            popupMaps.set(map, state);
            map.on('popupopen', function (event) {
                state.popup = event.popup;
                fitPopup(map, event.popup);
            });
            map.on('popupclose', function (event) {
                if (state.popup === event.popup) {
                    state.popup = null;
                }
            });
            map.on('resize', function () {
                fitPopup(map, state.popup);
            });
        }
        var popup = marker.getPopup();
        popupMaps.get(map).popup = popup && popup.isOpen() ? popup : null;
        fitPopup(map, popup);
    });
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
