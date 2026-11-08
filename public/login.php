<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/Helpers/auth.php';
require_once __DIR__ . '/../app/Helpers/csrf.php';
require_once __DIR__ . '/../app/Helpers/view.php';

use App\Controllers\AuthController;
use App\Repositories\UserRepository;

if (is_authenticated()) {
    redirect('/dashboard');
}

$controller = new AuthController(new UserRepository(db()));
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['_csrf'] ?? null);
    $login = (string) ($_POST['login'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($login === '' || $password === '') {
        $error = 'Username/email and password are required.';
    } elseif ($controller->login($login, $password)) {
        redirect('/dashboard');
    } else {
        $error = 'Invalid credentials or inactive account.';
    }
}

$controller->showLogin($error);
