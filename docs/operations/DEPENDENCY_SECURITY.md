# Accepted Temporary Residual Risks

This document tracks known dependency vulnerabilities that are present in the dependency tree but have been proven safe or non-reachable in the production environment. These exceptions must be reassessed during any major package upgrade or architectural shift.

## 1. uuid (Moderate)

- **Package**: `uuid`
- **Version**: `8.3.2`
- **Severity**: Moderate
- **Dependency Path**: `exceljs@4.4.0` -> `uuid@8.3.2`
- **Why remediation is not currently available/safe**: Fixed versions (uuid@9+) involve breaking API and module system changes (ESM vs CJS) that directly break `exceljs` internal resolution and packaging.
- **Runtime reachability**: SOURCE-PROVEN-NOT-REACHABLE (Vulnerability context). 
- **Reachability Justification**: The advisory affects `v3`, `v5`, and `v6` APIs when an external, attacker-controlled output buffer and offset are supplied. A direct source inspection of `exceljs` usage within the SIPERBANG bundle (`cf-rule-ext-xform.js` and other usages) proves that ExcelJS exclusively calls the `uuidv4()` API with zero arguments. No output buffers or offsets are ever supplied by the application or by ExcelJS internals. The vulnerable conditions cannot physically occur.
- **Build reachability**: NOT REACHABLE.
- **Compensating controls**: API usage constraint (v4 parameterless).
- **Condition that requires reassessment**: If UUID is ever utilized directly within the backend or if ExcelJS modifies its internal call signature to use vulnerable buffer bounds.
- **Target upgrade/removal strategy**: Upgrade when `exceljs` updates its dependency tree to a patched UUID version natively.
