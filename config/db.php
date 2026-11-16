<?php
// ==============================================================
// Пайвастшавӣ ба Пойгоҳи Додаҳо ва Функсияҳои Ёрирасон
// ==============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = '127.0.0.1;port=3307';
$db   = 'train_tickets_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die('<div style="font-family: sans-serif; padding: 25px; background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; border-radius: 8px; max-width: 600px; margin: 40px auto; line-height: 1.6;">
        <h2 style="margin-top: 0;">Хатогӣ дар пайвастшавӣ ба базаи маълумот!</h2>
        <p>Илтимос, санҷед, ки сервери MySQL (XAMPP / OpenServer) фаъол аст ва пойгоҳи додаҳо бо номи <strong>train_tickets_db</strong> дар phpMyAdmin сохта шудааст.</p>
        <p><em>Паёми хатогӣ: ' . htmlspecialchars($e->getMessage()) . '</em></p>
    </div>');
}

/**
 * Тоза кардани ҷойҳои бандшудае, ки муҳлати 15 дақиқаашон гузаштааст
 * Талаботи ТЗ: "Ҷой ба муддати 15 дақиқа то пардохт банд карда мешавад; агар пардохт нашавад, ҷой холӣ мешавад."
 */
function cleanExpiredReservations($pdo) {
    try {
        $stmt = $pdo->prepare("
            UPDATE tickets 
            SET status = 'cancelled', payment_status = 'expired' 
            WHERE status = 'reserved' 
              AND payment_status = 'pending' 
              AND expires_at < NOW()
        ");
        $stmt->execute();
    } catch (Exception $e) {
        // хатогӣ сабт шавад
    }
}

// Ҳангоми ҳар як боргирии саҳифа ҷойҳои куҳнашуда худкор холӣ мешаванд
cleanExpiredReservations($pdo);

/**
 * Санҷиши воридшавии корбар
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Баргардонидани маълумоти корбари ҷорӣ
 */
function currentUser() {
    return $_SESSION['user'] ?? null;
}

/**
 * Санҷиши дастрасӣ аз рӯи нақш (role)
 */
function requireRole($role) {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Барои ворид шудан ба ин саҳифа аввал вориди система шавед!';
        header('Location: /train_ticket_system/login.php');
        exit;
    }
    
    $user = currentUser();
    if ($user['role'] !== $role && $user['role'] !== 'admin') {
        $_SESSION['flash_error'] = 'Шумо ба ин бахш ҳуқуқи дастрасӣ надоред!';
        header('Location: /train_ticket_system/index.php');
        exit;
    }
}

/**
 * Паёмҳои фаврӣ (Flash messages)
 */
function setFlash($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

function getFlash($type) {
    if (isset($_SESSION['flash_' . $type])) {
        $msg = $_SESSION['flash_' . $type];
        unset($_SESSION['flash_' . $type]);
        return $msg;
    }
    return null;
}

/**
 * Форматкунии маблағ (бо сомонӣ)
 */
function formatTJS($amount) {
    return number_format($amount, 2, '.', ' ') . ' сомонӣ';
}

/**
 * Генерацияи рақами ягонаи чипта
 */
function generateTicketNumber() {
    return 'TKT-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Тарҷума ва намоиши зебои навъи вагон
 */
function carTypeName($type) {
    switch ($type) {
        case 'sv_lux':
            return 'СВ / Люкс (Бароҳатии олӣ)';
        case 'coupe':
            return 'Купе (Бароҳат)';
        case 'platskart':
            return 'Плацкарт (Умумӣ)';
        default:
            return ucfirst($type);
    }
}

function seatTypeName($type) {
    switch ($type) {
        case 'lower':
            return 'Поёнӣ';
        case 'upper':
            return 'Болоӣ';
        case 'side_lower':
            return 'Паҳлӯӣ поён';
        case 'side_upper':
            return 'Паҳлӯӣ боло';
        default:
            return $type;
    }
}
