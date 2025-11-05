# Currency Conversion Test Guide

## 🎯 Overview
This guide helps you verify that all currency conversions are working correctly across the application.

## 💱 Currency Setup

| Currency | Symbol | Conversion Rate | Example |
|----------|--------|-----------------|---------|
| **PKR** (Base) | Rs. | 1.0 | Rs.1,000 = Rs.1,000 |
| **USD** | $ | 282.0 | $1 = Rs.282 |
| **EUR** | € | 310.0 | €1 = Rs.310 |
| **GBP** | £ | 360.0 | £1 = Rs.360 |

---

## 📊 Test Data Summary

### Invoices Created

| Invoice | Client | Employee | Currency | Amount | Tax | Status | Payment Date | Payment Amount |
|---------|--------|----------|----------|--------|-----|--------|--------------|----------------|
| #1 | ABC Corp | John Doe (5%) | USD | $1,000 | $50 | Partial | Oct 15 | $250 |
| #2 | XYZ Industries | Jane Smith (3%) | PKR | Rs.110,000 | Rs.5,500 | Paid | Oct 20 | Rs.110,000 |
| #3 | TechCorp | Mike Johnson (7%) | EUR | €500 | €25 | Paid | Oct 25 | €500 |
| #4 | ABC Corp | John Doe (5%) | GBP | £300 | £15 | Paid | Nov 5 | £300 |
| #5 | XYZ Industries | Jane Smith (3%) | PKR | Rs.75,000 | Rs.3,750 | Pending | - | - |

### Expenses Created

| Description | Currency | Amount | Date | Base Currency Value |
|-------------|----------|--------|------|---------------------|
| Office Rent | PKR | Rs.50,000 | Oct 1 | Rs.50,000 |
| Software Licenses | USD | $100 | Oct 10 | Rs.28,200 |
| Marketing Campaign | EUR | €200 | Oct 15 | Rs.62,000 |
| Internet & Utilities | PKR | Rs.15,000 | Nov 5 | Rs.15,000 |

### Bonuses Created

| Employee | Currency | Amount | Description | Released |
|----------|----------|--------|-------------|----------|
| John Doe | PKR | Rs.10,000 | Performance Bonus - Q3 | No |
| Jane Smith | USD | $50 | Project Completion Bonus | No |

---

## ✅ Expected Calculations

### 1. Invoice Totals Page (`/invoices`)

**Total Amount (in Base Currency):**
```
Invoice #1: $1,000 × 282 = Rs.282,000
Invoice #2: Rs.110,000 × 1 = Rs.110,000
Invoice #3: €500 × 310 = Rs.155,000
Invoice #4: £300 × 360 = Rs.108,000
Invoice #5: Rs.75,000 × 1 = Rs.75,000
───────────────────────────────────
TOTAL: Rs.730,000
```

### 2. October Payments (for Reports)

**Total Payments in October:**
```
Payment #1: $250 × 282 = Rs.70,500
Payment #2: Rs.110,000 × 1 = Rs.110,000
Payment #3: €500 × 310 = Rs.155,000
───────────────────────────────────
TOTAL: Rs.335,500
```

### 3. November Salary Release - Commission Calculations

#### John Doe (5% commission rate)
**October Payment:**
- Invoice: $1,000 USD, Tax: $50
- Payment: $250
- Converted to base: $250 × 282 = Rs.70,500
- Tax proportion: ($50 / $1,000) × Rs.70,500 = Rs.3,525
- Net amount: Rs.70,500 - Rs.3,525 = Rs.66,975
- **Commission: Rs.66,975 × 5% = Rs.3,348.75**

**Bonus:**
- Rs.10,000 (PKR)

**Total Salary:**
```
Base Salary:    Rs.50,000.00
Commission:     Rs.3,348.75
Bonus:          Rs.10,000.00
Deductions:     Rs.0.00
─────────────────────────────
TOTAL:          Rs.63,348.75
```

#### Jane Smith (3% commission rate)
**October Payment:**
- Invoice: Rs.110,000 PKR, Tax: Rs.5,500
- Payment: Rs.110,000
- Net amount: Rs.110,000 - Rs.5,500 = Rs.104,500
- **Commission: Rs.104,500 × 3% = Rs.3,135.00**

**Bonus:**
- $50 × 282 = Rs.14,100

**Total Salary:**
```
Base Salary:    Rs.45,000.00
Commission:     Rs.3,135.00
Bonus:          Rs.14,100.00
Deductions:     Rs.0.00
─────────────────────────────
TOTAL:          Rs.62,235.00
```

#### Mike Johnson (7% commission rate)
**October Payment:**
- Invoice: €500 EUR, Tax: €25
- Payment: €500
- Converted to base: €500 × 310 = Rs.155,000
- Tax in base: €25 × 310 = Rs.7,750
- Net amount: Rs.155,000 - Rs.7,750 = Rs.147,250
- **Commission: Rs.147,250 × 7% = Rs.10,307.50**

**Total Salary:**
```
Base Salary:    Rs.40,000.00
Commission:     Rs.10,307.50
Bonus:          Rs.0.00
Deductions:     Rs.0.00
─────────────────────────────
TOTAL:          Rs.50,307.50
```

### 4. October Expenses Total

```
Office Rent:        Rs.50,000 × 1 = Rs.50,000
Software Licenses:  $100 × 282 = Rs.28,200
Marketing Campaign: €200 × 310 = Rs.62,000
────────────────────────────────────────────
TOTAL:              Rs.140,200
```

### 5. November Expenses Total

```
Internet & Utilities: Rs.15,000 × 1 = Rs.15,000
────────────────────────────────────────────
TOTAL:                Rs.15,000
```

---

## 🧪 Test Scenarios

### Scenario 1: Salary Release Preview
1. Go to `/salary-releases/create`
2. Select **John Doe**
3. Select **November 2025** as salary month
4. Click **Preview**

**Expected Results:**
- Should show: "Payments: **$250.00**" (not Rs.250.00)
- Commission should be: **Rs.3,348.75**
- Bonus should be: **Rs.10,000.00**
- Total should be: **Rs.63,348.75**

### Scenario 2: Invoice Totals
1. Go to `/invoices`
2. Check the **Total Amount** displayed

**Expected Result:**
- Total Amount: **Rs.730,000.00**

### Scenario 3: Reports Page
1. Go to `/reports`
2. Select date range: **October 1 - October 31**
3. Generate report

**Expected Results:**
- Payments Received: **Rs.335,500.00**
- Total Invoices: **Rs.547,000.00** (only invoices with October payments)
- Total Expenses: **Rs.140,200.00**
- Net Income: Rs.335,500 - Rs.140,200 = **Rs.195,300.00**

### Scenario 4: Audit Report PDF
1. Go to `/reports`
2. Select date range: **October 1 - October 31**
3. Click **Generate Audit Report**

**Expected Results:**
- Each invoice shows its original currency symbol
- Totals are in base currency (Rs.)
- Summary clearly states "All amounts in Base Currency"

### Scenario 5: Commission Edge Case
1. Create salary release for **John Doe** in **November**
2. Verify only **October payment** ($250) is counted
3. **November payment** (£300 from Invoice #4) should **NOT** be included

---

## 🔍 Verification Checklist

- [ ] Invoice totals page shows Rs.730,000
- [ ] Salary preview shows original currency ($250, not Rs.250)
- [ ] Commission calculated on previous month only
- [ ] All totals converted to base currency
- [ ] Audit PDF shows currency symbols correctly
- [ ] Expenses converted to base currency
- [ ] Bonuses converted to base currency
- [ ] Current month payments excluded from salary calculation
- [ ] Tax deduction handled correctly in commission
- [ ] No double commission payment

---

## 📝 Login Credentials

**Email:** test@example.com  
**Password:** password

---

## 🐛 Common Issues to Check

1. **Wrong currency symbol in salary preview**
   - ❌ Shows: Rs.250.00
   - ✅ Should show: $250.00

2. **Incorrect total on invoices page**
   - ❌ Shows: Rs.295,300 (direct sum without conversion)
   - ✅ Should show: Rs.730,000 (with conversion)

3. **Commission includes current month**
   - ❌ Includes £300 payment from November
   - ✅ Only includes October payments

4. **Totals not in base currency**
   - ❌ Mixed currency totals
   - ✅ All totals in PKR (base currency)

---

## 📞 Support

If any calculation doesn't match the expected values, review:
1. `Currency::toBase()` method
2. Model `getAmountInBaseCurrency()` methods
3. Controller conversion logic
4. Previous month date range calculation

**All calculations should be 100% accurate!**
