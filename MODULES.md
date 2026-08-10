# Module Catalogue

| Area | Modules |
|---|---|
| Executive | Operational dashboard; CFO dashboard; notifications; drill-down KPIs |
| Sales | POS; retail/wholesale; cash/credit; invoices; quotations; sales orders; delivery notes; receipts; returns; credit notes; discounts; cashier closing; invoice printing |
| Purchasing | Requisitions (optional); purchase orders; partial GRN; supplier invoices/purchases; supplier payments; returns; debit notes |
| Inventory | Products; categories/subcategories; brands; units; prices/history; barcodes; opening stock; movement ledger; weighted-average costing; serials; warranty; transfers; adjustments; damaged stock; stock count; reorder/low stock; reconciliation |
| Customers | Customer master/types/contacts/addresses; credit settings; opening balances; partial payments; allocations/advances; statements; receivables; aging |
| Suppliers | Supplier master/contacts/addresses; opening balances; statements; payables; aging |
| Accounting | Hierarchical chart of accounts; journal vouchers; reusable posting services; general ledger; periods; trial balance; P&L; balance sheet; cash flow; cash/bank; cheques; reconciliation; expenses/vouchers; taxes; opening balances |
| Profit & reporting | Sales/purchase/product/party reports; historical gross profit; retail/wholesale/product/invoice/customer/category/brand margins; inventory valuation; slow/fast/dead stock; report center; PDF/Excel/CSV/print |
| Administration | Company/system settings; users; roles; permissions; approvals; audit/login logs; import/export; backup/restore; numbering; migration reports |

## Shared statuses and invariants

- Workflow records use explicit draft, approved/confirmed, posted, reversed/voided and cancelled states as applicable.
- Invoice payment states are unpaid, partially paid, paid, overdue or cancelled.
- Posted entries are immutable. Reversal references the original document.
- Transaction posting is atomic and blocked in closed periods.
- Serial numbers are unique per product and cannot be sold twice.
- Product on-hand is derived from stock movements; cached quantity must reconcile.
- Financial statements derive from posted journal lines only.
