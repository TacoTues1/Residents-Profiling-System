USE barangay_db;

ALTER TABLE residents
  ADD COLUMN IF NOT EXISTS is_archived tinyint(1) NOT NULL DEFAULT 0 AFTER archive_reason;

UPDATE residents
SET is_archived = 1
WHERE status = 'Archived';

UPDATE residents
SET status = 'Transferred',
    archive_reason = COALESCE(NULLIF(archive_reason, ''), 'Transferred')
WHERE status = 'Archived'
  AND archive_reason LIKE 'Transferred%';

UPDATE residents
SET status = 'Deceased',
    archive_reason = COALESCE(NULLIF(archive_reason, ''), 'Deceased')
WHERE status = 'Archived'
  AND archive_reason = 'Deceased';

UPDATE residents
SET status = 'Active',
    archive_reason = COALESCE(NULLIF(archive_reason, ''), 'Archived')
WHERE status = 'Archived';

ALTER TABLE residents
  MODIFY status enum('Active','Transferred','Transferred to Another Location','Deceased','Duplicate Record','Error data entry','Other','Archived') NOT NULL DEFAULT 'Active';

ALTER TABLE households
  DROP COLUMN IF EXISTS status;
