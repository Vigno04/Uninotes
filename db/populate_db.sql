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
  (6, 'Paolo',    'Bianchi', 'paolo.bianchi@unibo.it',          NULL), -- docente

  -- new students / teachers
  (7, 'Elena',    'Neri',    'elena.neri@studenti.unibo.it',    NULL),
  (8, 'Luca',     'Romano',  'luca.romano@studenti.unibo.it',   NULL),
  (9, 'Silvia',   'Conti',   'silvia.conti@unibo.it',           NULL), -- docente
  (10,'Andrea',   'De Angelis','andrea.deangelis@unibo.it',     NULL); -- docente

-- -------------------------
-- USER (utenti applicazione)
-- password per ora in chiaro: "password"
-- -------------------------
INSERT INTO user (person_id, password, role, last_login)
VALUES
  (1, 'password', 'user',  NULL),
  (2, 'password', 'user',  NULL),
  (3, 'password', 'user',  NULL),
  (4, 'admin123', 'admin', NULL),

  -- new student accounts
  (7, 'password', 'user',  NULL),
  (8, 'password', 'user',  NULL);

-- -------------------------
-- TEACHER (docenti)
-- -------------------------
INSERT INTO teacher (person_id, department, unibo_site, phone_number, personal_site)
VALUES
  (5, 'Ingegneria e Scienze Informatiche',
   'https://www.unibo.it/docenti/maria.rossi', NULL, NULL),
  (6, 'Ingegneria e Scienze Informatiche',
   'https://www.unibo.it/docenti/paolo.bianchi', NULL, NULL),
  (9, 'Fisica e Astronomia',
   'https://www.unibo.it/docenti/silvia.conti', NULL, NULL),
  (10,'Economia',
   'https://www.unibo.it/docenti/andrea.deangelis', NULL, NULL);

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
     4),

  -- new courses
  (4, 'Fisica Generale I',
     'Meccanica classica: cinematica, dinamica, lavoro ed energia.',
     4),
  (5, 'Economia Politica',
     'Domanda, offerta, equilibrio di mercato e modelli base.',
     4),
  (6, 'Diritto Costituzionale',
     'Fonti del diritto, forma di Stato e forma di governo.',
     4);

-- -------------------------
-- COURSE OFFERING
-- -------------------------
INSERT INTO course_offering (id, year, semester, course_id)
VALUES
  (1, 2024, '1', 1), -- Algoritmi e Strutture Dati, 1° semestre
  (2, 2024, '1', 2), -- Analisi I, 1° semestre
  (3, 2024, '2', 3), -- Basi di Dati, 2° semestre

  -- new offerings
  (4, 2024, '1', 4), -- Fisica Generale I
  (5, 2024, '2', 5), -- Economia Politica
  (6, 2024, '2', 6); -- Diritto Costituzionale

-- -------------------------
-- COURSE_OFFERING_TEACHER (relazione molti-a-molti)
-- -------------------------
INSERT INTO course_offering_teacher (offering_id, teacher_id)
VALUES
  (1, 5), -- Algoritmi tenuto da Maria Rossi
  (2, 6), -- Analisi I tenuto da Paolo Bianchi
  (3, 5), -- Basi di Dati tenuto da Maria Rossi

  -- new links
  (4, 9),  -- Fisica da Silvia Conti
  (5, 10), -- Economia da Andrea De Angelis
  (6, 10); -- Diritto Costituzionale da Andrea De Angelis

-- -------------------------
-- COURSE_OFFERING_FOLLOW (studenti che seguono un corso)
-- -------------------------
INSERT INTO course_offering_follow (offering_id, user_id)
VALUES
  (1, 1), -- Mario segue Algoritmi
  (1, 2), -- Laura segue Algoritmi
  (2, 1), -- Mario segue Analisi
  (3, 3), -- Giuseppe segue Basi di Dati

  -- new follows
  (4, 2), -- Laura segue Fisica
  (4, 7), -- Elena segue Fisica
  (5, 1), -- Mario segue Economia
  (5, 8), -- Luca segue Economia
  (6, 3); -- Giuseppe segue Diritto

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
      'Forme normali da 1NF a BCNF, dipendenze funzionali.', 2),

  -- new topics
  (5, 4, 'Cinematica',
      'Moto rettilineo uniforme, uniformemente accelerato, grafici spazio-tempo.', 1),
  (6, 4, 'Dinamica',
      'Leggi di Newton, forze, attrito, piani inclinati.', 2),
  (7, 5, 'Domanda e Offerta',
      'Curve di domanda e offerta, equilibrio di mercato.', 1),
  (8, 5, 'Elasticità',
      'Elasticità della domanda rispetto al prezzo e al reddito.', 2),
  (9, 6, 'Fonti del diritto',
      'Costituzione, leggi, regolamenti, gerarchia delle fonti.', 1),
  (10, 6, 'Forme di governo',
       'Parlamentare, presidenziale, semipresidenziale.', 2);

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
   'published', NOW(), 1),

  -- new notes
  (5, 7, 5,
   'Fisica I - Riassunto di Cinematica',
   'Moto uniforme, accelerato, grafici e interpretazione fisica dei parametri.',
   NULL,
   'published', NOW(), 4),

  (6, 2, 6,
   'Fisica I - Esercizi di Dinamica',
   'Raccolta di esercizi su piani inclinati, attrito e forze vincolari.',
   NULL,
   'draft', NULL, 0),

  (7, 8, 7,
   'Economia Politica - Domanda e Offerta',
   'Definizioni, esempi grafici e casi di spostamento delle curve.',
   NULL,
   'published', NOW(), 2),

  (8, 1, 8,
   'Economia Politica - Elasticità',
   'Appunti sulle varie forme di elasticità della domanda e applicazioni pratiche.',
   NULL,
   'published', NOW(), 3),

  (9, 3, 9,
   'Diritto Costituzionale - Fonti del diritto',
   'Schema delle fonti, gerarchia e rapporti tra Costituzione e leggi ordinarie.',
   NULL,
   'published', NOW(), 1),

  (10, 2, 10,
   'Forme di governo - Schema riassuntivo',
   'Confronto tra forme di governo parlamentare, presidenziale e semipresidenziale.',
   NULL,
   'draft', NULL, 0);

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
      'application/pdf', 1),

  -- new files
  (4, 5, 'fisica-cinematica-riassunto.pdf',
      'upload/fisica-cinematica-riassunto.pdf', 'pdf', 600000,
      'application/pdf', 7),
  (5, 7, 'economia-domanda-offerta-notes.pdf',
      'upload/economia-domanda-offerta-notes.pdf', 'pdf', 550000,
      'application/pdf', 8),
  (6, 9, 'diritto-fonti-schema.pdf',
      'upload/diritto-fonti-schema.pdf', 'pdf', 300000,
      'application/pdf', 3);

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
  (4, 2, TRUE),

  -- new votes
  (5, 1, TRUE),
  (5, 2, TRUE),
  (5, 3, TRUE),
  (7, 1, TRUE),
  (7, 7, TRUE),
  (8, 8, TRUE),
  (9, 2, TRUE);

-- -------------------------
-- CORRECTION (segnalazioni)
-- -------------------------
INSERT INTO correction
  (id, reported_by, note_id, file_index, line_number, snippet, message, resolved)
VALUES
  (1, 2, 1, 1, 42,
   'T(n) = n^3',
   'Credo che la complessità dovrebbe essere O(n^2) e non O(n^3) per questo algoritmo.',
   FALSE),

  -- new corrections
  (2, 1, 5, 1, 15,
   'velocità media',
   'La formula indicata è in realtà la velocità istantanea, non quella media.',
   FALSE),

  (3, 8, 7, 1, NULL,
   'spostamento della curva di domanda',
   'Il grafico sembra mostrare una variazione lungo la curva, non uno spostamento della curva.',
   FALSE);
