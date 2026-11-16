<?php
define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';

if (!isLoggedIn()) {
    header('Location: /train_ticket_system/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $user = currentUser();

    if (!$ticketId) {
        setFlash('error', 'Чипта муайян нашудааст.');
        header('Location: /train_ticket_system/my_tickets.php');
        exit;
    }

    // Гирифтани маълумоти чипта
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        setFlash('error', 'Чипта ёфт нашуд.');
        header('Location: /train_ticket_system/my_tickets.php');
        exit;
    }

    // Санҷиши дастрасӣ: Мусофир танҳо чиптаи худашро баргардонда метавонад, кассир ва админ ҳамаро
    if ($user['role'] === 'passenger' && $ticket['passenger_id'] != $user['id']) {
        setFlash('error', 'Шумо ба баргардонидани ин чипта ҳуқуқ надоред.');
        header('Location: /train_ticket_system/my_tickets.php');
        exit;
    }

    if ($ticket['status'] !== 'confirmed') {
        setFlash('error', 'Ин чипта дар ҳолати фаъол қарор надорад ва баргардонида намешавад.');
        header('Location: /train_ticket_system/my_tickets.php');
        exit;
    }

    // Иҷрои амалиёти баргардонидан: маблағ бозпас ва ҷой дар нақша озод мешавад
    $refundStmt = $pdo->prepare("
        UPDATE tickets 
        SET status = 'refunded', 
            payment_status = 'refunded', 
            refunded_at = NOW() 
        WHERE id = ?
    ");
    $refundStmt->execute([$ticketId]);

    setFlash('success', 'Чипта бомуваффақият баргардонида шуд! Маблағи ' . formatTJS($ticket['price']) . ' баргардонида шуда, ҷойи нишаст озод гардид.');

    if ($user['role'] === 'cashier') {
        header('Location: /train_ticket_system/cashier/refund.php');
    } elseif ($user['role'] === 'admin') {
        header('Location: /train_ticket_system/admin/tickets.php');
    } else {
        header('Location: /train_ticket_system/my_tickets.php');
    }
    exit;
}

header('Location: /train_ticket_system/my_tickets.php');
exit;
