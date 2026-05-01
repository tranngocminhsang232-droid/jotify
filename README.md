# Note Management System

A simple, fast, and modern web application for managing your personal notes.

## Requirements

Before you begin, ensure you have the following installed on your system:
- **PHP**: version 8.3 or higher
- **Composer**: PHP package manager
- **Node.js & npm**: required for compiling frontend assets

## Installation

1. **Clone or download the project**
   Download the project files and open the folder in your terminal:
   ```bash
   cd CKWeb2
   ```

2. **Install PHP dependencies**
   This downloads all required backend packages.
   ```bash
   composer install
   ```

3. **Install frontend dependencies**
   This downloads all required packages for the user interface.
   ```bash
   npm install
   ```

## Environment Setup

1. **Create the environment file**
   Copy the example configuration file to create your own:
   ```bash
   cp .env.example .env
   ```
   *(On Windows Command Prompt, use `copy .env.example .env` instead)*

2. **Generate the application key**
   This sets a unique key for your application's security:
   ```bash
   php artisan key:generate
   ```

*Note: By default, this project uses an SQLite database (`DB_CONNECTION=sqlite`), so you don't need to manually configure any database variables like host or passwords in the `.env` file.*

## Database Setup

Initialize the database and tables needed for the application:
```bash
php artisan migrate
```
*(If prompted to create the database file, select `yes`)*

## Running the Project

You will need to run two commands in **separate terminal windows** to start the project.

1. **Terminal 1: Start the frontend build tool**
   ```bash
   npm run dev
   ```

2. **Terminal 2: Start the backend server**
   ```bash
   php artisan serve
   ```

Once both commands are running, you can open your browser and view the application at:
**http://localhost:8000**

## Common Errors & Quick Fixes

- **"Port already in use"**
  If port 8000 is taken, you can specify a different port for the backend server:
  ```bash
  php artisan serve --port=8001
  ```
  *(Then visit http://localhost:8001)*

- **"No application encryption key has been specified."**
  You forgot to generate the app key. Run: `php artisan key:generate`

- **"SQLSTATE[HY000]: General error: 1 no such table"**
  You haven't migrated the database. Run: `php artisan migrate`

- **Missing styling or UI issues**
  Ensure you have run `npm install` and are running `npm run dev` in a separate terminal.
