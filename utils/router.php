<?php 
class Router 
{
    private $pageMap = [
        'home' => [
            'file' => 'home.php',
            'title' => 'Uninotes',
        ],
        'useraccount' => [
            'file' => 'userhome.php',
            'title' => 'Uninotes - Account',
        ],
        'adminaccount' => [
            'file' => 'adminhome.php',
            'title' => 'Uninotes - Admin Dashboard',
        ],
        'login' => [
            'file' => 'login.php',
            'title' => 'Uninotes - Login',
        ],
    ];

    public function getRoute() 
    {
        // default: login
        $page = isset($_GET['page']) && array_key_exists($_GET['page'], $this->pageMap) 
            ? $_GET['page'] 
            : 'login';

        // semplice protezione: se non sei loggatə, vai sempre a login
        if (!isset($_SESSION['role']) && $page !== 'login') {
            $page = 'login';
        }

        // protezione admin: solo admin può vedere adminaccount
        if ($page === 'adminaccount' && ($_SESSION['role'] ?? '') !== 'admin') {
            $page = 'home'; // o 'login'
        }

        return $this->pageMap[$page];
    }

}

?>