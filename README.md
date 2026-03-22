# Personal Student Tracker

## How to deploy?

### Option 1: Using Docker (Recommended)
This is the fastest way to get the app running with all dependencies pre-configured.

1.  **Install dependencies:** Run the following command to download PHP libraries (Resend SDK, Dotenv, etc.):
    ```bash
    docker run --rm -v $(pwd)/src:/app composer install
    ```

2.  **Environment Setup:** - Copy `.env.example` to `.env` inside the `src/` folder.
    - Fill in your `RESEND_API_KEY`.

3.  **Spin up the containers:**
    ```bash
    docker compose up -d
    ```

4.  **Access the App:**
    - Web App: [http://localhost:8080/](http://localhost:8080/)
    - phpMyAdmin: [http://localhost:8081/](http://localhost:8081/)

---

### Option 2: Using XAMPP (Manual)
1.  **Install Composer:** Ensure you have [Composer](https://getcomposer.org/) installed on your machine.
2.  **Install PHP Libraries:** Open terminal in the `src/` directory and run:
    ```bash
    composer install
    ```
3.  **Database Config:** - Import the SQL file (if provided) into your MySQL via phpMyAdmin.
    - Change the MySQL password to `root` (or your current password).
    - Update connection variables in `src/includes/connect-db.php` and your `.env` file.
4.  **Run:** Move the `src/` content to your `htdocs` folder and access via `http://localhost/`.