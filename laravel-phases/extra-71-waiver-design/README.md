# Extra 71: nicer waiver design with the clinic's Terms and Conditions

`resources/views/partials/waiver-paper.blade.php` only:
- Header with the clinic logo, the 3 branches and the form title in a pill.
- Sections: Owner & Pet Information (cream box), Authorization (the first paragraph, procedure highlighted),
  Terms and Conditions (the clinic's paragraphs, word for word, numbered 1 and 2), Signature.
- "By signing below, I confirm that I have read, understood and agreed to all of the above."
- Status pill (Waiting / Signed / Reviewed), green "Signed ..." line, footer with the reference. Prints with the colors.
262 tests pass.
