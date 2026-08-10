# Database Design and ER Blueprint

## Conventions

Tables and columns use snake_case with unsigned bigint keys. Company-owned records carry `company_id`; searchable codes are unique within company. Financial amounts use `DECIMAL(18,2)`, quantities/unit costs `DECIMAL(18,4)`, dates use explicit date/datetime columns, and user-facing documents use locked `number_sequences`. Foreign keys restrict deletion of referenced masters and cascade only for true owned children. Posted transactions are immutable and retained.

## Foundation implemented

```text
companies 1--* company_settings
companies 1--* users *--* roles *--* permissions
companies 1--* financial_years 1--* accounting_periods
companies 1--* accounts *--1 account_types
accounts 1--* accounts (parent hierarchy)
companies 1--* customer_types 1--* customers
companies 1--* suppliers
companies 1--* product_categories (self hierarchy)
companies 1--* brands
companies 1--* units
product_categories 1--* products *--1 brands
units 1--* products 1--* product_prices
products 1--* product_serial_numbers
```

`products.current_quantity` and `average_cost` are performance caches; future stock reconciliation treats `stock_movements` and cost history as authoritative. Opening master balances are migration inputs only; Phase 6 converts approved openings into a balanced journal.

## Complete target ER domains

### Sales and receivables

`sales` belongs to company, customer and user and has many `sale_items`, `sale_payments`, allocations, returns and document links. Each item belongs to a product, stores quantity, unit price, discount, tax, historical unit cost, gross profit and margin. Serial assignments link sale items to serial inventory. Receipts have many allocations, enabling one receipt to cover several invoices and partial settlements. Quotations, orders and delivery notes retain source/target links.

### Purchases and payables

`purchase_orders` → `goods_received_notes` → `purchases` preserves ordered, received and invoiced quantities. Purchase items link products and actual costs. Payments allocate across supplier invoices. Returns/debit notes reference original lines. GRN posting creates stock; supplier invoice posting creates AP, avoiding duplicate inventory movements.

### Inventory

Every inventory event writes immutable `stock_movements` with product, location, reference type/id, quantity in/out, unit cost and value. Adjustments, counts and transfers have headers/items and approval/posting states. `inventory_cost_history` records each weighted-average transition. Serial rows link acquisition, current location, sale, customer, returns and warranty claims.

### Accounting

`journal_entries` belongs to company, period, source document and posting user; `journal_lines` belongs to an account and optionally a customer/supplier. A posting service locks the number sequence and validates total debit equals total credit before commit. General ledger, statements and reconciliation are derived from posted lines. Bank/cash accounts map one-to-one to ledger accounts; bank transactions and reconciliation items link statement lines to ERP entries.

### Controls

Imports use batch → row → errors/history. Approvals reference module/record and preserve decisions. Audit logs store actor, action, record, old/new JSON, IP and time. Backups log filename, checksum, size, creator and outcome. Normal users have no destructive access to audit or posted transaction history.

## Accounting event matrix

| Event | Debit | Credit |
|---|---|---|
| Cash sale | Cash/Bank; Cost of Goods Sold | Sales Revenue; Inventory |
| Credit sale | Accounts Receivable; Cost of Goods Sold | Sales Revenue; Inventory |
| Customer receipt | Cash/Bank | Accounts Receivable (or Customer Advances) |
| Cash purchase | Inventory | Cash/Bank |
| Credit purchase | Inventory | Accounts Payable |
| Supplier payment | Accounts Payable | Cash/Bank |
| Expense | Expense account | Cash/Bank or payable |
| Sales return | Sales Returns; Inventory | Cash/AR/Credit Note; COGS |
| Purchase return | Cash/AP/Debit Note | Inventory |
| Stock adjustment | Inventory or adjustment expense | Adjustment income or Inventory |
| Opening balances | Relevant asset/expense | Relevant liability/equity/income; balancing equity |
| Manual journal | User-selected account(s) | User-selected account(s), balance mandatory |

Tax lines are added through configured tax control accounts; reversals invert original lines rather than recalculating them.

## Stock event matrix

| Event | Movement |
|---|---|
| Opening stock, posted GRN, sales return | Quantity in |
| Sale, purchase return, damaged write-off | Quantity out |
| Transfer | Out from source and in to destination in one transaction |
| Confirmed count/adjustment | Difference in or out with reason and approval |

Reservations from confirmed sales orders affect availability but not on-hand. Draft documents never affect stock or ledgers.

## Integrity and performance

Unique/indexed keys cover company-scoped master codes, barcode, serial number, document/status/date fields and foreign keys. Services use database transactions and row locks for posting, numbering, stock and allocations. Reports paginate and eager-load relationships. Reconciliation jobs verify stock cache vs movements, AR/AP subsidiary totals vs controls, inventory valuation vs GL, and journal debit vs credit totals.
