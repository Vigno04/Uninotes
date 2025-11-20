-- ============================================
-- TRIGGERS: mantenere `note.vote_count`
-- - upvote (TRUE) counts +1, downvote (FALSE) counts -1
-- - INSERT/DELETE/UPDATE gestiti per mantenere `vote_count` consistente
-- ============================================

DROP TRIGGER IF EXISTS `vote_after_insert`;
DELIMITER $$
CREATE TRIGGER `vote_after_insert` AFTER INSERT ON `vote` FOR EACH ROW
BEGIN
	UPDATE `note`
	SET vote_count = vote_count + (CASE WHEN NEW.vote THEN 1 ELSE -1 END)
	WHERE id = NEW.note_id;
END$$

DROP TRIGGER IF EXISTS `vote_after_delete`;
CREATE TRIGGER `vote_after_delete` AFTER DELETE ON `vote` FOR EACH ROW
BEGIN
	UPDATE `note`
	SET vote_count = vote_count + (CASE WHEN OLD.vote THEN -1 ELSE 1 END)
	WHERE id = OLD.note_id;
END$$

DROP TRIGGER IF EXISTS `vote_after_update`;
CREATE TRIGGER `vote_after_update` AFTER UPDATE ON `vote` FOR EACH ROW
BEGIN
	-- if the vote moved to a different note, adjust both notes
	IF OLD.note_id <> NEW.note_id THEN
		UPDATE `note` SET vote_count = vote_count + (CASE WHEN OLD.vote THEN -1 ELSE 1 END) WHERE id = OLD.note_id;
		UPDATE `note` SET vote_count = vote_count + (CASE WHEN NEW.vote THEN 1 ELSE -1 END) WHERE id = NEW.note_id;
	ELSE
		-- same note: if the vote changed, apply +/-2 accordingly
		IF OLD.vote <> NEW.vote THEN
			IF NEW.vote THEN
				UPDATE `note` SET vote_count = vote_count + 2 WHERE id = NEW.note_id;
			ELSE
				UPDATE `note` SET vote_count = vote_count - 2 WHERE id = NEW.note_id;
			END IF;
		END IF;
	END IF;
END$$
DELIMITER ;