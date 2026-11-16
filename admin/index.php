<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$pageTitle = 'Панели Маъмур — Роҳи оҳан';

// Омори умумии система
$stats = [];

// 1. Маблағи умумии фурӯш
$revStmt = $pdo->query("SELECT COALESCE(SUM(price), 0) FROM tickets WHERE status = 'confirmed'");
$stats['total_revenue'] = $revStmt->fetchColumn();

// 2. Шумораи умумии чиптаҳои фурӯхташуда
$tktStmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'confirmed'");
$stats['total_tickets'] = $tktStmt->fetchColumn();

// 3. Қатнавҳои фаъол
$tripsStmt = $pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'scheduled' AND departure_time >= NOW()");
$stats['active_trips'] = $tripsStmt->fetchColumn();

// 4. Шумораи мусофирони сабтшуда
$usersStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'passenger'");
$stats['total_passengers'] = $usersStmt->fetchColumn();

// Фармоишҳои охирин
$recentStmt = $pdo->query("
    SELECT tk.*, r.route_name, tr.train_number, c.car_number, s.seat_number
    FROM tickets tk
    JOIN trips t ON tk.trip_id = t.id
    JOIN routes r ON t.route_id = r.id
    JOIN trains tr ON t.train_id = tr.id
    JOIN cars c ON tk.car_id = c.id
    JOIN seats s ON tk.seat_id = s.id
    ORDER BY tk.id DESC
    LIMIT 6
");
$recentTickets = $recentStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    
    <!-- Сарлавҳаи панели маъмурият -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="badge badge-primary"><i class="fa-solid fa-shield-halved"></i> Панели Идоракунии Марказӣ</span>
                <span class="text-xs text-slate-400 font-mono">Системаи 2026-2027</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-white">Идораи системаи роҳи оҳан</h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="/train_ticket_system/admin/trips.php?action=create" class="btn btn-primary text-xs !py-2.5">
                <i class="fa-solid fa-plus"></i> Қатнави нав
            </a>
            <a href="/train_ticket_system/admin/reports.php" class="btn btn-secondary text-xs !py-2.5">
                <i class="fa-solid fa-chart-pie"></i> Ҳисоботи молиявӣ
            </a>
        </div>
    </div>

    <!-- Менюи зудгузар ба бахшҳои маъмурӣ -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
        <a href="/train_ticket_system/admin/stations.php" class="glass-panel p-4 text-center hover:border-cyan-500/40 transition">
            <i class="fa-solid fa-city text-cyan-400 text-xl mb-1.5 block"></i>
            <span class="text-xs font-bold text-white block">Истгоҳҳо</span>
            <span class="text-[10px] text-slate-400">Шаҳрҳо ва вокзалҳо</span>
        </a>

        <a href="/train_ticket_system/admin/routes.php" class="glass-panel p-4 text-center hover:border-cyan-500/40 transition">
            <i class="fa-solid fa-route text-emerald-400 text-xl mb-1.5 block"></i>
            <span class="text-xs font-bold text-white block">Хатсайрҳо</span>
            <span class="text-[10px] text-slate-400">Самт ва масофаҳо</span>
        </a>

        <a href="/train_ticket_system/admin/trains.php" class="glass-panel p-4 text-center hover:border-cyan-500/40 transition">
            <i class="fa-solid fa-train text-amber-400 text-xl mb-1.5 block"></i>
            <span class="text-xs font-bold text-white block">Қатора ва вагонҳо</span>
            <span class="text-[10px] text-slate-400">Купе, плацкарт, ҷой</span>
        </a>

        <a href="/train_ticket_system/admin/trips.php" class="glass-panel p-4 text-center hover:border-cyan-500/40 transition">
            <i class="fa-solid fa-calendar-days text-purple-400 text-xl mb-1.5 block"></i>
            <span class="text-xs font-bold text-white block">Қатнавҳо</span>
            <span class="text-[10px] text-slate-400">Ҷадвали ҳаракат</span>
        </a>

        <a href="/train_ticket_system/admin/tickets.php" class="glass-panel p-4 text-center hover:border-cyan-500/40 transition">
            <i class="fa-solid fa-ticket text-rose-400 text-xl mb-1.5 block"></i>
            <span class="text-xs font-bold text-white block">Чиптаҳо</span>
            <span class="text-[10px] text-slate-400">Фурӯш ва бандкунӣ</span>
        </a>

        <a href="/train_ticket_system/admin/reports.php" class="glass-panel p-4 text-center hover:border-cyan-500/40 transition">
            <i class="fa-solid fa-file-invoice-dollar text-blue-400 text-xl mb-1.5 block"></i>
            <span class="text-xs font-bold text-white block">Ҳисобот</span>
            <span class="text-[10px] text-slate-400">Омори даромад</span>
        </a>
    </div>

    <!-- Кортҳои нишондиҳандаҳои оморӣ (KPIs) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <div class="glass-panel p-6 border-emerald-500/20 bg-emerald-950/20 relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs uppercase font-mono font-bold text-emerald-400">Даромади умумӣ</span>
                <i class="fa-solid fa-wallet text-emerald-400 text-lg"></i>
            </div>
            <div class="text-3xl font-black text-white font-mono"><?= formatTJS($stats['total_revenue']) ?></div>
            <span class="text-[11px] text-slate-400 mt-2 block">Аз чиптаҳои тасдиқшуда</span>
        </div>

        <div class="glass-panel p-6 border-cyan-500/20 bg-cyan-950/20">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs uppercase font-mono font-bold text-cyan-400">Чиптаҳои фурӯхташуда</span>
                <i class="fa-solid fa-ticket text-cyan-400 text-lg"></i>
            </div>
            <div class="text-3xl font-black text-white font-mono"><?= $stats['total_tickets'] ?></div>
            <span class="text-[11px] text-slate-400 mt-2 block">Мусофирони интиқолшуда</span>
        </div>

        <div class="glass-panel p-6 border-amber-500/20 bg-amber-950/20">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs uppercase font-mono font-bold text-amber-400">Қатнавҳои фаъол</span>
                <i class="fa-solid fa-clock text-amber-400 text-lg"></i>
            </div>
            <div class="text-3xl font-black text-white font-mono"><?= $stats['active_trips'] ?></div>
            <span class="text-[11px] text-slate-400 mt-2 block">Дар ҷадвали ҳаракат</span>
        </div>

        <div class="glass-panel p-6 border-purple-500/20 bg-purple-950/20">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs uppercase font-mono font-bold text-purple-400">Мусофирони сабтшуда</span>
                <i class="fa-solid fa-users text-purple-400 text-lg"></i>
            </div>
            <div class="text-3xl font-black text-white font-mono"><?= $stats['total_passengers'] ?></div>
            <span class="text-[11px] text-slate-400 mt-2 block">Корбарони фаъол</span>
        </div>

    </div>

    <!-- Чиптаҳои охирини система -->
    <div class="glass-panel p-6 border-white/10">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-cyan-400"></i> Фурӯшҳо ва фармоишҳои охирин
            </h2>
            <a href="/train_ticket_system/admin/tickets.php" class="text-xs text-cyan-400 hover:text-cyan-300">
                Ҳамаи чиптаҳо <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-900/80 text-slate-400 uppercase font-mono border-b border-white/5">
                    <tr>
                        <th class="p-3">Рақами чипта</th>
                        <th class="p-3">Мусофир</th>
                        <th class="p-3">Қатора ва Хатсайр</th>
                        <th class="p-3">Вагон / Ҷой</th>
                        <th class="p-3">Нарх</th>
                        <th class="p-3">Ҳолат</th>
                        <th class="p-3 text-right">Чоп</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($recentTickets as $rt): ?>
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="p-3 font-mono font-bold text-cyan-400"><?= htmlspecialchars($rt['ticket_number']) ?></td>
                            <td class="p-3 font-medium text-white"><?= htmlspecialchars($rt['passenger_name']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($rt['route_name']) ?> (№<?= $rt['train_number'] ?>)</td>
                            <td class="p-3">В.<?= $rt['car_number'] ?> / Ҷ.<?= $rt['seat_number'] ?></td>
                            <td class="p-3 font-mono font-bold text-emerald-400"><?= formatTJS($rt['price']) ?></td>
                            <td class="p-3">
                                <?php if ($rt['status'] === 'confirmed'): ?>
                                    <span class="badge badge-emerald">Пардохт</span>
                                <?php elseif ($rt['status'] === 'reserved'): ?>
                                    <span class="badge badge-amber">Банд (15 дақ)</span>
                                <?php elseif ($rt['status'] === 'refunded'): ?>
                                    <span class="badge badge-ruby">Бозгашт</span>
                                <?php else: ?>
                                    <span class="badge"><?= $rt['status'] ?></span>
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
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
