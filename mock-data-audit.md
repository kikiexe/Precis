# Mock / Dummy / Static Data Audit: frontend-app & frontend-pos

> [!WARNING]
> Total **8 locations** still using hardcoded mock data that MUST be replaced with backend API calls.

---

## frontend-app (3 issues)

### 1. Inventory Raw Materials & Stock Logs (localStorage mock)
**Files:**
- [inventory-service.ts:L199-252](file:///home/kikiexe/Projects/Precis/frontend-app/src/lib/services/inventory-service.ts#L199-L252) -> `defaultMaterials` array (Arabica Beans, Fresh Milk, Oatmilk, Syrup Vanilla, Paper Cup) persisted to `localStorage`
- [inventory-service.ts:L290-318](file:///home/kikiexe/Projects/Precis/frontend-app/src/lib/services/inventory-service.ts#L290-L318) -> `defaultLogs` adjustment logs (EXPIRED, RESTOCK) persisted to `localStorage`

**Consumers:**
- [KatalogSection.svelte:L37,L104-116](file:///home/kikiexe/Projects/Precis/frontend-app/src/lib/components/app/KatalogSection.svelte#L37-L116) -> calls mock `getRawMaterials()` / `createRawMaterial()` / `deleteRawMaterial()`
- [StockAdjustmentModal.svelte:L29,L67](file:///home/kikiexe/Projects/Precis/frontend-app/src/lib/components/app/StockAdjustmentModal.svelte#L29-L67) -> calls mock `getAdjustmentLogs()` / `adjustStock()`

**Fix:** Replace with `GET/POST/DELETE /api/raw-materials`, `GET/POST /api/inventory/adjustments`

### 2. Hardcoded Waste Cost Multiplier
- [FinanceSection.svelte:L240-247](file:///home/kikiexe/Projects/Precis/frontend-app/src/lib/components/app/FinanceSection.svelte#L240-L247) -> `* 20000` hardcoded instead of actual material unit cost

**Fix:** Fetch unit cost from backend per material

### 3. Billing Proof Upload Sample URL
- [BillingHistoryTab.svelte:L29,L36](file:///home/kikiexe/Projects/Precis/frontend-app/src/lib/components/app/settings/BillingHistoryTab.svelte#L29-L36) -> `proofUrl = 'https://r2.precis.id/proofs/bukti_transfer_sample.webp'` hardcoded

**Fix:** Add file picker + presign upload like `BillingModal.svelte` already does

---

## frontend-pos (5 issues)

### 4. Inventory Raw Materials (12 dummy items)
- [InventoriView.svelte:L62-214](file:///home/kikiexe/Projects/Precis/frontend-pos/src/lib/components/pos/pages/InventoriView.svelte#L62-L214) -> `defaultMaterials` 12 items + hardcoded categories `cat-bar`, `cat-kitchen`

**Fix:** Fetch via `GET /pos/inventory/raw-materials` & `/pos/inventory/categories`

### 5. Product Stock & Audit Logs (mock arrays)
- [ProdukView.svelte:L30-163](file:///home/kikiexe/Projects/Precis/frontend-pos/src/lib/components/pos/pages/ProdukView.svelte#L30-L163) -> 6 mock `rawMaterials`, 3 mock `stockLogs`, 5 mock `rawMaterialCategories`

**Fix:** Connect to backend stock & audit log endpoints via `posService`

### 6. Profile Bank & Sync Simulation
- [ProfilView.svelte:L45-65](file:///home/kikiexe/Projects/Precis/frontend-pos/src/lib/components/pos/pages/ProfilView.svelte#L45-L65) -> hardcoded `BCA 8901238910 - PT PRECIS KREATIF`, QRIS merchant name, and `setTimeout` fake sync

**Fix:** Load from `terminalInfo` / branch settings; call real `syncEngine.syncPendingOrders()`

### 7. Hardcoded Fallback IDs
- [PaymentModal.svelte:L37-39](file:///home/kikiexe/Projects/Precis/frontend-pos/src/lib/components/pos/PaymentModal.svelte#L37-L39) -> `'branch-sleman-01'`, `'ws-amore-01'`, `'sess-active-01'`
- [App.svelte:L627-629](file:///home/kikiexe/Projects/Precis/frontend-pos/src/App.svelte#L627-L629) -> same fallback IDs

**Fix:** Require valid IDs from pairing `terminalInfo` & active session; block if uninitialized

### 8. Printer Header Hardcoded
- [printer-service.ts:L10-15](file:///home/kikiexe/Projects/Precis/frontend-pos/src/lib/services/printer-service.ts#L10-L15) -> outlet name `PRECIS COFFEE & EATERY`, address `Jl. Kaliurang KM 5.2...`, phone

**Fix:** Pass dynamic store header from branch/terminal metadata

---

## Confirmed OK (not mock, legitimate static)

| Location | What | Why OK |
|----------|------|--------|
| `StaffPresensiSection.svelte:L320-342` | Canvas test simulator | Camera hardware fallback |
| `ShiftSection.svelte` / `ShiftRosterTab.svelte` | Year/month selectors | UI calendar constants |
| `defaults.ts` (both apps) | Business defaults | Shared-utils constants |
| `workspace-context.svelte.ts:L28-36` | Store initial state | Pre-auth placeholder |
| `OutletPurchaseModal.svelte:L39-47` | Category/unit options | UI enum |
| `StockWasteModal.svelte:L25-32` | Waste reasons | UI enum |
| `PaymentModal.svelte:L73-79` | Cash shortcuts | Keypad values |
| `PosSidebar.svelte:L30-38` | Nav items | Static navigation |
| `OrderCart.svelte:L69-73` | Order type options | Enum (Dine In/Take Away/Delivery) |
| `PosStockLogTab.svelte:L14-21` | Reason labels | Badge color mappings |
| `InventoriView.svelte:L244-255` | Standard units | Unit selector options |
| `*.test.ts` files | Test mock data | Test-only, expected |
