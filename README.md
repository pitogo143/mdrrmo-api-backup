# MDRRMO Incident REST API

A PHP REST API for the MDRRMO Constituents Website. It receives emergency incident reports, validates the submitted data, and stores incident details and map coordinates in PostgreSQL with PostGIS.

## Repository structure

~~~text
mdrrmo-api/
├── public/
│   └── api/
│       ├── health.php
│       └── incidents/
│           ├── create.php
│           ├── list.php
│           └── view.php
├── src/
│   ├── database.php
│   ├── db_connection.php
│   ├── http.php
│   └── schema.sql
├── .dockerignore
├── .env.example
├── .gitignore
├── Dockerfile
├── README.md
└── test-db.php
~~~

## Requirements

- PHP 8.3 with PDO PostgreSQL (pdo_pgsql) and mbstring, or Docker
- PostgreSQL with the PostGIS extension
- Git and a GitHub account for deployment

## 1. Configure the database

Create a PostgreSQL database. Copy .env.example to .env and replace its example values with your database settings. The .env file contains credentials; keep it private and do not commit it. When running the Docker image locally, Docker reads the file with --env-file.

The app accepts either DATABASE_URL or the separate DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD, and optional DB_SSLMODE variables. Render should use the database's Internal Database URL for DATABASE_URL.

Create the PostGIS extension and incidents table by applying src/schema.sql to your database. For example, from PowerShell with DATABASE_URL set:

~~~powershell
psql $env:DATABASE_URL -f .srcschema.sql
~~~

You can also run the SQL in src/schema.sql using your PostgreSQL client. Render Postgres supports PostGIS; see the [Render Postgres extension guide](https://render.com/docs/postgresql-extensions).

The schema creates an incidents table with reporter/contact details, incident type, description, optional barangay and address, a geographic point, a status, and created/updated timestamps. Allowed statuses are pending, verified, responding, resolved, and cancelled.

## 2. Run locally with Docker

From the repository root, after filling in .env and applying the schema:

~~~powershell
docker build -t mdrrmo-api .
docker run --rm --env-file .env -p 10000:10000 mdrrmo-api
~~~

The API will be available at http://localhost:10000. The Dockerfile serves the public directory and listens on port 10000.

## 3. API endpoints

All endpoints return JSON.

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | /api/health.php | Check the API, PostgreSQL connection, and PostGIS |
| POST | /api/incidents/create.php | Validate and save a new emergency report |
| GET | /api/incidents/list.php | List reports; supports limit, offset, and status query parameters |
| GET | /api/incidents/view.php?id=1 | Fetch one report by its ID |

Example: check service health and list the most recent reports.

~~~text
http://localhost:10000/api/health.php
http://localhost:10000/api/incidents/list.php?limit=20&offset=0
~~~

Create a report by sending JSON. reporter_name, contact_number, incident_type, description, latitude, and longitude are required. Coordinates must be valid latitude/longitude values. barangay and address are optional.

~~~powershell
$body = @{
  reporter_name = "Juan Dela Cruz"
  contact_number = "09171234567"
  incident_type = "Flooding"
  description = "Flood water is rising near the bridge."
  barangay = "Example Barangay"
  address = "Near the public bridge"
  latitude = 10.3157
  longitude = 123.8854
} | ConvertTo-Json
Invoke-RestMethod -Uri "http://localhost:10000/api/incidents/create.php" -Method Post -ContentType "application/json" -Body $body
~~~

The create endpoint returns HTTP 201 when a report is saved. Invalid input receives a JSON error response. Use the returned incident id with the view endpoint.

## 4. Deploy on Render

1. Push this repository to GitHub and create a PostgreSQL database on Render.
2. Apply src/schema.sql to that database so PostGIS and the incidents table are ready.
3. In Render, create a Web Service connected to pitogo143/mdrrmo-api, select the main branch, and choose the Docker runtime. Render will build the repository's Dockerfile.
4. In the web service's Environment settings, add DATABASE_URL and set it to the database's Internal Database URL. Keep this value in Render's environment settings; never place it in GitHub files.
5. Deploy the service. You can set /api/health.php as the health check path after the database schema has been applied.
6. Check https://<your-service-name>.onrender.com/api/health.php, then use the deployed base URL for the website's API requests.

## Database connection check

test-db.php is a command-line helper that checks the configured PostgreSQL connection and PostGIS. Run it in an environment where the database variables are available. It is not a public web endpoint.

## Notes

This repository contains the backend API. The website frontend remains a separate application and should send its emergency report form to the deployed POST /api/incidents/create.php endpoint with a JSON request body.
