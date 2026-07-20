# Test Plan

Just listing out what we're planning to test once the Loan and Payment features are actually pushed. 

## Loan Feature

- [ ] Can add a loan with valid Amount, Loan Type, and Status
- [ ] Form doesn't let you submit with empty/missing fields
- [ ] Loan Type dropdown only shows Tuition / Books / Living Expenses
- [ ] Status dropdown only shows Pending / Approved / Disbursed
- [ ] Amount field rejects letters/invalid numbers (e.g. negative amounts?)
- [ ] Clicking "Loans" on a student takes you to the right student's loan page (not someone else's)
- [ ] Clicking "View" only shows loans belonging to that specific student
- [ ] New loan actually shows up in the list after adding it (no refresh needed, hopefully)

## Payment Feature

- [ ] Can add a payment with valid Amount, Date, and Method
- [ ] Form doesn't let you submit with empty/missing fields
- [ ] Payment Method dropdown only shows Cash / Bank Transfer / Online Payment
- [ ] Date field only accepts valid dates
- [ ] Clicking "Payments" on a loan takes you to the right loan's payment page
- [ ] Clicking "View" only shows payments for that specific loan
- [ ] Total Paid updates correctly after adding a new payment
- [ ] Remaining Balance = loan amount - total paid, and it's actually correct
- [ ] What happens if total payments go over the loan amount? (need to check if this is handled)

## General / UI stuff
- [ ] No crashes when clicking around normally
- [ ] Buttons/forms respond without weird delays
- [ ] Error messages show up when something's wrong (not just a silent fail)
- [ ] UI looks okay on different screen sizes (if we have time to check this)

## Notes
Haven't been able to test any of this yet since features aren't pushed. Will update this file as testing happens and move stuff to "known issues" in the README/user guide if we find bugs.