# lab_exam



## Getting Started
 
1. **Clone the repository** and move into the project folder:
```bash
   git clone <your-repo-url>
   cd lab_exam
```
 
2. **Start the containers:**
```bash
   docker compose up -d --build
```
 
   This will:
   - Build the PHP/Apache image (`web` service) with the `pdo` and
     `pdo_mysql` extensions installed.
   - Start a MySQL 8.0 container (`db` service) and automatically create the
     `school_db` database and its tables from `schema.sql` on first run.
3. **Wait for the database to be healthy.** Compose will hold the `web`
   service until `db`'s healthcheck passes (usually a few seconds). Check
   status with:
```bash
   docker compose ps
```
 
4. **Open the app** in your browser:
```
   http://localhost:8080
```
