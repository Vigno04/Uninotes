<?php 
class Router
{
    private $pageMap = [
        'home' => [
            'file'  => 'home.php',
            'title' => 'Uninotes',
        ],
        'useraccount' => [
            'file'  => 'userhome.php',
            'title' => 'Uninotes - Account',
        ],
        'adminaccount' => [
            'file'  => 'adminhome.php',
            'title' => 'Uninotes - Admin Dashboard',
        ],
        'login' => [
            'file'  => 'login.php',
            'title' => 'Uninotes - Login',
        ],
        'search'=> [
            'file'=> 'search.php',
            'title'=> 'Uninotes - Cerca Appunti',
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