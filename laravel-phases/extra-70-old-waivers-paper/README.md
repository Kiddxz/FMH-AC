# Extra 70: old waivers use the paper design too

- `Waiver::paper()`: a waiver made before Extra 69 (no `details`) had "Owner: ...", "Pet: ...", "Date prepared: ..." on top of its text. These are read and shown in the blanks of the paper form (Owner's Name, Tel. No., Pet's Name, Species/Breed, Age/Sex, Date); address and color come from the records. The signed text in the database is not changed.
- `partials/waiver-paper.blade.php`: always shows the paper design.
- New test `test_a_waiver_made_before_the_paper_form_shows_the_same_design` (262 tests pass).
- `fmh-waiver-update.zip`: ALL files of Extra 69 + 70 in one zip (extract into the project folder).
