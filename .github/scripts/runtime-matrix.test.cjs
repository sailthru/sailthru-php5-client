const {test} = require('node:test');
const assert = require('node:assert/strict');
const discover = require('./runtime-matrix.cjs');

async function resolve(releases) {
  let output;
  await discover({
    core: {info() {}, setOutput(name, value) {
      assert.equal(name, 'versions');
      output = JSON.parse(value);
    }},
    fetch: async () => ({ok: true, json: async () => releases})
  });
  return output;
}

test('includes intermediate and future stable releases, excludes prereleases and duplicates', async () => {
  const releases = [
    {branch: '9.1', latest: '9.1.0', state: 'stable'},
    {branch: '8.1', latest: '8.1.30', state: 'eol'},
    {branch: '8.2', latest: '8.2.40', state: 'eol'},
    {branch: '8.3', latest: '8.3.30', state: 'security'},
    {branch: '8.4', latest: '8.4.20', state: 'stable'},
    {branch: '8.5', latest: '8.5.10', state: 'stable'},
    {branch: '9.0', latest: '9.0.1', state: 'stable'},
    {branch: '9.2', latest: '9.2.0RC1', state: 'prerelease'}
  ];
  assert.deepEqual(await resolve(releases), ['8.2', '8.3', '8.4', '8.5', '9.0', '9.1']);
});

test('fails rather than quietly running an empty or incomplete matrix', async () => {
  await assert.rejects(resolve([]));
  await assert.rejects(resolve([{branch: '9.0', latest: '9.0.0', state: 'stable'}]));
  await assert.rejects(resolve(null));
});

test('metadata HTTP, JSON and network failures fail discovery without emitting a matrix', async () => {
  const core = {info() {}, setOutput() {assert.fail('Must not emit a fallback matrix');}};
  await assert.rejects(discover({core, fetch: async () => ({ok: false, status: 503})}), /503/);
  await assert.rejects(discover({core, fetch: async () => ({
    ok: true, json: async () => {throw new SyntaxError('Invalid JSON');}
  })}), /Invalid JSON/);
  await assert.rejects(discover({core, fetch: async () => {throw new Error('Network failure');}}), /Network failure/);
});
