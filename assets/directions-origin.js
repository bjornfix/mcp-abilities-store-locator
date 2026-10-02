/* Isolated candidate only. Uses WP Store Locator 3.0.3 frontend filters. */
(function (hooks, settings) {
    'use strict';
    if (!hooks || !settings || !settings.api || settings.api.provider !== 'osm') {
        return;
    }
    var currentOrigin = '';
    function columnsActive() {
        var wrapper = document.getElementById('wpsl-wrap');
        return wrapper && wrapper.classList.contains('mcp-wpsl-columns');
    }
    function coordinate(value, limit) {
        if ((typeof value !== 'number' && typeof value !== 'string') || String(value).trim() === '') {
            return null;
        }
        var number = Number(value);
        return Number.isFinite(number) && Math.abs(number) <= limit ? number : null;
    }
    hooks.addFilter('wpslAjaxData', 'mcp-wpsl/current-directions-origin', function (data) {
        if (!columnsActive() || !data || data.action !== 'store_search') {
            return data;
        }
        var lat = coordinate(data.lat, 90);
        var lng = coordinate(data.lng, 180);
        currentOrigin = lat !== null && lng !== null ? lat + ',' + lng : '';
        return data;
    });
    hooks.addFilter('wpslDirectionsUrl', 'mcp-wpsl/current-directions-origin', function (value) {
        if (!columnsActive() || !currentOrigin || typeof value !== 'string') {
            return value;
        }
        var url;
        try {
            url = new URL(value);
        } catch (error) {
            return value;
        }
        if (url.protocol !== 'https:' || url.hostname !== 'www.openstreetmap.org' || url.pathname !== '/directions') {
            return value;
        }
        url.searchParams.set('from', currentOrigin);
        return url.href;
    });
}(window.wp && window.wp.hooks, window.wpslSettings));
