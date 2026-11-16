<?php
require_once __DIR__ . '/config/db.php';

session_unset();
session_destroy();

header('Location: /train_ticket_system/login.php');
exit;
