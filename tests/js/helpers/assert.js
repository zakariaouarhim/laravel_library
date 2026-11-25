/** Minimal check/report helpers, so the suites need no test framework. */
function suite(name) {
    const results = { name: name, passed: 0, failed: 0 };

    function check(ok, label, detail) {
        const mark = ok ? 'PASS' : 'FAIL';
        const extra = detail === undefined || detail === null || detail === '' ? '' : String(detail);
        console.log('  [' + mark + '] ' + String(label).padEnd(54) + ' ' + extra);
        if (ok) { results.passed++; } else { results.failed++; }
        return ok;
    }

    return { check: check, results: results };
}

/** Resolves once pending microtasks and timers have drained. */
function settle(ms) {
    return new Promise((r) => setTimeout(r, ms === undefined ? 30 : ms));
}

module.exports = { suite, settle };
