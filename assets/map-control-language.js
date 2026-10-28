/* Names only: keep the native Leaflet controls, events, focus and geometry. */
(function (hooks) {
    'use strict';
    if (!hooks) { return; }
    var observed = new WeakSet();
    var localizedLayers = new WeakSet();
    function localizeRegion() {
        var leaflet = window.L;
        var Layer = leaflet && leaflet.MaplibreGL;
        if (!labels.map_region_label || !Layer || typeof Layer.addInitHook !== 'function'
            || typeof leaflet.setOptions !== 'function' || localizedLayers.has(Layer)) { return; }
        Layer.addInitHook(function () {
            var current = this.options && this.options.locale;
            if (current !== undefined && current !== null
                && (typeof current !== 'object' || Array.isArray(current))) { return; }
            if (current && Object.prototype.hasOwnProperty.call(current, 'Map.Title')) { return; }
            var locale = Object.assign({}, current || {});
            locale['Map.Title'] = labels.map_region_label;
            leaflet.setOptions(this, {locale: locale});
        });
        localizedLayers.add(Layer);
    }

    function bind(frame) {
        if (!frame || observed.has(frame)) { return; }
        observed.add(frame);
        function update() {
            [['zoom_in_label', '.leaflet-control-zoom-in'],
             ['zoom_out_label', '.leaflet-control-zoom-out'],
             ['popup_close_label', '.leaflet-popup-close-button']].forEach(function (pair) {
                if (!labels[pair[0]]) { return; }
                frame.querySelectorAll(pair[1]).forEach(function (control) {
                    control.setAttribute('title', labels[pair[0]]);
                    control.setAttribute('aria-label', labels[pair[0]]);
                });
            });
        }
        update();
        new MutationObserver(update).observe(frame, {childList: true, subtree: true});
    }
    hooks.addFilter('wpslMapOptions', 'mcp-wpsl/map-control-language', function (options, mapId) {
        var frame = document.getElementById(mapId);
        if (frame && frame.matches('.wpsl-canvas-osm, .wpsl-canvas-stadia')) { localizeRegion(); bind(frame); }
        return options;
    });
}(window.wp && window.wp.hooks));
