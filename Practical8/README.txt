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
