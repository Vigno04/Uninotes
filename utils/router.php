<?php 
class Router
{
    private $pageMap = [
        'home' => [
            'file'  => 'home.php',
            'title' => 'Uninotes',
        ],
        'courses'=> [
            'file'=> 'courses.php',
            'title'=> 'Courses - Uninotes',
        ],
        'course_offerings'=> [
            'file'=> 'course_offerings.php',
            'title'=> 'Course Offerings - Uninotes',
        ],
        'account' => [
            'file'  => 'account.php',
            'title' => 'Profile - Uninotes',
        ],
        'admindashboard'=> [
            'file'=> 'admindashboard.php',
            'title'=> 'Admin Dashboard - Uninotes',
        ],
        'manage_courses'=> [
            'file'=> 'manage_courses.php',
            'title'=> 'Courses - Uninotes',
        ],
        'manage_users'=> [
            'file'=> 'manage_users.php',
            'title'=> 'Users - Uninotes',
        ],
        'view_reports'=> [
            'file'=> 'view_reports.php',
            'title'=> 'Reports - Uninotes',
        ],
        'login' => [
            'file'  => 'login.php',
            'title' => 'Login - Uninotes',
        ],
        'upload'=> [
            'file'=> 'upload.php',
            'title'=> 'Upload Notes - Uninotes',
            ],
        'note_view' => [
            'file'  => 'note_view.php',
            'template' => 'template/note_view.php',
            'title' => 'Uninotes - Note',
        ],
        'note_edit' => [
            'file'  => 'note_edit.php',
            'template' => 'template/note_edit.php',
            'title' => 'Uninotes - Note',
        ],
        'user_notes_upvoted'=> [
            'file'=> 'user_notes_upvoted.php',
            'title' => 'Uninotes - Note Upvoted',
        ],
        'user_notes_uploaded'=> [
            'file'=> 'user_notes_uploaded.php',
            'title' => 'Uninotes - Note Uploaded',
        ],
        'admin_course_edit'=> [
            'file'=> 'admin_course_edit.php',
            'title'=> 'Uninotes - Edit Course',
        ],
        'admin_teacher_create' => [
            'file'=> 'admin_teacher_create.php',
            'title'=> 'Uninotes - Manage Teachers',
        ],
        'admin_teacher_requests' => [
            'file'=> 'admin_teacher_requests.php',
            'title'=> 'Uninotes - Manage Teachers',
        ],
        'teacher_requests' => [
            'file'=> 'teacher_requests.php',
            'title'=> 'Uninotes - Manage Teachers',
        ],
        'admin_edit_teacher' => [
            'file'=> 'admin_edit_teacher.php',
            'title'=> 'Uninotes - Edit Teachers',
        ],
    ];

    public function getRoute()
    {
        // 1. pagina richiesta dal GET o 'login' di default
        $page = (isset($_GET['page']) && array_key_exists($_GET['page'], $this->pageMap))
              ? $_GET['page']
              : 'login';

        $loggedIn = isset($_SESSION['person_id']);
        $role     = $_SESSION['role'] ?? null;

        // 2. se non sei loggata, qualsiasi pagina tranne login → login
        if (!$loggedIn && $page !== 'login') {
            $page = 'login';
        }

        // 3. se non sei admin, non puoi vedere adminaccount
        if ($page === 'adminaccount' && $role !== 'admin') {
            $page = $loggedIn ? 'home' : 'login';
        }

        return $this->pageMap[$page];
    }
}


?>