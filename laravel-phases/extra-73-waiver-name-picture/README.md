# Extra 73: the clinic name from the paper form, no Service/Description on the form

- `public/image/fmh-name.png`: "FMH ANIMAL CLINIC" from the clinic's paper form (photo cleaned: black letters, transparent background).
- `partials/waiver-paper.blade.php`: the picture is shown next to the logo (instead of the Permanent Marker font, which is not needed any more);
  the "Service:" and "Description:" lines were removed from the form.
- The Service list on Prepare Waiver stays: it only fills the blank ("to perform ____").
262 tests pass.
