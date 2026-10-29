# Extra 72: minimalist waiver (black and white, like the paper) + Service and Description

- `partials/waiver-paper.blade.php`: no colors; "FMH ANIMAL CLINIC" in Permanent Marker (marker letters like the paper form);
  only the words of the paper form, plus "Service:" and "Description:" of the chosen clinic service.
- `public/fonts/permanent-marker.woff2`: the font (free, Apache License 2.0), kept in the project so it works without internet.
- Prepare Waiver: "Service" list (active services). Its description is shown, and its name goes in the blank ("to perform ____")
  when Procedure is empty. Procedure OR service is needed.
- `Waiver::fillFrom`: details.service_id, details.service, details.service_description.
- Print page: plain black and white button and background.
262 tests pass.
