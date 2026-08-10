# Supun Group ERP Development Plan

## Delivery rules

- Build one verified phase at a time; database-backed features only.
- Use migrations, foreign keys, transactions, policies, Form Requests and focused services.
- Posted financial/stock documents are reversed or voided, never destructively edited.
- Money uses `DECIMAL(18,2)`; quantities and costs use `DECIMAL(18,4)`.
- Every release must pass automated tests, migration rollback/rebuild, authorization checks and reconciliation checks.

## Architecture

Laravel provides the HTTP, authentication, authorization and domain-service layers. Blade, Bootstrap 5, Fetch API and Chart.js form the desktop-first UI. MariaDB is the system of record. Controllers orchestrate requests; posting, inventory, allocations, numbering and period validation live in reusable services. Reports query journal lines and stock movements rather than duplicated totals.

## Phases and acceptance gates

1. **Foundation (complete):** Laravel/XAMPP skeleton, schema design, company/security, periods, chart of accounts, parties and product masters. Gate: fresh migration and seed succeed; FK inspection passes.
2. **Master data/import:** CRUD for products, classifications, customers and suppliers; staged Excel/CSV validation, preview and error export. Gate: policies and import tests.
3. **Purchasing/inventory:** PO, GRN, purchases, weighted-average costing, serials, movement ledger. Gate: stock/cost reconciliation.
4. **Sales/POS:** retail/wholesale and cash/credit sales, invoices, holds and printing. Gate: atomic sale and unavailable-stock tests.
5. **Receivables (workflow complete; accounting reconciliation pending Phase 6):** receipts, allocation, advances, customer ledger and aging. Gate: receipt allocation tests pass; AR control account reconciliation follows accounting posting.
6. **Accounting engine (sales and receivables complete):** balanced journal service, period validation, sales/receipt posting, journals, trial balance and GL. Purchase/AP posting continues with Phase 7. Gate: unbalanced and closed-period posting rejected.
7. **Payables/cash/bank/expenses (complete):** GRN-linked supplier invoices, supplier payments/advances, AP ledger and aging, cash/bank expense vouchers and accounting posting. Gate: AP and journal tests pass.
8. **Returns/notes (in progress):** sale and purchase returns plus credit/debit notes are operational and tested. Exchanges and formal document reversals remain. Gate: stock/accounting reversal tests.
9. **Statements:** trial balance, P&L, balance sheet, cash flow and reconciliation. Gate: debit=credit and balance sheet equation.
10. **Management intelligence:** margins, profitability, stock valuation, CFO dashboard and report center. Gate: drill-down totals agree with sources.
11. **Document workflows:** quotations, SO, delivery, PO/partial GRN and statuses. Gate: conversion traceability.
12. **Controls/release:** approvals, audit, period locking, notifications, backup/restore, hardening and full regression. Gate: restore drill and security review.

## Cross-cutting test strategy

Feature tests cover permissions and workflows. Unit tests cover numbering, weighted average cost, payment allocation and journal balancing. Database tests cover constraints and idempotent seeders. Reconciliation tests compare AR/AP/inventory subsidiaries with control accounts. A release checklist records backup, migration, rollback, smoke test and restore results.

## Runtime decision

The detected XAMPP runtime is PHP 8.0.30/MariaDB 10.4.32, so this foundation uses Laravel 9.52. Laravel 9 and PHP 8.0 are end-of-life and Composer reports advisories. This is acceptable only for local foundation development; upgrade XAMPP to a supported PHP release and upgrade Laravel before production/go-live.
