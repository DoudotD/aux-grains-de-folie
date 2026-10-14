<?php
declare(strict_types=1);

require __DIR__ . '/../inc/bootstrap.php';
send_security_headers(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    auth_logout();
}
redirect('index.php');
