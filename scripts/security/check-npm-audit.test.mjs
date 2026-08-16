import test from 'node:test';
import assert from 'node:assert';
import { validatePolicy, evaluateAudit } from './check-npm-audit.mjs';

test('1. no findings: PASS', () => {
    const auditData = { vulnerabilities: {} };
    const { hasBlockingFindings, errors } = evaluateAudit(auditData, []);
    assert.strictEqual(hasBlockingFindings, false);
    assert.strictEqual(errors.length, 0);
});

test('2. exact active accepted High: PASS', () => {
    const auditData = {
        vulnerabilities: {
            "pkg1": {
                severity: "high",
                via: [{ name: "pkg1", severity: "high", url: "https://github.com/advisories/GHSA-123" }]
            }
        }
    };
    const exceptions = [{ advisoryId: 'GHSA-123', package: 'pkg1', reason: 'x', reachability: 'y' }];
    const { hasBlockingFindings, errors } = evaluateAudit(auditData, exceptions);
    assert.strictEqual(hasBlockingFindings, false);
    assert.strictEqual(errors.length, 0);
});

test('3. exact active accepted Moderate: PASS', () => {
    const auditData = {
        vulnerabilities: {
            "pkg2": {
                severity: "moderate",
                via: [{ name: "pkg2", severity: "moderate", url: "https://github.com/advisories/GHSA-abc" }]
            }
        }
    };
    const exceptions = [{ advisoryId: 'GHSA-abc', package: 'pkg2', reason: 'x', reachability: 'y' }];
    const { hasBlockingFindings, errors } = evaluateAudit(auditData, exceptions);
    assert.strictEqual(hasBlockingFindings, false);
    assert.strictEqual(errors.length, 0);
});

test('4. unknown High: FAIL', () => {
    const auditData = {
        vulnerabilities: {
            "pkg3": {
                severity: "high",
                via: [{ name: "pkg3", severity: "high", url: "https://github.com/advisories/GHSA-unknown" }]
            }
        }
    };
    const { hasBlockingFindings, errors } = evaluateAudit(auditData, []);
    assert.strictEqual(hasBlockingFindings, true);
    assert.strictEqual(errors.length, 1);
});

test('5. unknown Moderate: FAIL', () => {
    const auditData = {
        vulnerabilities: {
            "pkg4": {
                severity: "moderate",
                via: [{ name: "pkg4", severity: "moderate", url: "https://github.com/advisories/GHSA-unknown-mod" }]
            }
        }
    };
    const { hasBlockingFindings, errors } = evaluateAudit(auditData, []);
    assert.strictEqual(hasBlockingFindings, true);
    assert.strictEqual(errors.length, 1);
});

test('6. any Critical even if policy entry exists: FAIL', () => {
    const auditData = {
        vulnerabilities: {
            "pkg5": {
                severity: "critical",
                via: [{ name: "pkg5", severity: "critical", url: "https://github.com/advisories/GHSA-crit" }]
            }
        }
    };
    const exceptions = [{ advisoryId: 'GHSA-crit', package: 'pkg5', reason: 'x', reachability: 'y' }];
    const { hasBlockingFindings, errors } = evaluateAudit(auditData, exceptions);
    assert.strictEqual(hasBlockingFindings, true);
    assert.ok(errors[0].includes("Critical severity found"));
});

test('7. expired exception: FAIL', () => {
    const policy = {
        version: 1,
        exceptions: [{
            ecosystem: 'npm',
            advisoryId: 'GHSA-exp',
            package: 'pkg',
            reason: 'r',
            reachability: 'r',
            reviewedOn: '2023-01-01',
            expiresOn: '2023-02-01'
        }]
    };
    assert.throws(() => {
        validatePolicy(policy, new Date('2023-03-01'));
    }, /expired/);
});

test('8. mismatched package for known GHSA: FAIL', () => {
    const auditData = {
        vulnerabilities: {
            "pkg-other": {
                severity: "high",
                via: [{ name: "pkg-other", severity: "high", url: "https://github.com/advisories/GHSA-123" }]
            }
        }
    };
    const exceptions = [{ advisoryId: 'GHSA-123', package: 'pkg-correct', reason: 'x', reachability: 'y' }];
    const { hasBlockingFindings, errors } = evaluateAudit(auditData, exceptions);
    assert.strictEqual(hasBlockingFindings, true);
});

test('9. malformed policy: FAIL', () => {
    assert.throws(() => validatePolicy({ version: 2 }), /Unsupported policy/);
    assert.throws(() => validatePolicy({ version: 1, exceptions: [{ ecosystem: 'npm' }] }), /missing required fields/);
});

test('10. unidentified blocking advisory: FAIL', () => {
    const auditData = {
        vulnerabilities: {
            "pkg": {
                severity: "high",
                via: [{ name: "pkg", severity: "high", title: "no url or ghsa in title" }]
            }
        }
    };
    const { hasBlockingFindings, errors } = evaluateAudit(auditData, []);
    assert.strictEqual(hasBlockingFindings, true);
    assert.ok(errors[0].includes('Unidentified blocking advisory'));
});

test('11. low advisory: REPORT / NON-BLOCKING', () => {
    const auditData = {
        vulnerabilities: {
            "pkg": {
                severity: "low",
                via: [{ name: "pkg", severity: "low", url: "https://github.com/advisories/GHSA-low" }]
            }
        }
    };
    const { hasBlockingFindings, errors } = evaluateAudit(auditData, []);
    assert.strictEqual(hasBlockingFindings, false);
    assert.strictEqual(errors.length, 0);
});

test('12. duplicate policy exception: FAIL', () => {
    const policy = {
        version: 1,
        exceptions: [
            { ecosystem: 'npm', advisoryId: 'GHSA-1', package: 'p', reason: 'r', reachability: 'r', reviewedOn: '2026-01-01', expiresOn: '2026-12-31' },
            { ecosystem: 'npm', advisoryId: 'GHSA-1', package: 'p', reason: 'r2', reachability: 'r', reviewedOn: '2026-01-01', expiresOn: '2026-12-31' }
        ]
    };
    assert.throws(() => validatePolicy(policy, new Date('2026-06-01')), /Duplicate exception/);
});

test('13. stale non-expired exception: warning behavior', () => {
    const auditData = { vulnerabilities: {} };
    const exceptions = [{ advisoryId: 'GHSA-stale', package: 'pkg', reason: 'x', reachability: 'y' }];
    // Evaluate should pass but log a warning (which we don't intercept here, but check it doesn't fail)
    const { hasBlockingFindings, errors } = evaluateAudit(auditData, exceptions);
    assert.strictEqual(hasBlockingFindings, false);
});
