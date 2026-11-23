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
