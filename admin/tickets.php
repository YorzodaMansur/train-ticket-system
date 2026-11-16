<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$pageTitle = 'Идораи Чиптаҳо';

$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT tk.*, 
           t.departure_time, 
           r.route_name, 
           tr.train_number, 
           c.car_number, c.car_type,
           s.seat_number, s.seat_type
    FROM tickets tk
    JOIN trips t ON tk.trip_id = t.id
    JOIN routes r ON t.route_id = r.id
    JOIN trains tr ON t.train_id = tr.id
    JOIN cars c ON tk.car_id = c.id
    JOIN seats s ON tk.seat_id = s.id
    WHERE 1=1
";

$params = [];

if (!empty($statusFilter)) {
    $sql .= " AND tk.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $sql .= " AND (tk.ticket_number LIKE ? OR tk.passenger_name LIKE ? OR tk.passenger_passport LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY tk.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <a href="/train_ticket_system/admin/index.php" class="text-xs text-slate-400 hover:text-white transition inline-flex items-center gap-1 mb-2">
                <i class="fa-solid fa-arrow-left"></i> Ба панели асосӣ
            </a>
            <h1 class="text-2xl md:text-3xl font-extrabold text-white">Рӯйхати ҳамаи чиптаҳо</h1>
            <p class="text-xs text-slate-400 mt-1">Назорат, ҷустуҷӯ ва баргардонидани чиптаҳои электронӣ</p>
        </div>
    </div>

    <!-- Филтр ва ҷустуҷӯ -->
    <div class="glass-panel p-6 border-white/10 mb-8">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-6">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Ҷустуҷӯ аз рӯи рақами чипта, номи мусофир ё шиноснома..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-xs focus:outline-none focus:border-cyan-500 font-mono">
            </div>

            <div class="md:col-span-4">
                <select name="status" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-xs focus:outline-none focus:border-cyan-500">
                    <option value="">Ҳамаи ҳолатҳо</option>
                    <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Пардохтшуда (Фаъол)</option>
                    <option value="reserved" <?= $statusFilter === 'reserved' ? 'selected' : '' ?>>Бандшуда (15 дақиқа)</option>
                    <option value="refunded" <?= $statusFilter === 'refunded' ? 'selected' : '' ?>>Баргардонидашуда</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Бекоршуда</option>
                </select>
            </div>

            <div class="md:col-span-2 flex gap-2">
                <button type="submit" class="btn btn-primary w-full text-xs !py-2.5">
                    <i class="fa-solid fa-filter"></i> Филтр
                </button>
                <a href="/train_ticket_system/admin/tickets.php" class="btn btn-secondary text-xs !py-2.5 !px-3" title="Тоза кардан">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Ҷадвали чиптаҳо -->
    <div class="glass-panel p-6 border-white/10">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-900/80 text-slate-400 uppercase font-mono border-b border-white/5">
                    <tr>
                        <th class="p-3">Рақами чипта</th>
                        <th class="p-3">Мусофир</th>
                        <th class="p-3">Шиноснома</th>
                        <th class="p-3">Хатсайр</th>
                        <th class="p-3">Вагон/Ҷой</th>
                        <th class="p-3">Маблағ</th>
                        <th class="p-3">Ҳолат</th>
                        <th class="p-3 text-right">Амал</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="8" class="p-6 text-center text-slate-500">Ягон чипта ёфт нашуд.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="p-3 font-mono font-bold text-cyan-400">
                                    <?= htmlspecialchars($t['ticket_number']) ?>
                                </td>
                                <td class="p-3 font-medium text-white"><?= htmlspecialchars($t['passenger_name']) ?></td>
                                <td class="p-3 font-mono text-slate-400"><?= htmlspecialchars($t['passenger_passport']) ?></td>
                                <td class="p-3"><?= htmlspecialchars($t['route_name']) ?></td>
                                <td class="p-3">В.<?= $t['car_number'] ?> / Ҷ.<?= $t['seat_number'] ?></td>
                                <td class="p-3 font-mono font-bold text-emerald-400"><?= formatTJS($t['price']) ?></td>
                                <td class="p-3">
                                    <?php if ($t['status'] === 'confirmed'): ?>
                                        <span class="badge badge-emerald">Пардохт</span>
                                    <?php elseif ($t['status'] === 'reserved'): ?>
                                        <span class="badge badge-amber">Банд</span>
                                    <?php elseif ($t['status'] === 'refunded'): ?>
                                        <span class="badge badge-ruby">Бозгашт</span>
                                    <?php else: ?>
                                        <span class="badge"><?= $t['status'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-right flex items-center justify-end gap-2">
                                    <a href="/train_ticket_system/ticket_print.php?ticket_id=<?= $t['id'] ?>" target="_blank" class="btn btn-secondary text-xs !py-1 !px-2" title="Чоп">
                                        <i class="fa-solid fa-print"></i>
                                    </a>

                                    <?php if ($t['status'] === 'confirmed'): ?>
                                        <form method="POST" action="/train_ticket_system/refund_ticket.php" onsubmit="return confirm('Оё ин чиптаро баргардонед?');">
                                            <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                            <button type="submit" class="btn btn-secondary text-xs !py-1 !px-2 text-red-400 border-red-500/20" title="Баргардонидан">
                                                <i class="fa-solid fa-rotate-left"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
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
