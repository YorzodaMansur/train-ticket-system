<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';
requireRole('cashier');

$pageTitle = 'Ҳисоботи кассавӣ';
$user = currentUser();

$reportDate = $_GET['date'] ?? date('Y-m-d');

// Омори фурӯш барои санаи интихобшуда
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'confirmed' THEN 1 END) AS total_sold,
        COALESCE(SUM(CASE WHEN status = 'confirmed' THEN price ELSE 0 END), 0) AS total_revenue,
        COUNT(CASE WHEN status = 'refunded' THEN 1 END) AS total_refunded,
        COALESCE(SUM(CASE WHEN status = 'refunded' THEN price ELSE 0 END), 0) AS refunded_amount
    FROM tickets
    WHERE DATE(created_at) = ?
");
$statsStmt->execute([$reportDate]);
$stats = $statsStmt->fetch();

// Рӯйхати муфассали чиптаҳои фурӯхташуда барои ин сана
$listStmt = $pdo->prepare("
    SELECT tk.*, r.route_name, tr.train_number, c.car_number, s.seat_number
    FROM tickets tk
    JOIN trips t ON tk.trip_id = t.id
    JOIN routes r ON t.route_id = r.id
    JOIN trains tr ON t.train_id = tr.id
    JOIN cars c ON tk.car_id = c.id
    JOIN seats s ON tk.seat_id = s.id
    WHERE DATE(tk.created_at) = ?
    ORDER BY tk.id DESC
");
$listStmt->execute([$reportDate]);
$tickets = $listStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <a href="/train_ticket_system/cashier/index.php" class="text-xs text-slate-400 hover:text-white transition inline-flex items-center gap-1 mb-2">
                <i class="fa-solid fa-arrow-left"></i> Бозгашт ба касса
            </a>
            <h1 class="text-2xl md:text-3xl font-extrabold text-white">Ҳисоботи кассавии рӯзона</h1>
            <p class="text-xs text-slate-400 mt-1">Омори фурӯш ва баргардонидани чиптаҳо</p>
        </div>

        <!-- Интихоби сана -->
        <form method="GET" action="" class="flex items-center gap-2">
            <input type="date" name="date" value="<?= htmlspecialchars($reportDate) ?>" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-cyan-500">
            <button type="submit" class="btn btn-secondary text-xs !py-2 !px-3">
                <i class="fa-solid fa-filter"></i> Интихоб
            </button>
        </form>
    </div>

    <!-- Карточкаҳои оморӣ -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="glass-panel p-5 border-white/5">
            <span class="text-xs text-slate-400 uppercase font-mono block mb-1">Чиптаҳои фурӯхташуда:</span>
            <span class="text-3xl font-black text-white font-mono"><?= $stats['total_sold'] ?></span>
        </div>

        <div class="glass-panel p-5 border-emerald-500/20 bg-emerald-950/20">
            <span class="text-xs text-emerald-400 uppercase font-mono block mb-1">Маблағи умумии воридот:</span>
            <span class="text-2xl font-black text-emerald-400 font-mono"><?= formatTJS($stats['total_revenue']) ?></span>
        </div>

        <div class="glass-panel p-5 border-red-500/20 bg-red-950/20">
            <span class="text-xs text-red-400 uppercase font-mono block mb-1">Чиптаҳои баргардонидашуда:</span>
            <span class="text-3xl font-black text-red-400 font-mono"><?= $stats['total_refunded'] ?></span>
        </div>

        <div class="glass-panel p-5 border-white/5">
            <span class="text-xs text-slate-400 uppercase font-mono block mb-1">Маблағи бозпас додашуда:</span>
            <span class="text-2xl font-black text-slate-300 font-mono"><?= formatTJS($stats['refunded_amount']) ?></span>
        </div>
    </div>

    <!-- Ҷадвали муфассал -->
    <div class="glass-panel p-6 border-white/10">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold text-white">Муомилоти молиявӣ дар таърихи <?= date('d.m.Y', strtotime($reportDate)) ?></h2>
            <button onclick="window.print()" class="btn btn-secondary text-xs !py-1.5 !px-3">
                <i class="fa-solid fa-print"></i> Чопи ҳисобот
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-900/80 text-slate-400 uppercase font-mono border-b border-white/5">
                    <tr>
                        <th class="p-3">Вақт</th>
                        <th class="p-3">Рақами чипта</th>
                        <th class="p-3">Мусофир</th>
                        <th class="p-3">Хатсайр</th>
                        <th class="p-3">Вагон/Ҷой</th>
                        <th class="p-3">Пардохт</th>
                        <th class="p-3">Ҳолат</th>
                        <th class="p-3 text-right">Маблағ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="8" class="p-6 text-center text-slate-500">Дар ин сана ягон амалиёт сабт нашудааст.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="p-3 font-mono text-slate-400"><?= date('H:i', strtotime($t['created_at'])) ?></td>
                                <td class="p-3 font-mono font-bold text-cyan-400"><?= htmlspecialchars($t['ticket_number']) ?></td>
                                <td class="p-3 text-white font-medium"><?= htmlspecialchars($t['passenger_name']) ?></td>
                                <td class="p-3"><?= htmlspecialchars($t['route_name']) ?></td>
                                <td class="p-3">В.<?= $t['car_number'] ?> (Ҷ.<?= $t['seat_number'] ?>)</td>
                                <td class="p-3 uppercase text-[10px] text-slate-400"><?= htmlspecialchars($t['payment_method']) ?></td>
                                <td class="p-3">
                                    <?php if ($t['status'] === 'confirmed'): ?>
                                        <span class="badge badge-emerald">Пардохт</span>
                                    <?php elseif ($t['status'] === 'refunded'): ?>
                                        <span class="badge badge-ruby">Бозгашт</span>
                                    <?php else: ?>
                                        <span class="badge"><?= $t['status'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-right font-mono font-bold <?= $t['status'] === 'confirmed' ? 'text-emerald-400' : 'text-slate-400 line-through' ?>">
                                    <?= formatTJS($t['price']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
