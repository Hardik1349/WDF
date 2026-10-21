CampusConnect - Practical 5 and Practical 6
===========================================

Practical 5: Student registration form
- registration.html: registration form
- js/registration.js: JavaScript validation using regular expressions

Practical 6: Dynamic event list using Fetch API
- events.html: event page with search, category filter, sorting and pagination
- data/events.json: sample external JSON event records
- js/events.js: Fetch API, dynamic rendering, search, filtering, sorting and pagination logic
- CSS/style.css: website and event-page styles

How to run Practical 6
1. Extract all files and keep the folder structure unchanged.
2. Open the project folder in Visual Studio Code.
3. Run index.html using the Live Server extension (right-click index.html > Open with Live Server).
4. Click Events in the navigation, or open events.html through Live Server.

Important: Fetch API usually cannot load local JSON files when an HTML page is opened directly with file://. Use Live Server or another local web server. Keep data/events.json in the data folder.

How Practical 6 works
1. fetch('data/events.json') requests the external JSON file.
2. The JSON data is converted into JavaScript objects using response.json().
3. JavaScript dynamically creates event cards.
4. Search checks event title, category, venue, description and organizer.
5. Category filter shows selected categories.
6. Sorting supports date and title in ascending/descending order.
7. Pagination displays six events per page.
8. Reset filters clears search and restores the default sorting.

The event data is sample demonstration data and can be edited in data/events.json. This is a front-end practical; it does not connect to a database.

Note: The original home page references images/student.png; that image was not included in the supplied files, so the home page image may not appear unless you add it.
