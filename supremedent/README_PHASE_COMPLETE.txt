SUPREMEDENT - ADVISER FEEDBACK REVISION BUILD
Updated: September 11, 2026

INSTALL / RUN
1. Copy the "supremedent" folder to:
   C:\xampp\htdocs\supremedent

2. Start Apache and MySQL in XAMPP.

3. Make sure the database is named:
   supremedent_db

4. For a fresh setup, import supremedent_db.sql.
   For an existing database, you can import supremedent_upgrade.sql.
   The PHP connection also creates missing tables/settings automatically.

5. Open:
   http://localhost/supremedent/

ADVISER-FEEDBACK IMPROVEMENTS INCLUDED
- Patient must complete phone, address, birthday, and gender before booking.
- Patient dashboard shows a persistent profile-completion warning until completed.
- Patient booking changed from plain date/time fields to a color-coded calendar and time slots.
- Fully booked appointment slots are disabled.
- Booking cutoff / lead time is enforced (default: 3 hours before preferred schedule).
- Booking hours, slot duration, and lead time are configurable by Super Admin.
- Upcoming Appointments and Appointment History are separated.
- Assistant can approve/reject online appointment requests and assign a dentist.
- Assistant can record a registered walk-in patient and assign a dentist.
- Patient has a read-only Dental Chart page with the existing 3D GLB model.
- Dentist remains responsible for editing/confirming clinical dental chart findings.
- Landing page Contact Us now accepts public inquiries without requiring login.
- Clinic address, phone, email, and hours appear on the public site/footer.
- Super Admin has an Inquiries page to review Contact Us messages.
- Landing page cards were improved with more useful feature details.
- Laboratory module is disabled by default based on the latest scope discussion.
  Existing laboratory code/data was NOT deleted. Super Admin can re-enable it under Website Content.
- PHP timezone is explicitly set to Asia/Manila for booking logic.
- Existing dentist print/download chart tools and 3D files are preserved.

DEFAULT BOOKING SETTINGS
- Start: 09:00
- End: 17:00
- Slot duration: 60 minutes
- Booking lead time: 3 hours
These are editable in Super Admin > Website Content.

IMPORTANT ITEMS STILL NEEDING EXTERNAL CONFIGURATION
- Gmail/email OTP verification is configured through config/mail.php + config/mail.local.php.
- Forgot-password remains a local testing flow unless a real mail service is connected.
- Fill in the clinic address, phone number, and email under Super Admin > Website Content.

SCOPE NOTE
The Laboratory module was questioned during the adviser review. This build hides/disables it by default rather than permanently deleting it, so the project can safely restore it if the final approved scope still includes Laboratory.
