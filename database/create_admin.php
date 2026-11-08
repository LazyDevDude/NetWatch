<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script must be run from the command line.\n");
}

require __DIR__ . '/../app/bootstrap.php';

$username = $argv[1] ?? '';
$email = $argv[2] ?? '';
$password = $argv[3] ?? '';

if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "Usage: php database/create_admin.php USERNAME EMAIL PASSWORD\n");
    fwrite(STDERR, "Password must be at least 12 characters.\n");
    exit(1);
}

$statement = db()->prepare(
    'INSERT INTO users (username, email, password_hash, role)
     VALUES (:username, :email, :password_hash, :role)'
);

try {
    $statement->execute([
        'username' => $username,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'admin',
    ]);
} catch (PDOException $exception) {
    fwrite(STDERR, "Unable to create administrator: " . $exception->getMessage() . "\n");
    exit(1);
}

fwrite(STDOUT, "Administrator created successfully.\n");
