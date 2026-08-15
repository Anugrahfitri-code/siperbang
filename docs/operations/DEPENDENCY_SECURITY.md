# Accepted Temporary Residual Risks

This document tracks known dependency vulnerabilities that are present in the dependency tree but have been proven safe or non-reachable in the production environment. These exceptions must be reassessed during any major package upgrade or architectural shift.

## 1. brace-expansion (High)

- **Package**: `brace-expansion`
- **Version**: `1.1.16`
- **Severity**: High
- **Dependency Path**: `exceljs@4.4.0` -> `archiver@5.3.2` -> `archiver-utils@2.1.0` -> `glob@7.2.3` -> `minimatch@3.1.5` -> `brace-expansion@1.1.16`
- **Why remediation is not currently available/safe**: A direct transitive upgrade breaks the `glob`/`minimatch` internal APIs and is not natively resolvable without introducing breaking changes or forced overrides that destabilize `archiver` and `exceljs`. ExcelJS relies on this exact version combination.
- **Runtime reachability**: NOT REACHABLE. The application only executes ExcelJS within the browser (client-side) using Vite's bundling.
- **Build reachability**: NOT REACHABLE. Vite strips `fs` and Node-specific modules (like `archiver`) during the production build. The code for `brace-expansion` is never executed during Vite bundling or deployed to production.
- **Compensating controls**: Vite dead-code elimination ensures this code path is physically absent from the production browser bundle.
- **Condition that requires reassessment**: Upgrade of ExcelJS to version 5.x or any switch to server-side XLSX generation.
- **Target upgrade/removal strategy**: Await official `exceljs` patch or replace the library if server-side execution becomes necessary.

## 2. uuid (Moderate)

- **Package**: `uuid`
- **Version**: `8.3.2`
- **Severity**: Moderate
- **Dependency Path**: `exceljs@4.4.0` -> `uuid@8.3.2`
- **Why remediation is not currently available/safe**: Fixed version (uuid@10+) involves API and module system changes (ESM vs CJS) that break the `exceljs` internal usage.
- **Runtime reachability**: NOT REACHABLE (Vulnerability context). The application does not directly import `uuid`. ExcelJS uses it internally for zip generation. The browser environment implementation of `uuid` relies on the browser's native `crypto.getRandomValues()`, which does not suffer from the weak RNG vulnerability present in older Node implementations. Furthermore, there is no attacker-controlled buffer/offset path in our implementation.
- **Build reachability**: NOT REACHABLE (Vulnerability context).
- **Compensating controls**: Browser-native cryptographic functions are used.
- **Condition that requires reassessment**: If UUID is ever utilized directly within the Node.js backend.
- **Target upgrade/removal strategy**: Upgrade when `exceljs` updates its dependency tree.
