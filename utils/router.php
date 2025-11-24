<?php 
class Router
{
    private $pageMap = [
        'home' => [
            'file'  => 'home.php',
            'title' => 'Uninotes',
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