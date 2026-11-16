<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';
requireRole('cashier');

$pageTitle = 'Касса — Баргардонидани чипта';

$searchQuery = trim($_GET['search'] ?? '');
$searchedTicket = null;

if (!empty($searchQuery)) {
    $stmt = $pdo->prepare("
        SELECT tk.*, 
               t.departure_time, t.arrival_time,
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
        WHERE tk.ticket_number = ? OR tk.passenger_passport = ? OR tk.passenger_phone = ?
        ORDER BY tk.id DESC
        LIMIT 1
    ");
    $stmt->execute([$searchQuery, $searchQuery, $searchQuery]);
    $searchedTicket = $stmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    
    <div class="mb-8">
        <a href="/train_ticket_system/cashier/index.php" class="text-xs text-slate-400 hover:text-white transition inline-flex items-center gap-1 mb-2">
            <i class="fa-solid fa-arrow-left"></i> Бозгашт ба касса
        </a>
        <h1 class="text-2xl md:text-3xl font-extrabold text-white">Баргардонидани чипта дар касса</h1>
        <p class="text-xs text-slate-400 mt-1">Ҷустуҷӯ аз рӯи рақами чипта, рақами шиноснома ё телефони мусофир</p>
    </div>

    <!-- Формаи ҷустуҷӯ -->
    <div class="glass-panel p-6 mb-8 max-w-2xl border-white/10">
        <form method="GET" action="" class="flex gap-3">
            <div class="flex-grow">
                <input type="text" name="search" required value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Рақами чипта (масалан: TKT-2026-...) ё шиноснома..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-cyan-500 font-mono">
            </div>
            <button type="submit" class="btn btn-primary !py-3 !px-6 text-xs">
                <i class="fa-solid fa-magnifying-glass"></i> Ёфтан
            </button>
        </form>
    </div>

    <!-- Натиҷаи ёфтшуда -->
    <?php if (!empty($searchQuery)): ?>
        <?php if ($searchedTicket): ?>
            <div class="glass-panel p-6 max-w-2xl border-cyan-500/30">
                <div class="flex items-center justify-between pb-4 border-b border-white/5 mb-4">
                    <div>
                        <span class="text-xs text-slate-400 block">Рақами чипта:</span>
                        <span class="text-lg font-black font-mono text-cyan-400"><?= htmlspecialchars($searchedTicket['ticket_number']) ?></span>
                    </div>
                    <div>
                        <?php if ($searchedTicket['status'] === 'confirmed'): ?>
                            <span class="badge badge-emerald">Пардохтшуда (Фаъол)</span>
                        <?php elseif ($searchedTicket['status'] === 'refunded'): ?>
                            <span class="badge badge-ruby">Аллакай баргардонида шудааст</span>
                        <?php else: ?>
                            <span class="badge badge-amber"><?= $searchedTicket['status'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 text-xs mb-6">
                    <div>
                        <span class="text-slate-400 block">Мусофир:</span>
                        <strong class="text-white text-sm"><?= htmlspecialchars($searchedTicket['passenger_name']) ?></strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Шиноснома:</span>
                        <strong class="text-white font-mono"><?= htmlspecialchars($searchedTicket['passenger_passport']) ?></strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Хатсайр:</span>
                        <strong class="text-slate-200"><?= htmlspecialchars($searchedTicket['route_name']) ?></strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Вақти сафар:</span>
                        <strong class="text-slate-200"><?= date('d.m.Y H:i', strtotime($searchedTicket['departure_time'])) ?></strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Вагон ва Ҷой:</span>
                        <strong class="text-cyan-400">Вагони №<?= $searchedTicket['car_number'] ?>, Ҷойи №<?= $searchedTicket['seat_number'] ?></strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Маблағ барои баргардонидан:</span>
                        <strong class="text-emerald-400 font-mono text-base"><?= formatTJS($searchedTicket['price']) ?></strong>
                    </div>
                </div>

                <?php if ($searchedTicket['status'] === 'confirmed'): ?>
                    <form method="POST" action="/train_ticket_system/refund_ticket.php" onsubmit="return confirm('Оё мутмаин ҳастед, ки маблағро ба мизоҷ баргардонда, ҷойро холӣ мекунед?');">
                        <input type="hidden" name="ticket_id" value="<?= $searchedTicket['id'] ?>">
                        <button type="submit" class="btn btn-danger w-full py-3 text-sm font-bold">
                            <i class="fa-solid fa-rotate-left"></i> Тасдиқи баргардонидани чипта ва пардохти пул
                        </button>
                    </form>
                <?php else: ?>
                    <div class="p-3 bg-slate-800 rounded-xl text-center text-xs text-slate-400">
                        Ин чипта дар ҳолати фаъол нест ва баргардонида намешавад.
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="glass-panel p-8 text-center text-slate-400 max-w-2xl">
                <i class="fa-solid fa-circle-question text-3xl mb-2 text-amber-500"></i>
                <p class="text-sm">Чипта бо ин маълумот ёфт нашуд. Лутфан дурустии рақамҳоро санҷед.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
