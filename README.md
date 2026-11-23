# MDRRMO Incident REST API

PHP REST API for the MDRRMO Constituents Website. It receives incident reports and stores them in PostgreSQL/PostGIS.

## Step 1 — Repository structure

The project follows the Part 6 file structure:

~~~text
mdrrmo-api/
├── public/
│   └── api/
│       └── incidents/
│           ├── create.php
│           ├── list.php
│           └── view.php
├── src/
│   ├── database.php
│   └── db_connection.php
├── .dockerignore
├── .gitignore
├── Dockerfile
├── README.md
└── test-db.php
~~~

The three incident endpoints create, list, and view reports. The Dockerfile is included for the Render deployment in Part 6. This project has no Bootstrap dependency or UI framework.

## Step 2 — Database connection files

- src/database.php reads DATABASE_URL or the DB_HOST, DB_PORT, DB_NAME, DB_USER, and DB_PASSWORD environment variables.
- src/db_connection.php opens the PostgreSQL connection with PDO.

Set those variables in your local environment or in the Render service settings. Keep real database passwords out of GitHub. For Docker, create a private local .env file and run the container with --env-file .env; the .env file is ignored by Git.

Create the database table by connecting to your PostgreSQL database with psql and running:

~~~sql
CREATE EXTENSION IF NOT EXISTS postgis;

CREATE TABLE IF NOT EXISTS incidents (
    id BIGSERIAL PRIMARY KEY,
    reporter_name VARCHAR(150) NOT NULL,
    contact_number VARCHAR(40) NOT NULL,
    incident_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    barangay VARCHAR(120),
    address TEXT,
    location GEOGRAPHY(Point, 4326) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'verified', 'responding', 'resolved', 'cancelled')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS incidents_location_gix
    ON incidents USING GIST (location);
CREATE INDEX IF NOT EXISTS incidents_created_at_idx
    ON incidents (created_at DESC);
~~~

The API endpoints are POST public/api/incidents/create.php and GET public/api/incidents/list.php or public/api/incidents/view.php?id=1. The container serves the public folder on port 10000. On Render, connect this GitHub repository as a Docker web service and set DATABASE_URL in the service's Environment settings.
