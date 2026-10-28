# Extra 60: remove a pet added by mistake

- Pet profile: "Remove Pet" (with a confirm box) only while the pet has no clinic records; otherwise a note to contact the clinic.
- DELETE /portal/pets/{pet} (soft delete, logged).
- Adding the same pet (same name and species) twice is refused.
