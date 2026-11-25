/**
 * Runs the JS suites. No test framework on purpose: these exist to guard one
 * file (public/js/csrf.js) and its call sites, and the project has no JS test
 * tooling otherwise.
 *
 *   npm run test:js
 *
 * Exits non-zero if any check fails, so it can gate a deploy.
 */
const fs = require('fs');
const path = require('path');

const files = fs.readdirSync(__dirname)
    .filter((f) => f.endsWith('.test.js'))
    .sort();

(async () => {
    const all = [];

    for (const file of files) {
        const run = require(path.join(__dirname, file));
        console.log('\n' + file);
        try {
            all.push(await run());
        } catch (e) {
            // jQuery rejects its deferreds with a jqXHR, not an Error, so
            // e.message is undefined for those - show something useful.
            const why = e && e.message ? e.message
                : (e && e.status ? 'rejected with status ' + e.status : require('util').inspect(e));
            console.log('  [FAIL] suite threw: ' + why);
            if (e && e.stack) { console.log(e.stack); }
            all.push({ name: file, passed: 0, failed: 1 });
        }
    }

    const passed = all.reduce((n, r) => n + r.passed, 0);
    const failed = all.reduce((n, r) => n + r.failed, 0);

    console.log('\n' + '-'.repeat(64));
    all.forEach((r) => {
        console.log('  ' + String(r.name).padEnd(28) + r.passed + ' passed' + (r.failed ? ', ' + r.failed + ' FAILED' : ''));
    });
    console.log('  ' + 'total'.padEnd(28) + passed + ' passed' + (failed ? ', ' + failed + ' FAILED' : ''));

    process.exit(failed ? 1 : 0);
})();
