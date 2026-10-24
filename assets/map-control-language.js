/* Names only: keep the native Leaflet controls, events, focus and geometry. */
(function (hooks) {
    'use strict';
    if (!hooks) { return; }
    var observed = new WeakSet();
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
        if (frame && frame.matches('.wpsl-canvas-osm, .wpsl-canvas-stadia')) { bind(frame); }
        return options;
    });
}(window.wp && window.wp.hooks));
