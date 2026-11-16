<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';
requireRole('cashier');

$pageTitle = 'Кассаи роҳи оҳан — Фурӯши чипта';
$user = currentUser();

// Гирифтани ҳамаи қатнавҳои фаъол
$tripsStmt = $pdo->query("
    SELECT t.*, r.route_name, tr.train_number, tr.train_name,
           s1.city AS from_city, s2.city AS to_city
    FROM trips t
    JOIN routes r ON t.route_id = r.id
    JOIN trains tr ON t.train_id = tr.id
    JOIN stations s1 ON r.from_station_id = s1.id
    JOIN stations s2 ON r.to_station_id = s2.id
    WHERE t.status = 'scheduled' AND t.departure_time >= NOW()
    ORDER BY t.departure_time ASC
");
$trips = $tripsStmt->fetchAll();

// Чиптаҳои имрӯз аз ҷониби ин кассир фурӯхташуда
$todaySalesStmt = $pdo->prepare("
    SELECT tk.*, r.route_name, tr.train_number, c.car_number, s.seat_number
    FROM tickets tk
    JOIN trips t ON tk.trip_id = t.id
    JOIN routes r ON t.route_id = r.id
    JOIN trains tr ON t.train_id = tr.id
    JOIN cars c ON tk.car_id = c.id
    JOIN seats s ON tk.seat_id = s.id
    WHERE (tk.cashier_id = ? OR tk.cashier_id IS NULL)
      AND DATE(tk.created_at) = CURRENT_DATE
    ORDER BY tk.id DESC
    LIMIT 10
");
$todaySalesStmt->execute([$user['id']]);
$recentTickets = $todaySalesStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    
    <!-- Сарлавҳаи панели кассир -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="badge badge-amber"><i class="fa-solid fa-cash-register"></i> Бахши Кассир</span>
                <span class="text-xs text-slate-400 font-mono"><?= htmlspecialchars($user['full_name']) ?></span>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-white">Кассаи фурӯши чиптаҳои роҳи оҳан</h1>
        </div>

        <div class="flex items-center gap-3">
            <a href="/train_ticket_system/cashier/refund.php" class="btn btn-secondary text-xs !py-2.5 text-red-400 border-red-500/20">
                <i class="fa-solid fa-rotate-left"></i> Баргардонидани чипта
            </a>
            <a href="/train_ticket_system/cashier/reports.php" class="btn btn-secondary text-xs !py-2.5 text-cyan-400 border-cyan-500/20">
                <i class="fa-solid fa-chart-line"></i> Ҳисоботи баст (смена)
            </a>
        </div>
    </div>

    <!-- Қатнавҳо барои фурӯши фаврӣ -->
    <div class="glass-panel p-6 mb-8 border-white/10">
        <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <i class="fa-solid fa-ticket text-cyan-400"></i> Фурӯши чипта барои қатнавҳо
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-900/80 text-slate-400 uppercase font-mono border-b border-white/5">
                    <tr>
                        <th class="p-3">Қатора</th>
                        <th class="p-3">Хатсайр</th>
                        <th class="p-3">Вақти ҳаракат</th>
                        <th class="p-3">Вақти расидан</th>
                        <th class="p-3">Нархи асосӣ</th>
                        <th class="p-3 text-right">Амал</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 font-sans">
                    <?php foreach ($trips as $trip): ?>
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="p-3 font-bold text-white">
                                <span class="badge badge-primary">№ <?= htmlspecialchars($trip['train_number']) ?></span>
                            </td>
                            <td class="p-3 font-semibold text-slate-200">
                                <?= htmlspecialchars($trip['route_name']) ?>
                            </td>
                            <td class="p-3 font-mono">
                                <?= date('d.m.Y — H:i', strtotime($trip['departure_time'])) ?>
                            </td>
                            <td class="p-3 font-mono">
                                <?= date('d.m.Y — H:i', strtotime($trip['arrival_time'])) ?>
                            </td>
                            <td class="p-3 font-mono font-bold text-emerald-400">
                                <?= formatTJS($trip['base_price']) ?>
                            </td>
                            <td class="p-3 text-right">
                                <a href="/train_ticket_system/select_seat.php?trip_id=<?= $trip['id'] ?>" class="btn btn-primary text-xs !py-1.5 !px-3">
                                    <i class="fa-solid fa-chair"></i> Интихоби ҷой ва фурӯш
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Чиптаҳои охирини фурӯхташуда -->
    <div class="glass-panel p-6 border-white/10">
        <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-amber-400"></i> Чиптаҳои имрӯз фурӯхташуда дар касса
        </h2>

        <?php if (empty($recentTickets)): ?>
            <p class="text-xs text-slate-500 py-4 text-center">Имрӯз то ҳол чипта фурӯхта нашудааст.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/80 text-slate-400 uppercase font-mono border-b border-white/5">
                        <tr>
                            <th class="p-3">Рақами чипта</th>
                            <th class="p-3">Мусофир</th>
                            <th class="p-3">Хатсайр</th>
                            <th class="p-3">Вагон / Ҷой</th>
                            <th class="p-3">Маблағ</th>
                            <th class="p-3">Ҳолат</th>
                            <th class="p-3 text-right">Чоп</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($recentTickets as $rt): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="p-3 font-mono font-bold text-cyan-400">
                                    <?= htmlspecialchars($rt['ticket_number']) ?>
                                </td>
                                <td class="p-3">
                                    <strong class="text-white"><?= htmlspecialchars($rt['passenger_name']) ?></strong>
                                    <span class="block text-[10px] text-slate-400"><?= htmlspecialchars($rt['passenger_passport']) ?></span>
                                </td>
                                <td class="p-3 font-semibold"><?= htmlspecialchars($rt['route_name']) ?></td>
                                <td class="p-3">Ваг. №<?= $rt['car_number'] ?> (Ҷойи <?= $rt['seat_number'] ?>)</td>
                                <td class="p-3 font-mono font-bold text-emerald-400"><?= formatTJS($rt['price']) ?></td>
                                <td class="p-3">
                                    <?php if ($rt['status'] === 'confirmed'): ?>
                                        <span class="badge badge-emerald">Пардохтшуда</span>
                                    <?php elseif ($rt['status'] === 'refunded'): ?>
                                        <span class="badge badge-ruby">Бозгардонидашуда</span>
                                    <?php else: ?>
                                        <span class="badge badge-amber"><?= $rt['status'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-right">
                                    <a href="/train_ticket_system/ticket_print.php?ticket_id=<?= $rt['id'] ?>" target="_blank" class="btn btn-secondary text-xs !py-1 !px-2.5">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
