# Extra 69: the clinic's paper CONSENT form as the waiver

- New form **Consent**: the same words as the clinic's paper form, with `[procedure]` where the staff type the procedure. The 4 old forms are turned off (not deleted; old waivers still show them, the Admin can turn them on again).
- Migration `2026_10_30_000100_waiver_consent_form.php`: `waivers.procedure`, `waivers.details` (owner and pet details, locked like the text), `waivers.signature` (drawn signature, PNG).
- Prepare Waiver: Procedure, Color (from the pet record), Weight (kg), Temperature (°C).
- `partials/waiver-paper.blade.php`: the waiver looks like the paper form (clinic name, 3 branches, CONSENT, Owner's Name / Date / Address / Tel. No. / Pet's Name / Species/Breed / Age/Sex / Color / Weight / Temp, the text, Name in Print + Signature). Used on the staff/admin/super admin page, the portal page and the print page.
- `partials/signature-pad.blade.php`: the owner signs with the finger or the mouse (at the clinic or in the portal). The server only accepts a real PNG picture.
- Tests: `WaiverTest` updated, new `test_the_signature_must_be_drawn` (261 tests pass).

Install: extract the zip into the project folder (replace the files), then `php artisan migrate` and `php artisan view:clear`.
