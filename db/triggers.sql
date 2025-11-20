-- ============================================
-- TRIGGERS: mantenere note.vote_count
-- - upvote (TRUE) counts +1, downvote (FALSE) counts -1
-- - INSERT/DELETE/UPDATE gestiti per mantenere vote_count consistente
-- ============================================

DROP TRIGGER IF EXISTS vote_after_insert;

CREATE TRIGGER vote_after_insert AFTER INSERT ON vote FOR EACH ROW

	UPDATE note
	SET vote_count = vote_count + (CASE WHEN NEW.vote THEN 1 ELSE -1 END)
	WHERE id = NEW.note_id;


DROP TRIGGER IF EXISTS vote_after_delete;
CREATE TRIGGER vote_after_delete AFTER DELETE ON vote FOR EACH ROW

	UPDATE note
	SET vote_count = vote_count + (CASE WHEN OLD.vote THEN -1 ELSE 1 END)
	WHERE id = OLD.note_id;

DELIMITER $$

CREATE TRIGGER vote_after_update
AFTER UPDATE ON vote
FOR EACH ROW
BEGIN
    IF OLD.note_id <> NEW.note_id THEN
        UPDATE note
        SET vote_count = vote_count + (CASE WHEN OLD.vote THEN -1 ELSE 1 END)
        WHERE id = OLD.note_id;

        UPDATE note
        SET vote_count = vote_count + (CASE WHEN NEW.vote THEN 1 ELSE -1 END)
        WHERE id = NEW.note_id;
    ELSE
        IF OLD.vote <> NEW.vote THEN
            IF NEW.vote THEN
                UPDATE note
                SET vote_count = vote_count + 2
                WHERE id = NEW.note_id;
            ELSE
                UPDATE note
                SET vote_count = vote_count - 2
                WHERE id = NEW.note_id;
            END IF;
        END IF;
    END IF;
END$$

DELIMITER;