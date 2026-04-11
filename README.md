# Personal Student Tracker

## How to deploy?

### Using Docker (Recommended)
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
4.  **IMPORTANT**
    - Import semesters and criterions data before accesss the app, its is required for training point page.
5.  **Access the App:**
    - Web App: [http://localhost:8080/](http://localhost:8080/)
    - phpMyAdmin: [http://localhost:8081/](http://localhost:8081/)

---
