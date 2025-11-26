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
            'title' => 'Uninotes - Profilo',
        ],
        'admindashboard'=> [
            'file'=> 'admindashboard.php',
            'title'=> 'Uninotes- Admin Dashboard',
        ],
        'manage_courses'=> [
            'file'=> 'manage_courses.php',
            'title'=> 'Uninotes - Corsi',
        ],
        'manage_users'=> [
            'file'=> 'manage_users.php',
            'title'=> 'Uninotes - Users',
        ],
        'view_reports'=> [
            'file'=> 'view_reports.php',
            'title'=> 'Uninotes - Reports',
        ],
        'login' => [
            'file'  => 'login.php',
            'title' => 'Uninotes - Login',
        ],
        'note_view' => [
            'file'  => 'note_view.php',
            'title' => 'Uninotes - Nota',
        ],
        'note_edit' => [
            'file'  => 'note_edit.php',
            'template' => 'template/note_edit.php',
            'title' => 'Uninotes - Nota',
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