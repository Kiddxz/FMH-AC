# Extra 55: cancelling on the patient flow board also cancels the appointment

- `app/Services/PatientFlow.php`: a cancelled visit that came from an appointment cancels that appointment (same reason), so the pet owner sees Cancelled.
- Migration fixes appointments that were already left as Confirmed. Run `php artisan migrate`.
