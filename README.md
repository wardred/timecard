# timecard
A very simple MySQL/HTML/PHP timecard web application for volunteers.

www - The HTML/PHP of the application.
    - Place this somewhere on one's webserver and point a
      virtual host at it.

empty_db
    - The SQL to create an empty timecard schema.
      DO NOT RUN THE SQL ON A DATABASE WITH DATA YOU CARE ABOUT!
      It will drop all tables before creating a new, empty schema.

www/config.php
    - A config file with default configuration parameters.
      CHANGE THESE!

# load db
``
cd empty_db/
./import-data.sh
``