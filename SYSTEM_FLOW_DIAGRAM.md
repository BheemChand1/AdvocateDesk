================================================================================
SYSTEM FLOW DIAGRAM
================================================================================

# CASE CREATION PHASE:

┌──────────────────┐
│ CREATE CASE │
└────────┬─────────┘
│
├─ Set case details
├─ Select case type
│
▼
┌─────────────────────────────┐
│ Add Fee Items │
│ (case_fee_grid table) │
│ │
│ • Fee Item 1 - ₹X,XXX │
│ • Fee Item 2 - ₹X,XXX │
│ • Fee Item 3 - ₹X,XXX │
└─────────────────────────────┘

# CASE MANAGEMENT PHASE:

┌─────────────────────────────────────────┐
│ CAUSE LIST - UPDATE MODAL │
│ ┌───────────────────────────────────┐ │
│ │ Update Date: [________] │ │
│ │ Fee Item: [Fee Item 1 ▼] │ │ ← Load from case_fee_grid
│ │ Fee Amount: ₹5,000 (read-only) │ │ for this case_id only
│ │ Payment Status: [pending ▼] │ │
│ │ • Pending │ │
│ │ • Processing │ │
│ │ • Completed │ │
│ │ Remarks: [_____________] │ │
│ │ │ │
│ │ [Cancel] [Update] [End Case] │ │
│ └───────────────────────────────────┘ │
└────────────────┬────────────────────────┘
│
▼
┌───────────────────────────────────────┐
│ INSERT INTO case_position_updates │
│ ┌─────────────────────────────────┐ │
│ │ case_id: 123 │ │
│ │ update_date: 2025-01-15 │ │
│ │ position: Fee Item 1 │ │
│ │ fee_amount: 5000.00 ✓ (NEW) │ │
│ │ payment_status: pending ✓ (NEW)│ │
│ │ remarks: ... │ │
│ │ created_by: user_id │ │
│ └─────────────────────────────────┘ │
└───────────────────────────────────────┘
│
├─ Update case_stage_id = fee_id
├─ If "End Case", set status = 'closed'
└─ Commit transaction

# ACCOUNTS MANAGEMENT PHASE:

┌──────────────────────────────────────────────────────────────┐
│ CASE ACCOUNTS DASHBOARD │
├──────────────────────────────────────────────────────────────┤
│ │
│ PENDING CASES PROCESSING FEES │
│ ────────────── ───────────── │
│ [GO TO PENDING] [GO TO PROCESSING] │
│ │
│ ┌─────────────────────────┐ ┌─────────────────────────┐ │
│ │ Query: │ │ Query: │ │
│ │ SELECT ... FROM │ │ SELECT ... FROM │ │
│ │ case_position_updates │ │ case_position_updates │ │
│ │ WHERE │ │ WHERE │ │
│ │ payment_status='pending'│ │ payment_status= │ │
│ │ │ │ 'processing' │ │
│ │ Shows: │ │ │ │
│ │ Case ID │ Fee │ Amount │ │ Shows: │ │
│ │ ────────┼─────┼──────── │ │ Case ID │ Fee │ Amount │ │
│ │ CASE001 │Fee1 │ ₹5,000 │ │ ────────┼─────┼──────── │ │
│ │ CASE002 │Fee2 │ ₹7,500 │ │ CASE001 │Fee1 │ ₹5,000 │ │
│ └─────────────────────────┘ └─────────────────────────┘ │
│ │
│ COMPLETED FEES │
│ ─────────────── │
│ [GO TO COMPLETED] │
│ │
│ ┌─────────────────────────┐ │
│ │ Query: │ │
│ │ SELECT ... FROM │ │
│ │ case_position_updates │ │
│ │ WHERE │ │
│ │ payment_status= │ │
│ │ 'completed' │ │
│ │ │ │
│ │ Shows: │ │
│ │ Case ID │ Fee │ Amount │ │
│ │ ────────┼─────┼──────── │ │
│ │ CASE003 │Fee3 │ ₹3,200 │ │
│ └─────────────────────────┘ │
└──────────────────────────────────────────────────────────────┘

# PAYMENT WORKFLOW:

STEP 1: USER MARKS AS PENDING
┌────────────────────────────────────────┐
│ Cause List → Update │
│ Payment Status: pending ✓ │
│ │
│ case_position_updates: │
│ payment_status = 'pending' │
└────────────────────────────────────────┘
│
▼
┌────────────────────────────────────────┐
│ Pending Cases Page │
│ Shows this fee │
└────────────────────────────────────────┘

STEP 2: USER UPDATES TO PROCESSING
┌────────────────────────────────────────┐
│ Cause List → Update (same fee) │
│ Payment Status: processing ✓ │
│ │
│ case_position_updates: │
│ New record: payment_status='processing'│
└────────────────────────────────────────┘
│
▼
┌────────────────────────────────────────┐
│ Processing Fees Page │
│ Shows this fee │
└────────────────────────────────────────┘

STEP 3: USER MARKS AS COMPLETED
┌────────────────────────────────────────┐
│ Cause List → Update (same fee) │
│ Payment Status: completed ✓ │
│ │
│ case_position_updates: │
│ New record: payment_status='completed' │
└────────────────────────────────────────┘
│
▼
┌────────────────────────────────────────┐
│ Completed Fees Page │
│ Shows this fee │
└────────────────────────────────────────┘

# DATABASE SCHEMA:

case_fee_grid (Master Fee List)
┌─────────┬──────────────┬────────────┐
│ id (PK) │ case_id (FK) │ fee_name │
├─────────┼──────────────┼────────────┤
│ 1 │ 123 │ Fee Item 1 │
│ 2 │ 123 │ Fee Item 2 │
│ 3 │ 124 │ Fee Item 1 │
└─────────┴──────────────┴────────────┘
│ fee_amount
├─ 5000.00
├─ 7500.00
└─ 3200.00

case_position_updates (Fee Update History)
┌─────────┬──────────────┬──────────────┬──────────────┬─────────────────────────┐
│ id (PK) │ case_id (FK) │ position │ fee_amount │ payment_status (NEW) │
├─────────┼──────────────┼──────────────┼──────────────┼─────────────────────────┤
│ 1 │ 123 │ Fee Item 1 │ 5000.00 │ pending │
│ 2 │ 123 │ Fee Item 1 │ 5000.00 │ processing │
│ 3 │ 123 │ Fee Item 1 │ 5000.00 │ completed │
│ 4 │ 123 │ Fee Item 2 │ 7500.00 │ pending │
│ 5 │ 124 │ Fee Item 1 │ 3200.00 │ processing │
└─────────┴──────────────┴──────────────┴──────────────┴─────────────────────────┘

cases
┌────────┬──────────────────┐
│ id (PK)│ case_stage_id │ ← Points to current fee in case_fee_grid
├────────┼──────────────────┤
│ 123 │ 1 │ ← Currently on Fee Item 1
│ 124 │ 3 │ ← Currently on Fee Item 1
└────────┴──────────────────┘

# KEY IMPROVEMENTS:

OLD SYSTEM:
❌ Showed all fees at once in accounts
❌ No payment status tracking per fee
❌ Used generic case_stages table
❌ No audit trail of fee progression

NEW SYSTEM:
✓ Shows only fees with their specific payment status
✓ Payment status tracked per fee update
✓ Uses actual fees from case_fee_grid
✓ Complete audit trail in case_position_updates
✓ Fee progression visible through status changes
✓ Case stage matches current fee being processed

================================================================================
