// Discover every stable release line from the existing CI minimum.
const metadataUrl = 'https://www.php.net/releases/branches.php?json';
const compare = (a, b) => a.localeCompare(b, 'en', {numeric: true});

module.exports = async function discover({core, fetch = globalThis.fetch}) {
  const response = await fetch(metadataUrl, {
    headers: {'User-Agent': 'sailthru-ci-runtime-matrix'},
    signal: AbortSignal.timeout(30000)
  });
  if (!response.ok) throw new Error(`Runtime metadata request failed: ${response.status}`);
  const releases = await response.json();
  // Retain released branches after EOL; exclude development/RC branches.
  const versions = releases
    .filter(release => ['stable', 'security', 'eol'].includes(release.state)
      && /^\d+\.\d+\.\d+$/.test(release.latest)
      && /^\d+\.\d+$/.test(release.branch))
    .map(release => release.branch)
    .filter(version => compare(version, '8.2') >= 0);

  const matrix = [...new Set(versions)].sort(compare);
  if (!matrix.includes('8.2') || matrix.length > 256) {
    throw new Error('Runtime matrix is incomplete or exceeds the GitHub Actions limit');
  }
  core.info(`Testing php release lines: ${matrix.join(', ')}`);
  core.setOutput('versions', JSON.stringify(matrix));
};
