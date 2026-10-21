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
