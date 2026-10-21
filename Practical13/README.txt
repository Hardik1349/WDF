CampusConnect / StudentHub - Practicals 5, 6, 7 and 8
====================================================

Practical 5: Registration form with client-side JavaScript/Regex validation.
Practical 6: Event list loaded from data/events.json using Fetch API, with search, category filtering, sorting and pagination.
Practical 7: PHP server-side form validation and CSV storage for registration/contact forms.
Practical 8: StudentHub MySQL database design and PHP MySQL connection.

PRACTICAL 8 FILES
-----------------
database/studenthub.sql  Creates the studenthub database and students, events, registrations tables.
config/db.php            Connects PHP to MySQL using mysqli.
database_test.php        Tests the connection and displays counts and sample event rows.

DATABASE DESIGN
---------------
students: student details and password hash.
events: event title, description, date, venue and category.
registrations: links a student to an event, with registration date and status.
Foreign keys connect registrations.student_id to students.student_id and registrations.event_id to events.event_id. A unique constraint prevents a student registering for the same event twice.

HOW TO RUN ON XAMPP
-------------------
The user previously set the project location to D:\xampp\htdocs\Practical7. You can keep using that folder by copying the contents of this ZIP's CampusConnect_Practical8 folder into D:\xampp\htdocs\Practical7. Keep a backup first and merge the new files/folders; do not delete your existing practical files.

1. Open XAMPP Control Panel.
2. Start Apache and MySQL.
3. Open http://localhost:8080/phpmyadmin/ (use the port that works for your XAMPP setup).
4. In phpMyAdmin, choose the Import tab, select database/studenthub.sql, and click Import/Go.
   Alternatively, open the SQL file, copy its statements into phpMyAdmin's SQL tab, and run them.
5. Ensure config/db.php uses your MySQL username and password. The default XAMPP setup often uses root and an empty password; change these values if your setup differs.
6. Open http://localhost:8080/Practical7/database_test.php
7. A successful page displays the connection status, record counts and sample events.

IMPORTANT
---------
- Start MySQL as well as Apache for Practical 8.
- Apache is using port 8080 in the user's setup; if your actual port differs, adjust the URL.
- The registration form still uses Practical 7 CSV storage. Practical 8 demonstrates database design and connection; it does not silently change the previous form to store data in MySQL.
- Do not put real passwords in a classroom demo. The students table stores a password_hash field, not a plain-text password.


PRACTICAL 9 - SECURE USER REGISTRATION
--------------------------------------
New files:
- secure_registration.html: frontend form with HTML validation and JavaScript checks.
- register_user.php: server-side validation, duplicate username/email check using a prepared MySQLi statement, and password_hash().
- database/practical9_users.sql: creates the users table in the existing studenthub database.

Setup:
1. Keep Apache and MySQL running in XAMPP.
2. Open http://localhost:8080/phpmyadmin/
3. Select the studenthub database, choose Import, and import:
   D:\xampp\htdocs\Practical9\database\practical9_users.sql
   (adjust the folder path if your project is stored elsewhere).
4. Open http://localhost:8080/Practical9/secure_registration.html
5. Submit a username, email, and strong password.
6. Check the users table in phpMyAdmin. The password_hash column contains a hash, not the original password.

The previous Practical 5–8 files are retained. The new Practical 9 registration is on secure_registration.html so it does not break the existing student registration and CSV practical.


============================================================
PRACTICAL 10: SECURE LOGIN, LOGOUT AND PHP SESSIONS
============================================================

NEW FILES
- login.php: login form with optional remember-me checkbox
- login_process.php: checks password using password_verify(), regenerates the session ID, then redirects by role
- dashboard.php: protected student dashboard
- admin_dashboard.php: protected admin-only dashboard
- logout.php: clears session and remember-me token
- auth_helpers.php: session timeout and secure session/remember-token helpers
- database/practical10_auth.sql: adds role column and remember_tokens table

SETUP ON YOUR XAMPP (Apache port 8080)
1. Keep your Practical 5-9 work safe. Extract this ZIP to D:\xampp\htdocs\Practical10.
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost:8080/phpmyadmin/ and select the existing studenthub database.
4. Click Import and import: D:\xampp\htdocs\Practical10\database\practical10_auth.sql
   IMPORTANT: Run this SQL only once because it adds the role column. If you already imported it, do not import it again.
5. Open http://localhost:8080/Practical10/login.php
6. Log in using a username/email and password created in Practical 9.
7. Successful student logins go to dashboard.php.
8. To test admin redirection, open the studenthub database in phpMyAdmin, choose SQL, and run (replace the username):
   UPDATE users SET role = 'admin' WHERE username = 'your_username';
   Log out and log in again. That account should open admin_dashboard.php.
9. To test role restrictions, try opening admin_dashboard.php while logged in as a student; it redirects to dashboard.php.
10. Test logout, invalid password, direct dashboard access while logged out, and session timeout (15 minutes of inactivity).
11. Optional remember-me: tick the checkbox during login. The browser stores a random token in an HttpOnly cookie; only a SHA-256 hash of its validator is stored in MySQL. Logout revokes the token.

SECURITY FEATURES
- Prepared statement for user lookup
- password_verify() for checking stored password hashes
- session_regenerate_id(true) after login
- HttpOnly and SameSite=Lax session/remember cookies; Secure flag on HTTPS
- Protected student and admin dashboards, with server-side role checks
- 15-minute inactivity timeout
- Random remember-me selector/validator, hashed validator in database, token rotation, and logout revocation

NOTE
- The project uses your existing config/db.php settings (database studenthub, XAMPP root account with blank password by default).
- Existing Practical 5-9 files are included and have not been intentionally removed.
- For production websites, always use HTTPS and consider CSRF protection, rate limiting, and a stricter deployment configuration.

============================================================
PRACTICAL 11: STUDENT MANAGEMENT MODULE (CRUD)
============================================================

NEW FILES
- students.php: admin-only page to add, view, edit, delete, search and filter student records.
- database/practical11_student_management.sql: verifies/creates the students table used in Practical 8.

HOW TO RUN (Apache port 8080)
1. Keep your Practical 5-10 folders safe. Extract this ZIP to D:\xampp\htdocs\Practical11.
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost:8080/phpmyadmin/ and select the studenthub database.
4. The students table should already exist from Practical 8. If it does not, import database/practical11_student_management.sql.
5. Make sure Practical 10's role column exists and that your account has role admin. If needed, run this once in the SQL tab (replace the username):
   UPDATE users SET role = 'admin' WHERE username = 'your_username';
6. Log in at http://localhost:8080/Practical11/login.php
7. Open http://localhost:8080/Practical11/students.php, or click Manage Students on the Admin Dashboard.
8. Add a student using a unique email and initial password (at least 8 characters). Passwords are stored as hashes.
9. Try editing a record, deleting a test record, searching by name/email/mobile, and filtering by course/year.

FEATURES
- Create, read, update and delete student records (CRUD).
- Search by student name, email or mobile number.
- Filter by course and study year; combine filters with search.
- Prepared MySQLi statements for insert, select, update and delete.
- Backend validation, duplicate email check, password_hash() for a new student's initial password.
- Clear success/failure messages and escaped output.
- Admin-only access, PHP session timeout, and CSRF token validation for write actions.

NOTES
- This module uses the existing studenthub.students table from Practical 8; no duplicate table is needed.
- It is protected for administrators because it can change student records.
- If your existing studenthub database was not created with Practical 8, import database/studenthub.sql first, then set up Practical 9 and Practical 10 as in their README sections.
- Previous Practical 5-10 files are included in this project copy and are not intentionally removed.

============================================================
PRACTICAL 12: ADMIN EVENT MANAGEMENT + POSTER UPLOADS
============================================================

NEW FILES / CHANGES
- events_admin.php: admin-only create, view, update and delete for event records.
- database/practical12_event_management.sql: adds poster_path to the existing events table.
- uploads/event_posters/: destination for uploaded posters, with executable PHP files blocked by .htaccess.
- admin_dashboard.php: now includes a Manage Events link.

SETUP (Apache port 8080)
1. Extract the complete ZIP into D:\xampp\htdocs\Practical12 (or your existing Practical 11 folder if you are continuing there). Practical 11 files remain included.
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost:8080/phpmyadmin/ and select the studenthub database.
4. Import/run database/practical12_event_management.sql ONCE. It adds poster_path to the events table created in Practical 8. Do not run this migration a second time because the column already exists.
5. Confirm your login user has role admin. If needed, run this once in the SQL tab, replacing the username:
   UPDATE users SET role = 'admin' WHERE username = 'your_username';
6. Open http://localhost:8080/Practical12/login.php and log in with that admin account.
7. Open http://localhost:8080/Practical12/admin_dashboard.php and click Manage Events, or directly visit http://localhost:8080/Practical12/events_admin.php.
8. Add an event with title, date/time, venue and category. A poster is optional. Accepted poster types are JPG/JPEG, PNG and WEBP, maximum 2 MB.
9. Test viewing the list, editing an event, replacing a poster, deleting a test event, trying a non-image file, and trying a file larger than 2 MB.

FEATURES AND SAFETY
- Admin-only access uses the existing Practical 10 session and role check.
- Create, read, update and delete events with prepared MySQLi statements.
- CSRF token validation for add/update/delete operations.
- Server-side checks for required event fields, lengths, and valid date/time.
- Poster file size and actual MIME type validation, plus image-content validation.
- Random generated filenames; original client filenames are not trusted.
- When an existing poster is replaced or an event is deleted, its old image is removed.
- If no new poster is selected during an edit, the current poster is kept.
- Uploaded files are stored under uploads/event_posters/ and executable PHP extensions are denied there.

TROUBLESHOOTING
- If the page reports that the Practical 12 SQL setup is needed, run the SQL migration once in the studenthub database.
- If poster upload fails, check that uploads/event_posters is writable by Apache and that PHP's upload_max_filesize and post_max_size are at least 2M (preferably 4M for post_max_size).
- This is a classroom project. For a production deployment, also configure web-server upload restrictions and regular backups.


============================================================
PRACTICAL 13: PHP JSON API + FETCH API (ASYNC EVENT CRUD)
============================================================

NEW FILES
- api/events.php: JSON API endpoint for GET (list), POST (add), PUT (update), and DELETE (delete).
- events_api.php: admin page that consumes the API without a full page reload.
- js/events_api.js: uses Fetch API and JSON for asynchronous requests and rendering.
- admin_dashboard.php: includes an Events JSON API link.

SETUP (Apache port 8080)
1. Extract this combined ZIP to D:\xampp\htdocs\Practical13. Keep your previous practical folders as backups.
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost:8080/phpmyadmin/ and select the studenthub database.
4. Make sure the events table exists. If you imported database/studenthub.sql earlier, it should already exist.
5. Practical 12 added poster_path. If you are using this combined project and have already run database/practical12_event_management.sql, do not run it again. The API does not need poster_path.
6. Make sure your login account has role admin. If needed, run this once in phpMyAdmin SQL (replace the username):
   UPDATE users SET role = 'admin' WHERE username = 'your_username';
7. Open http://localhost:8080/Practical13/login.php and log in as the admin.
8. From Admin Dashboard, choose Events JSON API, or open http://localhost:8080/Practical13/events_api.php.
9. Add an event. Try Edit and Update, Delete, and Refresh List. The list updates without a full page reload.

HOW IT WORKS
- GET api/events.php returns event records as JSON.
- POST api/events.php creates an event from JSON data.
- PUT api/events.php updates an event using event_id.
- DELETE api/events.php deletes an event using event_id.
- JavaScript Fetch API sends requests asynchronously and updates the table.
- The API checks admin login, validates data, uses prepared SQL statements, and checks a CSRF token for write operations.
- The UI escapes event text before placing it into the table.

TROUBLESHOOTING
- If you get an access message, log in with an admin account; students cannot use the management API.
- If the database reports that events does not exist, import database/studenthub.sql into studenthub.
- If the page says Practical 12 setup is needed elsewhere, check that poster_path was added for the separate Practical 12 admin page. The Practical 13 API itself does not use posters.
- Check the browser Console and Network tabs if Fetch requests fail.

NOTE
This classroom example is intended for local XAMPP use. Keep PHP errors out of public API responses and use HTTPS and additional protections for production.
