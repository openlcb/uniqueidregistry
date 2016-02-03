-- person_created
ALTER TABLE Person ADD COLUMN person_created TIMESTAMP NULL;
UPDATE Person SET person_created = last_updated WHERE person_created IS NULL;
ALTER TABLE Person CHANGE person_created person_created TIMESTAMP NOT NULL;

-- person_password_hash
ALTER TABLE Person ADD COLUMN person_password_hash VARCHAR(255) NULL;

-- person_is_moderator
ALTER TABLE Person ADD COLUMN person_is_moderator VARCHAR(1) NOT NULL DEFAULT 'n';
UPDATE Person SET person_is_moderator = 'y' WHERE person_id IN (3, 4);

-- uniqueid_created
ALTER TABLE UniqueIDs ADD COLUMN uniqueid_created TIMESTAMP NULL;
UPDATE UniqueIDs SET uniqueid_created = last_updated WHERE uniqueid_created IS NULL;
ALTER TABLE UniqueIDs CHANGE uniqueid_created uniqueid_created TIMESTAMP NOT NULL;

-- uniqueid_approved
ALTER TABLE UniqueIDs ADD COLUMN uniqueid_approved TIMESTAMP NULL;
UPDATE UniqueIDs SET uniqueid_approved = uniqueid_created;

-- uniqueid_approved_by
ALTER TABLE UniqueIDs ADD COLUMN uniqueid_approved_by INT NULL;
ALTER TABLE UniqueIDs ADD FOREIGN KEY (uniqueid_approved_by) REFERENCES Person (person_id);

-- person_email_shared_secret
ALTER TABLE Person ADD COLUMN person_email_shared_secret VARCHAR(255) NULL;

-- person_email_verified
ALTER TABLE Person ADD COLUMN person_email_verified VARCHAR(1) NOT NULL DEFAULT 'n';
