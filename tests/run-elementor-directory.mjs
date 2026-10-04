import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const actions = new Map(), filters = new Map(), listeners = new Map();
const hooks = {
    addAction: (name, scope, callback) => actions.set(name, callback),
    addFilter: (name, scope, callback) => filters.set(name, callback),
};
const grid = {children: [], appendChild(row) { row.remove(); this.children.push(row); row.parent = this; }};
const makeRow = (id) => ({className: `elementor e-loop-item e-loop-item-${id} post-${id}`, classList: {contains: (name) => name === 'e-loop-item'}, parent: grid, remove() { if (this.parent) { this.parent.children = this.parent.children.filter((r) => r !== this); this.parent = null; } }});
const rows = [11,12,13].map(makeRow); grid.children = [...rows];
const status = {hidden: true}, list = {textContent: '', querySelector: () => null};
const wrapper = {querySelector: (selector) => selector === '#wpsl-result-list' ? status : list};
const input = {value: '0256Oslo', closest: () => wrapper};
const document = {
    readyState: 'complete',
    querySelector: (selector) => selector === '#wpsl-wrap.mcp-wpsl-elementor' ? wrapper : grid,
    getElementById: () => input,
    addEventListener: (name, callback) => listeners.set(name, callback),
};
vm.runInNewContext(fs.readFileSync(new URL('../assets/elementor-directory.js', import.meta.url),'utf8'), {window:{wp:{hooks}}, document, MutationObserver: class {observe(){}}, Map, Array, String, Boolean});
const found = actions.get('wpslAjaxResultsFound'), data = filters.get('wpslAjaxData');
const ids = () => grid.children.map((row) => Number(row.className.match(/e-loop-item-(\d+)/)[1]));
data({action:'store_search',autoload:1});found([{id:13},{id:11},{id:12}]);assert.deepEqual(ids(),[11,12,13]);
data({action:'store_search'});found([{id:13},{id:11}]);assert.deepEqual(ids(),[13,11]);assert.equal(grid.children[0],rows[2]);
found([{properties:{id:12}}]);assert.deepEqual(ids(),[12]);assert.equal(grid.children[0],rows[1]);
actions.get('wpslAjaxNoResultsFound')();assert.deepEqual(ids(),[]);
found([{id:11},{id:12},{id:13}]);assert.deepEqual(ids(),[11,12,13]);assert.equal(grid.children[0],rows[0]);
listeners.get('keydown')({type:'keydown',key:'Enter',target:input});assert.equal(input.value,'0256 Oslo');
input.value='W1A 1AA';listeners.get('submit')({type:'submit'});assert.equal(input.value,'W1A 1AA');
assert.equal(status.hidden,true);
console.log('PASS: native row identity, filtering, proximity order, restore, GeoJSON, no-results and postcode normalization');
