# Car Info Scraper and Scanner

This is a small project that scrapes car listings from bilweb.se, tries to detect license plates in the images, and shows the results in a simple web interface.

## Overview

- **Main page**: `http://localhost/carinfotest/public/index.html`
- **Scripts**:
  - `scraper.php` — collects car ads and saves them in the database
  - `scanner.php` — checks the images for license plates

You can run the scripts manually or set them up to run automatically with cron.

## Setup

### 1. Unzip the Project

Place the folder (`carinfotest`) into your web server root directory, like `htdocs` if you're using XAMPP or WAMP.

### 2. Database

- Create a MySQL database (e.g. named `test`)
- Import the SQL file from: `carinfotest/create_sql_table`
- Edit the database connection settings in `src/db.php` if needed

### 3. Requirements

Make sure PHP is installed with these extensions:
- `cURL`
- `DOMDocument`

## How to Use

### Main Web Page

Open this in your browser:

http://localhost/carinfotest/public/index.html

### Run Scraper

- Via terminal:  
  `php src/scraper.php`

- Or in your browser:  
  `http://localhost/carinfotest/src/scraper.php`

### Run Scanner

- Via terminal:  
  `php src/scanner.php`

- Or in your browser:  
  `http://localhost/carinfotest/src/scanner.php`

### Automating with Cron Jobs

Example cron setup (adjust paths as needed):
0 * * * * /usr/bin/php /path/to/htdocs/carinfotest/src/scraper.php
0 */2 * * * /usr/bin/php /path/to/htdocs/carinfotest/src/scanner.php

