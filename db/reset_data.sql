-- RESET DATA FOR UniNotes (solo dati, NON schema)
-- Usa DELETE invece di TRUNCATE per evitare problemi di FK

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM vote;
DELETE FROM file;
DELETE FROM correction;
DELETE FROM note;
DELETE FROM topic;
DELETE FROM course_offering_follow;
DELETE FROM course_offering_teacher;
DELETE FROM course_offering;
DELETE FROM course;
DELETE FROM teacher;
DELETE FROM user;
DELETE FROM person;

-- (opzionale) reset degli AUTO_INCREMENT
ALTER TABLE person AUTO_INCREMENT = 1;
ALTER TABLE course AUTO_INCREMENT = 1;
ALTER TABLE course_offering AUTO_INCREMENT = 1;
ALTER TABLE topic AUTO_INCREMENT = 1;
ALTER TABLE note AUTO_INCREMENT = 1;
ALTER TABLE file AUTO_INCREMENT = 1;
ALTER TABLE correction AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;
