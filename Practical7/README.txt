CampusConnect - Practicals 5, 6 and 7
====================================

Practical 5: Student registration form
- registration.html: registration form
- js/registration.js: client-side validation using regular expressions

Practical 6: Dynamic event list using Fetch API
- events.html: event page with search, category filter, sorting and pagination
- data/events.json: external JSON event records
- js/events.js: Fetch API and dynamic rendering logic

Practical 7: PHP form processing and CSV storage
- registration.html submits registration data using POST to process_form.php.
- contact.html provides a contact form and submits using POST to process_form.php.
- process_form.php sanitizes and validates fields on the server, then saves valid records to CSV files.
- storage/registrations.csv is created when a registration is successfully submitted.
- storage/contacts.csv is created when a contact message is successfully submitted.
- Passwords are never saved as plain text; the registration CSV stores a password hash.
- PHP returns success or error messages after processing. Invalid requests are rejected.

HOW TO RUN PRACTICAL 7 (IMPORTANT)
Live Server is only for static HTML/CSS/JavaScript and does not execute PHP. Use XAMPP or another PHP-enabled local server.
1. Install and open XAMPP.
2. Start Apache from the XAMPP Control Panel. MySQL is not required for this practical.
3. Copy the entire CampusConnect_Practical7 folder into C:\xampp\htdocs\CampusConnect_Practical7 (or your XAMPP htdocs folder).
4. In your browser open http://localhost/CampusConnect_Practical7/registration.html
5. Submit a valid registration form. You should see a server success message.
6. Open the storage folder to check registrations.csv. The file is created after the first successful submission.
7. Test the contact form at http://localhost/CampusConnect_Practical7/contact.html and check storage/contacts.csv.
8. To test server-side validation, try submitting invalid values. PHP should show an error instead of saving the record.

Practical 6 note: To test events, use http://localhost/CampusConnect_Practical7/events.html. Fetch API needs a server to load data/events.json.

Security / classroom notes
- Do not open PHP files using file:// and do not use Live Server to run PHP.
- CSV files are for this practical demonstration. The storage folder includes an Apache .htaccess rule to deny direct web access.
- The PHP script validates data again on the server; browser validation alone is not trusted.
- Passwords are stored as password hashes, not plain text. Avoid using real passwords or personal data in classroom demos.
- On non-Apache servers, configure the storage directory to be outside the public web root or deny public access appropriately.

Note: The home page references images/student.png; the image was not included in the supplied files, so the home page image may not appear unless you add it.
