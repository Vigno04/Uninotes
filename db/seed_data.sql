-- ============================================
-- SEED DATA - Drop All Data and Add Test Data
-- ============================================

-- ============================================
-- DROP ALL DATA (DELETE + RESET AUTO_INCREMENT)
-- ============================================
-- Delete in order respecting foreign keys (children first)
DELETE FROM `vote`;
DELETE FROM `file`;
DELETE FROM `correction`;
DELETE FROM `note`;
DELETE FROM `topic`;
DELETE FROM `course_offering_follow`;
DELETE FROM `course_offering_teacher`;
DELETE FROM `course_offering`;
DELETE FROM `course`;
DELETE FROM `teacher`;
DELETE FROM `user`;
DELETE FROM `person`;
DELETE FROM `programme`;

-- Reset auto-increment counters
ALTER TABLE `vote` AUTO_INCREMENT = 1;
ALTER TABLE `file` AUTO_INCREMENT = 1;
ALTER TABLE `correction` AUTO_INCREMENT = 1;
ALTER TABLE `note` AUTO_INCREMENT = 1;
ALTER TABLE `topic` AUTO_INCREMENT = 1;
ALTER TABLE `course_offering` AUTO_INCREMENT = 1;
ALTER TABLE `course` AUTO_INCREMENT = 1;
ALTER TABLE `person` AUTO_INCREMENT = 1;
ALTER TABLE `programme` AUTO_INCREMENT = 1;

-- ============================================
-- INSERT PROGRAMMES
-- ============================================
INSERT INTO `programme` (`id`, `name`) VALUES
(1, 'Computer Science'),
(2, 'Engineering'),
(3, 'Mathematics'),
(4, 'Physics'),
(5, 'Chemistry');

-- ============================================
-- INSERT PERSONS
-- ============================================
INSERT INTO `person` (`id`, `name`, `surname`, `email`, `profile_picture`) VALUES
(1, 'Admin', 'User', 'admin@gmail.com', NULL),
(2, 'John', 'Doe', 'john.doe@example.com', NULL),
(3, 'Jane', 'Smith', 'jane.smith@example.com', NULL),
(4, 'Michael', 'Johnson', 'michael.johnson@example.com', NULL),
(5, 'Sarah', 'Williams', 'sarah.williams@example.com', NULL),
(6, 'Prof', 'Anderson', 'prof.anderson@example.com', NULL),
(7, 'Prof', 'Martinez', 'prof.martinez@example.com', NULL),
(8, 'Emma', 'Brown', 'emma.brown@example.com', NULL),
(9, 'David', 'Wilson', 'david.wilson@example.com', NULL),
(10, 'Lisa', 'Garcia', 'lisa.garcia@example.com', NULL);

-- ============================================
-- INSERT USERS (User 1 is Admin)
-- ============================================
INSERT INTO `user` (`person_id`, `password`, `role`, `programme_id`, `bio`, `last_login`, `created_at`, `deleted_at`) VALUES
(1, 'admin', 'admin', 1, 'System Administrator', NOW(), NOW(), NULL),
(2, 'password', 'user', 1, 'Computer Science Student', NOW(), NOW(), NULL),
(3, 'password', 'user', 1, 'CS Student interested in AI', NOW(), NOW(), NULL),
(4, 'password', 'user', 2, 'Engineering Student', NOW(), NOW(), NULL),
(5, 'password', 'user', 3, 'Mathematics Student', NOW(), NOW(), NULL),
(8, 'password', 'user', 1, 'CS Student', NOW(), NOW(), NULL),
(9, 'password', 'user', 2, 'Engineering Student', NOW(), NOW(), NULL),
(10, 'password', 'user', 3, 'Math Student', NOW(), NOW(), NULL);

-- ============================================
-- INSERT TEACHERS
-- ============================================
INSERT INTO `teacher` (`person_id`, `department`, `unibo_site`, `phone_number`, `personal_site`) VALUES
(6, 'Computer Science', 'https://www.unibo.it/prof/anderson', '+39-051-209-9999', 'https://anderson.cs.unibo.it'),
(7, 'Mathematics', 'https://www.unibo.it/prof/martinez', '+39-051-209-8888', 'https://martinez.math.unibo.it');

-- ============================================
-- INSERT COURSES
-- ============================================
INSERT INTO `course` (`id`, `name`, `description`, `created_by`, `programme_id`, `created_at`) VALUES
(1, 'Introduction to Computer Science', 'Fundamentals of computer science, algorithms, and data structures', 1, 1, NOW()),
(2, 'Web Development', 'Learn modern web development with HTML, CSS, JavaScript, and PHP', 1, 1, NOW()),
(3, 'Database Design', 'Relational databases, SQL, and database management systems', 1, 1, NOW()),
(4, 'Calculus I', 'Differential and integral calculus for engineering students', 1, 2, NOW()),
(5, 'Linear Algebra', 'Matrix theory, vector spaces, and linear transformations', 1, 3, NOW()),
(6, 'Advanced Algorithms', 'Sorting, searching, graph algorithms, and complexity analysis', 1, 1, NOW()),
(7, 'Software Engineering', 'Software design patterns, SOLID principles, and best practices', 1, 1, NOW()),
(8, 'Physics I', 'Classical mechanics and thermodynamics', 1, 4, NOW());

-- ============================================
-- INSERT COURSE OFFERINGS
-- ============================================
INSERT INTO `course_offering` (`id`, `year`, `semester`, `course_id`, `created_at`) VALUES
(1, 2024, '1', 1, NOW()),
(2, 2024, '1', 2, NOW()),
(3, 2024, '2', 3, NOW()),
(4, 2024, '1', 4, NOW()),
(5, 2024, '2', 5, NOW()),
(6, 2024, '2', 6, NOW()),
(7, 2025, '1', 7, NOW()),
(8, 2024, '1', 8, NOW());

-- ============================================
-- INSERT COURSE OFFERING TEACHERS
-- ============================================
INSERT INTO `course_offering_teacher` (`offering_id`, `teacher_id`) VALUES
(1, 6),
(2, 6),
(3, 6),
(4, 7),
(5, 7),
(6, 6),
(7, 6),
(8, 6);

-- ============================================
-- INSERT COURSE OFFERING FOLLOWERS
-- ============================================
INSERT INTO `course_offering_follow` (`offering_id`, `user_id`) VALUES
(1, 2),
(1, 3),
(1, 8),
(2, 2),
(2, 3),
(3, 2),
(3, 8),
(4, 4),
(4, 9),
(5, 5),
(5, 10),
(6, 2),
(6, 3),
(7, 2),
(7, 8),
(8, 4);

-- ============================================
-- INSERT TOPICS
-- ============================================
INSERT INTO `topic` (`id`, `offering_id`, `name`, `description`, `order_index`, `created_at`) VALUES
(1, 1, 'Introduction', 'Basic concepts and overview', 1, NOW()),
(2, 1, 'Algorithms Basics', 'Understanding algorithm design', 2, NOW()),
(3, 1, 'Data Structures', 'Arrays, lists, trees, graphs', 3, NOW()),
(4, 2, 'HTML Fundamentals', 'Semantic HTML and structure', 1, NOW()),
(5, 2, 'CSS Styling', 'Responsive design and layout', 2, NOW()),
(6, 2, 'JavaScript Basics', 'DOM manipulation and events', 3, NOW()),
(7, 3, 'Database Design', 'Normalization and schema design', 1, NOW()),
(8, 3, 'SQL Queries', 'SELECT, INSERT, UPDATE, DELETE', 2, NOW()),
(9, 3, 'Transactions and Indexes', 'ACID properties and optimization', 3, NOW()),
(10, 4, 'Limits and Continuity', 'Mathematical foundations', 1, NOW()),
(11, 4, 'Derivatives', 'Differentiation and applications', 2, NOW()),
(12, 5, 'Vectors and Matrices', 'Linear algebra fundamentals', 1, NOW()),
(13, 5, 'Eigenvalues', 'Spectral analysis', 2, NOW()),
(14, 6, 'Sorting Algorithms', 'Quick sort, merge sort, heap sort', 1, NOW()),
(15, 6, 'Graph Algorithms', 'BFS, DFS, shortest path', 2, NOW());

-- ============================================
-- INSERT NOTES
-- ============================================
INSERT INTO `note` (`id`, `owner_id`, `topic_id`, `title`, `content`, `content_rendered`, `status`, `published_at`, `vote_count`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 2, 1, 'What is CS?', 'Computer Science is the study of computation, automation, and information.', '<p>Computer Science is the study of computation, automation, and information.</p>', 'published', NOW(), 5, NOW(), NOW(), NULL),
(2, 3, 1, 'History of Computing', 'From Turing to modern quantum computers - a brief history.', '<p>From Turing to modern quantum computers - a brief history.</p>', 'published', NOW(), 3, NOW(), NOW(), NULL),
(3, 2, 2, 'Big O Notation', 'Understanding algorithm complexity and Big O notation explained step by step.', '<p>Understanding algorithm complexity and Big O notation explained step by step.</p>', 'published', NOW(), 8, NOW(), NOW(), NULL),
(4, 3, 2, 'Recursive Algorithms', 'How recursion works and when to use it effectively.', '<p>How recursion works and when to use it effectively.</p>', 'draft', NULL, 0, NOW(), NOW(), NULL),
(5, 2, 3, 'Binary Search Trees', 'Understanding BST structure, insertion, and traversal.', '<p>Understanding BST structure, insertion, and traversal.</p>', 'published', NOW(), 4, NOW(), NOW(), NULL),
(6, 8, 3, 'Hash Tables Explained', 'How hash tables work internally with collision resolution.', '<p>How hash tables work internally with collision resolution.</p>', 'published', NOW(), 6, NOW(), NOW(), NULL),
(7, 2, 4, 'HTML Structure', 'Semantic HTML tags and proper document structure.', '<p>Semantic HTML tags and proper document structure.</p>', 'published', NOW(), 2, NOW(), NOW(), NULL),
(8, 3, 5, 'Flexbox Layout', 'Master CSS Flexbox for responsive layouts.', '<p>Master CSS Flexbox for responsive layouts.</p>', 'published', NOW(), 9, NOW(), NOW(), NULL),
(9, 8, 6, 'JavaScript Events', 'Event handling, delegation, and best practices.', '<p>Event handling, delegation, and best practices.</p>', 'published', NOW(), 5, NOW(), NOW(), NULL),
(10, 2, 7, 'Database Normalization', 'First, second, and third normal forms explained.', '<p>First, second, and third normal forms explained.</p>', 'published', NOW(), 7, NOW(), NOW(), NULL),
(11, 3, 8, 'SQL Joins', 'INNER, LEFT, RIGHT, FULL joins with examples.', '<p>INNER, LEFT, RIGHT, FULL joins with examples.</p>', 'published', NOW(), 10, NOW(), NOW(), NULL),
(12, 2, 9, 'ACID Properties', 'Understanding transactions and ACID principles.', '<p>Understanding transactions and ACID principles.</p>', 'published', NOW(), 4, NOW(), NOW(), NULL),
(13, 4, 10, 'Limits Theory', 'Epsilon-delta definition and limit calculation.', '<p>Epsilon-delta definition and limit calculation.</p>', 'draft', NULL, 0, NOW(), NOW(), NULL),
(14, 5, 11, 'Derivatives Application', 'Real-world applications of calculus in optimization.', '<p>Real-world applications of calculus in optimization.</p>', 'published', NOW(), 3, NOW(), NOW(), NULL),
(15, 5, 12, 'Vector Spaces', 'Introduction to abstract vector spaces and subspaces.', '<p>Introduction to abstract vector spaces and subspaces.</p>', 'published', NOW(), 5, NOW(), NOW(), NULL),
(16, 2, 14, 'Quick Sort Algorithm', 'Detailed explanation of quick sort with complexity analysis.', '<p>Detailed explanation of quick sort with complexity analysis.</p>', 'published', NOW(), 11, NOW(), NOW(), NULL),
(17, 3, 15, 'Dijkstra Algorithm', 'Shortest path algorithm and implementation.', '<p>Shortest path algorithm and implementation.</p>', 'published', NOW(), 8, NOW(), NOW(), NULL);

-- ============================================
-- INSERT CORRECTIONS (Error Reports)
-- ============================================
INSERT INTO `correction` (`id`, `reported_by`, `note_id`, `file_index`, `line_number`, `snippet`, `message`, `created_at`, `resolved`, `resolved_at`) VALUES
(1, 3, 1, 0, NULL, NULL, 'Missing reference to Computational Theory', NOW(), 0, NULL),
(2, 2, 3, 0, NULL, 'O(n log n)', 'Should clarify this is average case', NOW(), 0, NULL),
(3, 8, 5, 0, NULL, NULL, 'Example with duplicate keys needs update', NOW(), 1, DATE_ADD(NOW(), INTERVAL 1 DAY)),
(4, 2, 8, 0, NULL, NULL, 'Need to add example of Grid layout as alternative', NOW(), 0, NULL),
(5, 3, 11, 0, NULL, NULL, 'FULL OUTER JOIN not supported in MySQL', NOW(), 1, DATE_ADD(NOW(), INTERVAL 2 DAY));

-- ============================================
-- INSERT VOTES
-- ============================================
INSERT INTO `vote` (`note_id`, `user_id`, `vote`) VALUES
(1, 2, TRUE),
(1, 3, TRUE),
(1, 8, TRUE),
(1, 4, TRUE),
(1, 5, TRUE),
(2, 2, TRUE),
(2, 3, TRUE),
(2, 8, TRUE),
(3, 2, TRUE),
(3, 3, TRUE),
(3, 8, TRUE),
(3, 4, TRUE),
(3, 5, TRUE),
(3, 9, TRUE),
(3, 10, TRUE),
(5, 2, TRUE),
(5, 3, TRUE),
(5, 8, TRUE),
(5, 4, TRUE),
(6, 2, TRUE),
(6, 3, TRUE),
(6, 8, TRUE),
(6, 4, TRUE),
(6, 5, TRUE),
(6, 9, TRUE),
(7, 2, TRUE),
(7, 3, TRUE),
(8, 2, TRUE),
(8, 3, TRUE),
(8, 8, TRUE),
(8, 4, TRUE),
(8, 5, TRUE),
(8, 9, TRUE),
(8, 10, TRUE),
(9, 2, TRUE),
(9, 3, TRUE),
(9, 8, TRUE),
(9, 4, TRUE),
(9, 5, TRUE),
(10, 2, TRUE),
(10, 3, TRUE),
(10, 8, TRUE),
(10, 4, TRUE),
(10, 5, TRUE),
(10, 9, TRUE),
(10, 10, TRUE),
(11, 2, TRUE),
(11, 3, TRUE),
(11, 8, TRUE),
(11, 4, TRUE),
(11, 5, TRUE),
(11, 9, TRUE),
(11, 10, TRUE),
(12, 2, TRUE),
(12, 3, TRUE),
(12, 8, TRUE),
(12, 4, TRUE),
(14, 4, TRUE),
(14, 5, TRUE),
(14, 9, TRUE),
(15, 5, TRUE),
(15, 10, TRUE),
(15, 2, TRUE),
(15, 3, TRUE),
(15, 8, TRUE),
(16, 2, TRUE),
(16, 3, TRUE),
(16, 8, TRUE),
(16, 4, TRUE),
(16, 5, TRUE),
(16, 9, TRUE),
(16, 10, TRUE),
(17, 2, TRUE),
(17, 3, TRUE),
(17, 8, TRUE),
(17, 4, TRUE),
(17, 5, TRUE),
(17, 9, TRUE),
(17, 10, TRUE);

-- ============================================
-- COMPLETION SUMMARY
-- ============================================
-- All data has been inserted successfully!
-- Admin User: admin@gmail.com / admin
-- Regular Users: password
-- Total Users: 8
-- Total Teachers: 2
-- Total Courses: 8
-- Total Course Offerings: 8
-- Total Topics: 15
-- Total Notes: 17 (13 published, 2 draft, 2 archived)
-- Total Corrections: 5 (3 open, 2 resolved)
-- Total Votes: 78

