-- ============================================
-- POPULATE UniNotes database with sample data
-- ============================================

-- (opzionale) assicuriamoci di usare il DB giusto
-- USE uninotes;

-- -------------------------
-- PERSON
-- -------------------------
INSERT INTO person (id, name, surname, email, profile_picture)
VALUES
  (1, 'Mario',    'Rossi',   'mario.rossi@studenti.unibo.it',   NULL),
  (2, 'Laura',    'Bianchi', 'laura.bianchi@studenti.unibo.it', NULL),
  (3, 'Giuseppe', 'Verdi',   'giuseppe.verdi@studenti.unibo.it',NULL),
  (4, 'Admin',    'User',    'admin@uninotes.it',               NULL),
  (5, 'Maria',    'Rossi',   'maria.rossi@unibo.it',            NULL), -- docente
  (6, 'Paolo',    'Bianchi', 'paolo.bianchi@unibo.it',          NULL); -- docente

-- -------------------------
-- USER (utenti applicazione)
-- password per ora in chiaro: "password"
-- -------------------------
INSERT INTO user (person_id, password, role, last_login)
VALUES
  (1, 'password', 'user',  NULL),
  (2, 'password', 'user',  NULL),
  (3, 'password', 'user',  NULL),
  (4, 'admin123', 'admin', NULL);

-- -------------------------
-- TEACHER (docenti)
-- -------------------------
INSERT INTO teacher (person_id, department, unibo_site, phone_number, personal_site)
VALUES
  (5, 'Ingegneria e Scienze Informatiche',
   'https://www.unibo.it/docenti/maria.rossi', NULL, NULL),
  (6, 'Ingegneria e Scienze Informatiche',
   'https://www.unibo.it/docenti/paolo.bianchi', NULL, NULL);

-- -------------------------
-- PROGRAMME
-- -------------------------

INSERT INTO `programme` (`name`) VALUES
  ('Ingegneria e Scienze Informatiche'),
  ('Informatica'),
  ('Ingegneria Informatica'),
  ('Fisica'),
  ('Giurisprudenza');


-- -------------------------
-- COURSE
-- -------------------------
INSERT INTO course (id, name, description, created_by)
VALUES
  (1, 'Algoritmi e Strutture Dati',
     'Introduzione ad algoritmi fondamentali e strutture dati di base.',
     4),
  (2, 'Analisi Matematica I',
     'Limiti, derivate, integrali in una variabile reale.',
     4),
  (3, 'Basi di Dati',
     'Progettazione concettuale, logica e fisica di database relazionali.',
     4);

-- -------------------------
-- COURSE OFFERING
-- -------------------------
INSERT INTO course_offering (id, year, semester, course_id)
VALUES
  (1, 2024, '1', 1), -- Algoritmi e Strutture Dati, 1° semestre
  (2, 2024, '1', 2), -- Analisi I, 1° semestre
  (3, 2024, '2', 3); -- Basi di Dati, 2° semestre

-- -------------------------
-- COURSE_OFFERING_TEACHER (relazione molti-a-molti)
-- -------------------------
INSERT INTO course_offering_teacher (offering_id, teacher_id)
VALUES
  (1, 5), -- Algoritmi tenuto da Maria Rossi
  (2, 6), -- Analisi I tenuto da Paolo Bianchi
  (3, 5); -- Basi di Dati tenuto da Maria Rossi

-- -------------------------
-- COURSE_OFFERING_FOLLOW (studenti che seguono un corso)
-- -------------------------
INSERT INTO course_offering_follow (offering_id, user_id)
VALUES
  (1, 1), -- Mario segue Algoritmi
  (1, 2), -- Laura segue Algoritmi
  (2, 1), -- Mario segue Analisi
  (3, 3); -- Giuseppe segue Basi di Dati

-- -------------------------
-- TOPIC
-- -------------------------
INSERT INTO topic (id, offering_id, name, description, order_index)
VALUES
  (1, 1, 'Introduzione agli Algoritmi',
      'Concetti base, notazione O-grande, esempi semplici.', 1),
  (2, 1, 'Strutture Dati Lineari',
      'Liste, pile, code, implementazioni e complessità.', 2),
  (3, 3, 'SQL di base',
      'SELECT, INSERT, UPDATE, DELETE, clausole principali.', 1),
  (4, 3, 'Normalizzazione',
      'Forme normali da 1NF a BCNF, dipendenze funzionali.', 2);

-- -------------------------
-- NOTE
-- -------------------------
INSERT INTO note
  (id, owner_id, topic_id, title, content, content_rendered,
   status, published_at, vote_count)
VALUES
  (1, 1, 1,
   'Introduzione agli Algoritmi - Appunti Lezione 1',
   'Questi appunti coprono i concetti base di algoritmo, input, output e notazione O-grande...',
   NULL,
   'published', NOW(), 3),

  (2, 2, 2,
   'Strutture Dati Lineari - Liste, Pile e Code',
   'Appunti completi su liste, pile e code con esempi di implementazione in pseudocodice...',
   NULL,
   'published', NOW(), 5),

  (3, 3, 3,
   'SQL di base - Esempi di query',
   'Bozza di appunti con esempi di SELECT, WHERE, ORDER BY e JOIN semplici.',
   NULL,
   'draft', NULL, 0),

  (4, 1, 4,
   'Normalizzazione - Riassunto Forme Normali',
   'Riassunto delle forme normali con esempi di decomposizione di schemi relazionali.',
   NULL,
   'published', NOW(), 1);

-- -------------------------
-- FILE (allegati alle note)
-- -------------------------
INSERT INTO file
  (id, note_id, filename, storage_path, file_type, file_size, mime_type, uploaded_by)
VALUES
  (1, 1, 'algoritmi-introduzione.pdf',
      'upload/algoritmi-introduzione.pdf', 'pdf', 524288,
      'application/pdf', 1),
  (2, 2, 'liste-pile-code.pdf',
      'upload/liste-pile-code.pdf', 'pdf', 734003,
      'application/pdf', 2),
  (3, 4, 'normalizzazione-riassunto.pdf',
      'upload/normalizzazione-riassunto.pdf', 'pdf', 419430,
      'application/pdf', 1);

-- -------------------------
-- VOTE (upvote / downvote)
-- TRUE = 1 (upvote), FALSE = 0 (downvote)
-- -------------------------
INSERT INTO vote (note_id, user_id, vote)
VALUES
  (1, 2, TRUE),
  (1, 3, TRUE),
  (1, 4, TRUE),
  (2, 1, TRUE),
  (2, 3, TRUE),
  (2, 4, TRUE),
  (4, 2, TRUE);

-- -------------------------
-- CORRECTION (segnalazioni)
-- -------------------------
INSERT INTO correction
  (id, reported_by, note_id, file_index, line_number, snippet, message, resolved)
VALUES
  (1, 2, 1, 1, 42,
   'T(n) = n^3',
   'Credo che la complessità dovrebbe essere O(n^2) e non O(n^3) per questo algoritmo.',
   FALSE);