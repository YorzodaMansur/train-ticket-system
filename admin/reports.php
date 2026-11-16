<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$pageTitle = 'Ҳисоботи фурӯш ва даромад';

$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Аз аввали моҳи ҷорӣ
$endDate   = $_GET['end_date'] ?? date('Y-m-d');     // То имрӯз

// Омори асосӣ дар ин муддат
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'confirmed' THEN 1 END) AS total_sold,
        COALESCE(SUM(CASE WHEN status = 'confirmed' THEN price ELSE 0 END), 0) AS gross_revenue,
        COUNT(CASE WHEN status = 'refunded' THEN 1 END) AS total_refunded,
        COALESCE(SUM(CASE WHEN status = 'refunded' THEN price ELSE 0 END), 0) AS refunded_amount
    FROM tickets
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$startDate, $endDate]);
$summary = $stmt->fetch();

$netRevenue = $summary['gross_revenue'] - $summary['refunded_amount'];

// Даромад аз рӯи хатсайрҳо
$routesStmt = $pdo->prepare("
    SELECT r.route_name, 
           COUNT(tk.id) AS tickets_count, 
           COALESCE(SUM(tk.price), 0) AS route_revenue
    FROM tickets tk
    JOIN trips t ON tk.trip_id = t.id
    JOIN routes r ON t.route_id = r.id
    WHERE tk.status = 'confirmed' AND DATE(tk.created_at) BETWEEN ? AND ?
    GROUP BY r.id, r.route_name
    ORDER BY route_revenue DESC
");
$routesStmt->execute([$startDate, $endDate]);
$routesStats = $routesStmt->fetchAll();

// Даромад аз рӯи намуди вагонҳо (купе, плацкарт, люкс)
$carsStmt = $pdo->prepare("
    SELECT c.car_type,
           COUNT(tk.id) AS tickets_count,
           COALESCE(SUM(tk.price), 0) AS car_revenue
    FROM tickets tk
    JOIN cars c ON tk.car_id = c.id
    WHERE tk.status = 'confirmed' AND DATE(tk.created_at) BETWEEN ? AND ?
    GROUP BY c.car_type
    ORDER BY car_revenue DESC
");
$carsStmt->execute([$startDate, $endDate]);
$carsStats = $carsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <a href="/train_ticket_system/admin/index.php" class="text-xs text-slate-400 hover:text-white transition inline-flex items-center gap-1 mb-2">
                <i class="fa-solid fa-arrow-left"></i> Ба панели асосӣ
            </a>
            <h1 class="text-2xl md:text-3xl font-extrabold text-white">Ҳисоботи фурӯш ва таҳлили даромад</h1>
            <p class="text-xs text-slate-400 mt-1">Нишондиҳандаҳои молиявии роҳи оҳан барои давраи муайяншуда</p>
        </div>

        <!-- Филтри сана -->
        <form method="GET" action="" class="flex items-center gap-2">
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-cyan-500">
            <span class="text-xs text-slate-400">—</span>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-cyan-500">
            <button type="submit" class="btn btn-primary text-xs !py-2 !px-4">
                <i class="fa-solid fa-filter"></i> Ҳисоб
            </button>
        </form>
    </div>

    <!-- Кортҳои нишондиҳандаҳо -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="glass-panel p-6 border-cyan-500/20 bg-cyan-950/20">
            <span class="text-xs uppercase font-mono font-bold text-cyan-400 block mb-1">Чиптаҳои фурӯхташуда</span>
            <span class="text-3xl font-black text-white font-mono"><?= $summary['total_sold'] ?></span>
            <span class="text-[11px] text-slate-400 block mt-1">Шумораи умумии мусофирон</span>
        </div>

        <div class="glass-panel p-6 border-emerald-500/20 bg-emerald-950/20">
            <span class="text-xs uppercase font-mono font-bold text-emerald-400 block mb-1">Даромади умумӣ (Брутто)</span>
            <span class="text-2xl font-black text-emerald-400 font-mono"><?= formatTJS($summary['gross_revenue']) ?></span>
            <span class="text-[11px] text-slate-400 block mt-1">Пеш аз баргардонидан</span>
        </div>

        <div class="glass-panel p-6 border-red-500/20 bg-red-950/20">
            <span class="text-xs uppercase font-mono font-bold text-red-400 block mb-1">Баргардонида шудааст</span>
            <span class="text-2xl font-black text-red-400 font-mono"><?= formatTJS($summary['refunded_amount']) ?></span>
            <span class="text-[11px] text-slate-400 block mt-1"><?= $summary['total_refunded'] ?> адад чипта</span>
        </div>

        <div class="glass-panel p-6 border-blue-500/20 bg-blue-950/20">
            <span class="text-xs uppercase font-mono font-bold text-blue-400 block mb-1">Даромади соф (Нетто)</span>
            <span class="text-2xl font-black text-white font-mono"><?= formatTJS($netRevenue) ?></span>
            <span class="text-[11px] text-slate-400 block mt-1">Фоидаи ниҳоӣ</span>
        </div>
    </div>

    <!-- Ҷадвалҳои таҳлилӣ -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        
        <!-- Даромад аз рӯи хатсайрҳо -->
        <div class="glass-panel p-6 border-white/10">
            <h3 class="text-base font-bold text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-route text-cyan-400"></i> Фурӯш аз рӯи хатсайрҳо
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/80 text-slate-400 uppercase font-mono border-b border-white/5">
                        <tr>
                            <th class="p-3">Хатсайр</th>
                            <th class="p-3">Чиптаҳо</th>
                            <th class="p-3 text-right">Маблағ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php if (empty($routesStats)): ?>
                            <tr><td colspan="3" class="p-4 text-center text-slate-500">Маълумот нест</td></tr>
                        <?php else: ?>
                            <?php foreach ($routesStats as $rs): ?>
                                <tr>
                                    <td class="p-3 font-semibold text-white"><?= htmlspecialchars($rs['route_name']) ?></td>
                                    <td class="p-3 font-mono"><?= $rs['tickets_count'] ?></td>
                                    <td class="p-3 text-right font-mono font-bold text-emerald-400"><?= formatTJS($rs['route_revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Даромад аз рӯи намуди вагонҳо -->
        <div class="glass-panel p-6 border-white/10">
            <h3 class="text-base font-bold text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-couch text-amber-400"></i> Фурӯш аз рӯи намуди вагон
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/80 text-slate-400 uppercase font-mono border-b border-white/5">
                        <tr>
                            <th class="p-3">Навъи вагон</th>
                            <th class="p-3">Чиптаҳо</th>
                            <th class="p-3 text-right">Маблағ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php if (empty($carsStats)): ?>
                            <tr><td colspan="3" class="p-4 text-center text-slate-500">Маълумот нест</td></tr>
                        <?php else: ?>
                            <?php foreach ($carsStats as $cs): ?>
                                <tr>
                                    <td class="p-3 font-semibold text-white"><?= carTypeName($cs['car_type']) ?></td>
                                    <td class="p-3 font-mono"><?= $cs['tickets_count'] ?></td>
                                    <td class="p-3 text-right font-mono font-bold text-emerald-400"><?= formatTJS($cs['car_revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Тугмаи чоп -->
    <div class="text-right">
        <button onclick="window.print()" class="btn btn-secondary text-xs !py-2.5 !px-5">
            <i class="fa-solid fa-print mr-1"></i> Чоп ё сабт ба PDF
        </button>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
