import fs from 'node:fs';
import { execSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

export function parseAuditJson(jsonStr) {
    try {
        return JSON.parse(jsonStr);
    } catch (e) {
        throw new Error('Malformed audit JSON output');
    }
}

export function validatePolicy(policy, currentDate = new Date()) {
    if (!policy || typeof policy !== 'object' || policy.version !== 1) {
        throw new Error('Unsupported policy schema version or malformed policy');
    }
    const validatedExceptions = [];
    const seen = new Set();

    for (const exc of (policy.exceptions || [])) {
        if (exc.ecosystem !== 'npm') continue;
        if (!exc.advisoryId || !exc.package || !exc.reason || !exc.reachability || !exc.reviewedOn || !exc.expiresOn) {
            throw new Error('Policy exception missing required fields');
        }
        const key = `${exc.advisoryId}:${exc.package}`;
        if (seen.has(key)) {
            throw new Error(`Duplicate exception in policy: ${key}`);
        }
        seen.add(key);

        const expiresOn = new Date(exc.expiresOn);
        if (isNaN(expiresOn.getTime())) {
            throw new Error(`Invalid date format for expiresOn: ${exc.expiresOn}`);
        }

        // Expiry check
        const now = new Date(currentDate);
        now.setHours(0,0,0,0);
        if (expiresOn < now) {
            throw new Error(`Policy exception expired for ${key} on ${exc.expiresOn}`);
        }

        validatedExceptions.push(exc);
    }

    return validatedExceptions;
}

export function evaluateAudit(auditData, exceptions) {
    let hasBlockingFindings = false;
    let errors = [];
    const findings = [];
    const activeExceptions = new Set();
    const exceptionMap = new Map();

    for (const exc of exceptions) {
        exceptionMap.set(`${exc.advisoryId}:${exc.package}`, exc);
    }

    if (!auditData.vulnerabilities) {
        throw new Error('Audit data missing vulnerabilities field');
    }

    // Parse NPM v2 audit format
    for (const [pkgName, vuln] of Object.entries(auditData.vulnerabilities)) {
        const severity = vuln.severity;
        const vias = Array.isArray(vuln.via) ? vuln.via : [];

        for (const via of vias) {
            if (typeof via === 'string') {
                continue; // transitive string reference
            }
            if (typeof via === 'object') {
                let advisoryId = 'UNKNOWN';
                if (via.url) {
                    const match = via.url.match(/(GHSA-[a-zA-Z0-9\-]+|CVE-[0-9\-]+)/);
                    if (match) advisoryId = match[1];
                } else if (via.title) {
                    const match = via.title.match(/(GHSA-[a-zA-Z0-9\-]+|CVE-[0-9\-]+)/);
                    if (match) advisoryId = match[1];
                }

                const actualPkgName = via.name || via.dependency || pkgName;
                const key = `${advisoryId}:${actualPkgName}`;
                const actualSeverity = via.severity || severity;

                findings.push({ advisoryId, package: actualPkgName, severity: actualSeverity });

                if (advisoryId === 'UNKNOWN' && ['critical', 'high', 'moderate'].includes(actualSeverity)) {
                     errors.push(`Unidentified blocking advisory for package ${actualPkgName}`);
                     hasBlockingFindings = true;
                     continue;
                }

                if (actualSeverity === 'critical') {
                    errors.push(`Critical severity found for ${key}. Critical cannot be bypassed.`);
                    hasBlockingFindings = true;
                    continue;
                }

                if (['high', 'moderate'].includes(actualSeverity)) {
                    const exception = exceptionMap.get(key);
                    if (exception) {
                        activeExceptions.add(key);
                        console.log(`[ACCEPTED RISK] ${key} - Severity: ${actualSeverity}. Reason: ${exception.reason}`);
                    } else {
                        errors.push(`Unapproved ${actualSeverity} severity finding: ${key}`);
                        hasBlockingFindings = true;
                    }
                } else {
                    console.log(`[REPORT] Low severity finding: ${key} - Not blocking.`);
                }
            }
        }
    }

    for (const [key, exc] of exceptionMap.entries()) {
        if (!activeExceptions.has(key)) {
            console.warn(`[STALE EXCEPTION WARNING] Exception for ${key} is no longer present in audit findings.`);
        }
    }

    return { hasBlockingFindings, errors, findings };
}

export function runLiveAuditAndCheck() {
    console.log("Loading policy...");
    let policyJson;
    try {
        policyJson = fs.readFileSync('security/dependency-audit-policy.json', 'utf8');
    } catch (e) {
        console.error("Failed to read policy file:", e.message);
        process.exit(1);
    }

    let policyObj;
    try {
        policyObj = JSON.parse(policyJson);
    } catch (e) {
        console.error("Policy file is not valid JSON:", e.message);
        process.exit(1);
    }

    let exceptions;
    try {
        exceptions = validatePolicy(policyObj);
    } catch (e) {
        console.error("Policy validation failed:", e.message);
        process.exit(1);
    }

    console.log("Running npm audit --omit=dev --json...");
    let auditOut = "";
    try {
        auditOut = execSync('npm audit --omit=dev --json', { encoding: 'utf8', stdio: ['pipe', 'pipe', 'pipe'] });
    } catch (error) {
        auditOut = error.stdout || "";
        if (!auditOut.trim().startsWith('{')) {
            console.error("npm audit failed to execute or network error:");
            console.error(error.stderr || error.message);
            process.exit(1);
        }
    }

    let auditData;
    try {
        auditData = parseAuditJson(auditOut);
    } catch (e) {
        console.error(e.message);
        process.exit(1);
    }

    if (auditData.error) {
        console.error("NPM audit returned an error object:", auditData.error.summary || auditData.error);
        process.exit(1);
    }

    const { hasBlockingFindings, errors } = evaluateAudit(auditData, exceptions);

    if (hasBlockingFindings || errors.length > 0) {
        console.error("\nDEPENDENCY SECURITY AUDIT FAILED:");
        errors.forEach(err => console.error(`- ${err}`));
        process.exit(1);
    } else {
        console.log("\nDEPENDENCY SECURITY AUDIT PASSED.");
        process.exit(0);
    }
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
    runLiveAuditAndCheck();
}
