<?php
define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Чиптаҳои ман';

if (!isLoggedIn()) {
    setFlash('error', 'Барои дидани чиптаҳо аввал ба система ворид шавед.');
    header('Location: /train_ticket_system/login.php');
    exit;
}

$user = currentUser();

// Гирифтани ҳамаи чиптаҳои ин мусофир
$stmt = $pdo->prepare("
    SELECT tk.*, 
           t.departure_time, t.arrival_time,
           r.route_name,
           s1.city AS from_city, s2.city AS to_city,
           tr.train_number, tr.train_name,
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
    WHERE tk.passenger_id = ?
    ORDER BY tk.id DESC
");
$stmt->execute([$user['id']]);
$tickets = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-white">Чиптаҳои харидашудаи ман</h1>
            <p class="text-xs text-slate-400 mt-1">Таърихи сафарҳо, чопи чиптаҳои электронӣ ва баргардонидани онҳо</p>
        </div>
        <a href="/train_ticket_system/trips.php" class="btn btn-primary text-xs !py-2.5 !px-4">
            <i class="fa-solid fa-plus"></i> Сафари нав
        </a>
    </div>

    <?php if (empty($tickets)): ?>
        <div class="glass-panel p-12 text-center text-slate-400">
            <div class="w-16 h-16 rounded-full bg-slate-800 flex items-center justify-center text-2xl text-slate-500 mx-auto mb-4">
                <i class="fa-solid fa-ticket-simple"></i>
            </div>
            <h3 class="text-lg font-bold text-white mb-2">Шумо то ҳол чипта нахаридаед</h3>
            <p class="text-xs text-slate-400 max-w-md mx-auto mb-6">
                Хатсайри дилхоҳатонро интихоб кунед ва дар чанд сония соҳиби чиптаи қатораи байнишаҳрӣ шавед.
            </p>
            <a href="/train_ticket_system/trips.php" class="btn btn-primary text-xs !py-3 !px-6">
                Ҷустуҷӯи қатнавҳо
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($tickets as $t): ?>
                <div class="glass-panel p-6 border-white/5 hover:border-cyan-500/30 transition">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                        
                        <!-- Сутуни 1: Рақами чипта ва ҳолат -->
                        <div class="lg:col-span-3">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="font-mono text-sm font-bold text-cyan-400"><?= htmlspecialchars($t['ticket_number']) ?></span>
                            </div>
                            
                            <?php if ($t['status'] === 'confirmed'): ?>
                                <span class="badge badge-emerald"><i class="fa-solid fa-check"></i> Фаъол / Тасдиқшуда</span>
                            <?php elseif ($t['status'] === 'refunded'): ?>
                                <span class="badge badge-ruby"><i class="fa-solid fa-rotate-left"></i> Баргардонидашуда</span>
                            <?php elseif ($t['status'] === 'reserved'): ?>
                                <span class="badge badge-amber"><i class="fa-solid fa-clock"></i> Мунтазири пардохт</span>
                            <?php else: ?>
                                <span class="badge"><i class="fa-solid fa-ban"></i> Бекоршуда</span>
                            <?php endif; ?>

                            <div class="text-[11px] text-slate-500 mt-3">
                                Харидорӣ: <?= date('d.m.Y H:i', strtotime($t['created_at'])) ?>
                            </div>
                        </div>

                        <!-- Сутуни 2: Хатсайр ва вақти сафар -->
                        <div class="lg:col-span-5">
                            <h3 class="text-base font-bold text-white mb-2"><?= htmlspecialchars($t['route_name']) ?></h3>
                            <div class="bg-slate-900/60 p-3 rounded-xl border border-white/5 flex items-center justify-between text-xs">
                                <div>
                                    <span class="text-slate-400 block text-[10px]">Ҳаракат:</span>
                                    <strong class="text-white font-mono text-sm"><?= date('H:i', strtotime($t['departure_time'])) ?></strong>
                                    <span class="text-slate-400 block"><?= htmlspecialchars($t['from_city']) ?></span>
                                    <span class="text-[10px] text-slate-500"><?= date('d.m.Y', strtotime($t['departure_time'])) ?></span>
                                </div>
                                <div class="text-slate-600 px-2">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </div>
                                <div class="text-right">
                                    <span class="text-slate-400 block text-[10px]">Расидан:</span>
                                    <strong class="text-white font-mono text-sm"><?= date('H:i', strtotime($t['arrival_time'])) ?></strong>
                                    <span class="text-slate-400 block"><?= htmlspecialchars($t['to_city']) ?></span>
                                    <span class="text-[10px] text-slate-500"><?= date('d.m.Y', strtotime($t['arrival_time'])) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Сутуни 3: Ҷой ва нарх -->
                        <div class="lg:col-span-2 text-center lg:text-left">
                            <div class="text-xs text-slate-400">Вагон ва Ҷой:</div>
                            <div class="text-white font-bold text-sm">
                                Вагони №<?= $t['car_number'] ?> 
                                <span class="text-cyan-400 font-mono">(Ҷойи №<?= $t['seat_number'] ?>)</span>
                            </div>
                            <div class="text-xs text-slate-400"><?= carTypeName($t['car_type']) ?></div>
                            <div class="text-base font-black text-emerald-400 font-mono mt-1">
                                <?= formatTJS($t['price']) ?>
                            </div>
                        </div>

                        <!-- Сутуни 4: Амалҳо (Чоп / Баргардонидан) -->
                        <div class="lg:col-span-2 flex flex-col gap-2">
                            <?php if ($t['status'] === 'confirmed'): ?>
                                <a href="/train_ticket_system/ticket_print.php?ticket_id=<?= $t['id'] ?>" target="_blank" class="btn btn-emerald text-xs !py-2 text-center flex items-center justify-center gap-1.5">
                                    <i class="fa-solid fa-print"></i> Чопи чипта (PDF)
                                </a>

                                <form method="POST" action="/train_ticket_system/refund_ticket.php" onsubmit="return confirm('Оё мутмаин ҳастед, ки ин чиптаро бармегардонед? Маблағ баргардонида шуда, ҷой озод мегардад.');">
                                    <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-secondary text-xs !py-1.5 w-full text-red-400 hover:text-red-300 border-red-500/20">
                                        <i class="fa-solid fa-rotate-left"></i> Баргардонидан
                                    </button>
                                </form>
                            <?php elseif ($t['status'] === 'reserved'): ?>
                                <a href="/train_ticket_system/checkout.php?ticket_id=<?= $t['id'] ?>" class="btn btn-primary text-xs !py-2 text-center">
                                    Пардохти 15-дақиқа
                                </a>
                            <?php else: ?>
                                <span class="text-xs text-slate-500 text-center block py-1">Амал дастнорас</span>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
