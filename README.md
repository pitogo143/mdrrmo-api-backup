# MDRRMO Incident REST API

A PHP backend for the MDRRMO Constituents Website. The API accepts emergency incident reports, validates the submitted values, and stores reports with geographic coordinates in PostgreSQL/PostGIS.

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

- PHP 8.3 with pdo_pgsql and mbstring, or Docker
- PostgreSQL with the PostGIS extension enabled
- psql for creating the database table
- A GitHub account and a Render account for deployment

## 1. Configure the database

Create a PostgreSQL database. The database server must support PostGIS. Render PostgreSQL supports the extension; after connecting to the database, the schema script enables it and creates the incidents table and indexes.

The API reads either a PostgreSQL connection URL or individual database settings:

- DATABASE_URL — connection URL, recommended on Render
- DB_HOST
- DB_PORT — usually 5432
- DB_NAME
- DB_USER
- DB_PASSWORD

For local development, copy .env.example to .env and replace its sample values with your local database settings. Keep .env private; it is ignored by Git. Never put a real password or connection URL in .env.example or commit it to GitHub.

To create the database schema from PowerShell, set your actual connection URL, then run:

~~~powershell
$env:DATABASE_URL = "postgresql://USER:PASSWORD@HOST:PORT/DATABASE?sslmode=require"
psql $env:DATABASE_URL -f src/schema.sql
~~~

Use the database's real connection details in the command. On Render, use the Internal Database URL when the web service and database are in the same Render region. Add it as the web service environment variable named DATABASE_URL.

## 2. Build and run with Docker

From the repository root, build the image:

~~~powershell
docker build -t mdrrmo-api .
~~~

Run it locally using your private .env file:

~~~powershell
docker run --rm --env-file .env -p 10000:10000 mdrrmo-api
~~~

The API will be available at http://localhost:10000. The container serves the public folder and listens on port 10000.

## 3. API endpoints

All responses use JSON.

### Check the API and database

~~~http
GET /api/health.php
~~~

### Create an incident report

~~~http
POST /api/incidents/create.php
Content-Type: application/json
~~~

Required JSON fields: reporter_name, contact_number, incident_type, description, latitude, and longitude. The barangay and address fields are optional. Latitude must be between -90 and 90; longitude must be between -180 and 180.

PowerShell example:

~~~powershell
$body = @{
  reporter_name = "Juan Dela Cruz"
  contact_number = "09171234567"
  incident_type = "Flood"
  description = "Flooding near the bridge."
  barangay = "Poblacion"
  address = "Near the municipal bridge"
  latitude = 10.1234
  longitude = 124.5678
} | ConvertTo-Json

$request = @{
  Method = "Post"
  Uri = "http://localhost:10000/api/incidents/create.php"
  ContentType = "application/json"
  Body = $body
}
Invoke-RestMethod @request
~~~

A successful create request returns HTTP 201 with the new incident data. Invalid values return an error response.

### List incident reports

~~~http
GET /api/incidents/list.php?limit=20&offset=0
~~~

Optional query parameters are limit, offset, and status.

### View one incident report

~~~http
GET /api/incidents/view.php?id=1
~~~

Replace 1 with the report's database ID. An invalid ID returns HTTP 400; a report that does not exist returns HTTP 404.

## 4. Deploy on Render

1. Push this repository to GitHub.
2. In Render, choose New then Web Service, and connect pitogo143/mdrrmo-api.
3. Select the main branch and choose the Docker runtime. Keep the Dockerfile path at ./Dockerfile.
4. Create or select a PostgreSQL database with PostGIS support.
5. In the web service's Environment settings, add DATABASE_URL and paste the database's Internal Database URL.
6. Deploy the web service. When deployment finishes, open https://YOUR-SERVICE.onrender.com/api/health.php to check the API and database connection.

If deployment reports a database or PostGIS error, confirm the database is ready, the DATABASE_URL value is correct, and src/schema.sql has been run against that database.

## 5. Check database setup from the command line

With DATABASE_URL configured, run:

~~~powershell
php test-db.php
~~~

This script checks the connection, PostGIS, and the incidents table. It is a command-line utility and is not a public API endpoint.

## Security notes

- Keep real database credentials in local .env files or Render environment settings; never commit them.
- The API validates input and uses parameterized database queries.
- Configure the frontend's allowed origin more narrowly before using this API for a production public website.
