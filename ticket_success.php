<?php
define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Чипта бомуваффақият харида шуд';

$ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
if (!$ticketId) {
    header('Location: /train_ticket_system/index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT tk.*, 
           t.departure_time, t.arrival_time,
           r.route_name,
           s1.city AS from_city, s1.name AS from_station,
           s2.city AS to_city, s2.name AS to_station,
           tr.train_number,
           c.car_number, c.car_type,
           s.seat_number, s.seat_type
    FROM tickets tk
    JOIN trips t ON tk.trip_id = t.id
    JOIN routes r ON t.route_id = r.id
    JOIN stations s1 ON r.from_station_id = s1.id
    JOIN stations s2 ON r.to_station_id = s2.id
    JOIN trains tr ON t.train_id = tr.id
    JOIN cars c ON tk.car_id = c.id
    JOIN seats s ON tk.seat_id = s.id
    WHERE tk.id = ?
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die("Чипта ёфт нашуд!");
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container mx-auto px-4 py-12">
    <div class="max-w-2xl mx-auto glass-panel p-8 text-center border-emerald-500/30 shadow-2xl">
        
        <!-- Нишони муваффақият -->
        <div class="w-20 h-20 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 flex items-center justify-center text-4xl mx-auto mb-6 shadow-lg shadow-emerald-500/20">
            <i class="fa-solid fa-check"></i>
        </div>

        <h1 class="text-3xl font-extrabold text-white mb-2">Пардохт бомуваффақият анҷом ёфт!</h1>
        <p class="text-slate-400 text-sm mb-6">
            Чиптаи электронии шумо ба қайд гирифта шуд ва омодаи чоп мебошад.
        </p>

        <!-- Корти мухтасари чипта -->
        <div class="bg-slate-900/80 p-6 rounded-2xl border border-white/5 text-left space-y-4 mb-8">
            <div class="flex items-center justify-between pb-3 border-b border-white/5">
                <div>
                    <span class="text-xs text-slate-400 block">Рақами чипта:</span>
                    <span class="text-lg font-black font-mono text-cyan-400"><?= htmlspecialchars($ticket['ticket_number']) ?></span>
                </div>
                <span class="badge badge-emerald">
                    <i class="fa-solid fa-circle-check"></i> Пардохтшуда
                </span>
            </div>

            <div class="grid grid-cols-2 gap-4 text-xs">
                <div>
                    <span class="text-slate-500 block">Мусофир:</span>
                    <strong class="text-white text-sm"><?= htmlspecialchars($ticket['passenger_name']) ?></strong>
                </div>
                <div>
                    <span class="text-slate-500 block">Шиноснома:</span>
                    <strong class="text-white font-mono"><?= htmlspecialchars($ticket['passenger_passport']) ?></strong>
                </div>
                <div>
                    <span class="text-slate-500 block">Хатсайр:</span>
                    <strong class="text-white"><?= htmlspecialchars($ticket['route_name']) ?></strong>
                </div>
                <div>
                    <span class="text-slate-500 block">Вақти ҳаракат:</span>
                    <strong class="text-cyan-400 font-mono"><?= date('d.m.Y H:i', strtotime($ticket['departure_time'])) ?></strong>
                </div>
                <div>
                    <span class="text-slate-500 block">Вагон ва Ҷой:</span>
                    <strong class="text-emerald-400">Вагони №<?= $ticket['car_number'] ?>, Ҷойи №<?= $ticket['seat_number'] ?></strong>
                </div>
                <div>
                    <span class="text-slate-500 block">Маблағи пардохтшуда:</span>
                    <strong class="text-emerald-400 font-mono"><?= formatTJS($ticket['price']) ?></strong>
                </div>
            </div>
        </div>

        <!-- Тугмаҳои амалиёт -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="/train_ticket_system/ticket_print.php?ticket_id=<?= $ticket['id'] ?>" target="_blank" class="btn btn-emerald w-full sm:w-auto !py-3.5 !px-6">
                <i class="fa-solid fa-file-pdf"></i> Чоп кардан / Зеркашии PDF
            </a>
            <a href="/train_ticket_system/my_tickets.php" class="btn btn-secondary w-full sm:w-auto !py-3.5 !px-6">
                <i class="fa-solid fa-ticket"></i> Чиптаҳои ман
            </a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
