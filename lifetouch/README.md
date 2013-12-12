dbchecker - A PHP based script to check the status of a connection from a Web server to a database

This script 

- Connects to a database to check the connectivity
- Connect and determine the number of unique users in the USERS table
- Record appropriate values of unique USERS at that moment in the STATUS table and disconnect
- Log significant events to a local file.
- If a database failure occurs, notify the sysadmins by email
- A simple web interface which can be displayed to an end-user to show the latest status OR latest error.
