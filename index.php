<?php
define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Фурӯши онлайни чиптаҳои қатораҳои байнишаҳрӣ';

// Гирифтани рӯйхати истгоҳҳо барои филтри ҷустуҷӯ
$stationsStmt = $pdo->query("SELECT * FROM stations WHERE is_active = 1 ORDER BY city ASC");
$stations = $stationsStmt->fetchAll();

// Қатнавҳои фаъол барои имрӯз ва рӯзҳои наздик
$tripsQuery = "
    SELECT t.*, 
           r.route_name, r.duration_hours, r.distance_km,
           s1.name AS from_station, s1.city AS from_city,
           s2.name AS to_station, s2.city AS to_city,
           tr.train_number, tr.train_name, tr.train_type
    FROM trips t
    JOIN routes r ON t.route_id = r.id
    JOIN stations s1 ON r.from_station_id = s1.id
    JOIN stations s2 ON r.to_station_id = s2.id
    JOIN trains tr ON t.train_id = tr.id
    WHERE t.status = 'scheduled' AND t.departure_time >= NOW()
    ORDER BY t.departure_time ASC
    LIMIT 6
";
$tripsStmt = $pdo->query($tripsQuery);
$upcomingTrips = $tripsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section бо майдони ҷустуҷӯи чиптаҳо -->
<section class="relative py-16 md:py-24 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-cyan-500/10 via-transparent to-transparent pointer-events-none"></div>

    <div class="container mx-auto px-4 relative z-10">
        
        <div class="max-w-3xl mx-auto text-center mb-10">
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 mb-4 animate-pulse">
                <i class="fa-solid fa-bolt"></i> Технологияи насли нави фурӯши чиптаҳо 2026-2027
            </span>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight mb-4">
                Сафари бароҳат бо <span class="bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">Қатораҳои Байнишаҳрӣ</span>
            </h1>
            <p class="text-slate-400 text-base md:text-lg max-w-2xl mx-auto">
                Ҷойи дилхоҳатонро дар нақшаи вагон онлайн интихоб кунед. Ҷой ба муддати 15 дақиқа то пардохт барои Шумо банд нигоҳ дошта мешавад.
            </p>
        </div>

        <!-- Формаи ҷустуҷӯи қатнавҳо (Search Box) -->
        <div class="max-w-4xl mx-auto glass-panel p-6 md:p-8 border-cyan-500/20 shadow-2xl shadow-cyan-500/10">
            <form action="/train_ticket_system/trips.php" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                
                <!-- Аз куҷо -->
                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        <i class="fa-solid fa-location-dot text-cyan-400 mr-1"></i> Аз куҷо (Истгоҳ)
                    </label>
                    <select name="from" required class="w-full bg-slate-900/90 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
                        <option value="">Интихоби шаҳр/истгоҳ...</option>
                        <?php foreach ($stations as $st): ?>
                            <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['city']) ?> (<?= htmlspecialchars($st['name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Ба куҷо -->
                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        <i class="fa-solid fa-flag-checkered text-emerald-400 mr-1"></i> Ба куҷо (Истгоҳ)
                    </label>
                    <select name="to" required class="w-full bg-slate-900/90 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
                        <option value="">Интихоби шаҳр/истгоҳ...</option>
                        <?php foreach ($stations as $st): ?>
                            <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['city']) ?> (<?= htmlspecialchars($st['name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Санаи сафар -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        <i class="fa-solid fa-calendar-day text-amber-400 mr-1"></i> Сана
                    </label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" class="w-full bg-slate-900/90 border border-slate-700 rounded-xl px-3 py-3 text-white text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
                </div>

                <!-- Тугмаи Ҷустуҷӯ -->
                <div class="md:col-span-2">
                    <button type="submit" class="btn btn-primary w-full py-3.5 !rounded-xl text-center flex items-center justify-center gap-2">
                        <i class="fa-solid fa-magnifying-glass"></i> Ҷустуҷӯ
                    </button>
                </div>

            </form>

            <!-- Самтҳои зудпайванд -->
            <div class="mt-4 pt-4 border-t border-white/5 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                <span class="font-semibold text-slate-300">Хатсайрҳои машҳур:</span>
                <a href="/train_ticket_system/trips.php?from=1&to=2" class="px-2.5 py-1 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-cyan-400 transition">Душанбе → Хуҷанд</a>
                <a href="/train_ticket_system/trips.php?from=2&to=1" class="px-2.5 py-1 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-cyan-400 transition">Хуҷанд → Душанбе</a>
                <a href="/train_ticket_system/trips.php?from=1&to=3" class="px-2.5 py-1 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-cyan-400 transition">Душанбе → Бохтар</a>
                <a href="/train_ticket_system/trips.php?from=1&to=4" class="px-2.5 py-1 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-cyan-400 transition">Душанбе → Кӯлоб</a>
            </div>
        </div>

    </div>
</section>

<!-- Бартариятҳои асосии система -->
<section class="py-12 border-y border-white/5 bg-slate-950/40">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            
            <div class="glass-panel p-6 border-white/5">
                <div class="w-12 h-12 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center text-2xl mb-4">
                    <i class="fa-solid fa-couch"></i>
                </div>
                <h3 class="font-bold text-white text-base mb-1">Интихоби ҷой дар вагон</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Нақшаи мукаммали графикии вагонҳо (СВ, Купе, Плацкарт) бо нишон додани ҷойҳои холӣ ва фурӯхташуда.
                </p>
            </div>

            <div class="glass-panel p-6 border-white/5">
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-2xl mb-4">
                    <i class="fa-solid fa-stopwatch"></i>
                </div>
                <h3 class="font-bold text-white text-base mb-1">Бандкунии 15 дақиқа</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Ҷойи интихобкарда ба муддати 15 дақиқа барои Шумо маҳфуз мемонад ва ба корбарони дигар нишон дода намешавад.
                </p>
            </div>

            <div class="glass-panel p-6 border-white/5">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-2xl mb-4">
                    <i class="fa-solid fa-file-pdf"></i>
                </div>
                <h3 class="font-bold text-white text-base mb-1">Чиптаи расмӣ бо PDF</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Пас аз пардохт чиптаи электронӣ бо QR-код ва штрих-код дастрас шуда, фавран чоп ё зеркашӣ мешавад.
                </p>
            </div>

            <div class="glass-panel p-6 border-white/5">
                <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-2xl mb-4">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>
                <h3 class="font-bold text-white text-base mb-1">Баргардонидани осон</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Агар нақшаи сафари шумо тағйир ёбад, чиптаро дар як пахши тугма бе навбатпоӣ бармегардонед.
                </p>
            </div>

        </div>
    </div>
</section>

<!-- Қатнавҳои наздиктарин (Upcoming Trips) -->
<section class="py-16">
    <div class="container mx-auto px-4">
        
        <div class="flex items-center justify-between mb-8">
            <div>
                <span class="text-xs text-cyan-400 uppercase font-mono tracking-wider font-semibold">Ҷадвали фаврии қатораҳо</span>
                <h2 class="text-2xl md:text-3xl font-bold text-white">Қатнавҳои дастраси имрӯза</h2>
            </div>
            <a href="/train_ticket_system/trips.php" class="btn btn-secondary text-xs !py-2 !px-4">
                Ҳамаи қатнавҳо <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
        </div>

        <?php if (empty($upcomingTrips)): ?>
            <div class="glass-panel p-8 text-center text-slate-400">
                <i class="fa-solid fa-calendar-xmark text-4xl mb-3 text-slate-600"></i>
                <p>Дар айни замон қатнавҳои фаъол ёфт нашуданд. Лутфан, дертар санҷед ё хатсайри дигарро интихоб кунед.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($upcomingTrips as $trip): ?>
                    <div class="glass-panel p-6 flex flex-col justify-between hover:border-cyan-500/40 transition group">
                        <div>
                            <!-- Қатора ва вақт -->
                            <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/5">
                                <span class="badge badge-primary">
                                    <i class="fa-solid fa-train"></i> <?= htmlspecialchars($trip['train_number']) ?>
                                </span>
                                <span class="text-xs text-slate-400 font-mono">
                                    <?= date('d.m.Y', strtotime($trip['departure_time'])) ?>
                                </span>
                            </div>

                            <h3 class="text-lg font-bold text-white group-hover:text-cyan-400 transition mb-3">
                                <?= htmlspecialchars($trip['route_name']) ?>
                            </h3>

                            <!-- Вақти рафту омад -->
                            <div class="flex items-center justify-between bg-slate-900/60 p-3.5 rounded-xl border border-white/5 mb-4">
                                <div>
                                    <div class="text-xl font-bold text-white font-mono"><?= date('H:i', strtotime($trip['departure_time'])) ?></div>
                                    <div class="text-xs text-slate-400"><?= htmlspecialchars($trip['from_city']) ?></div>
                                </div>
                                <div class="flex flex-col items-center px-2">
                                    <span class="text-[10px] text-slate-500 font-mono"><?= htmlspecialchars($trip['duration_hours']) ?></span>
                                    <div class="w-16 h-0.5 bg-slate-700 relative my-1">
                                        <i class="fa-solid fa-chevron-right text-[8px] text-cyan-400 absolute -right-1 -top-1"></i>
                                    </div>
                                    <span class="text-[9px] text-emerald-400 font-semibold">Беистгоҳи мобайнӣ</span>
                                </div>
                                <div class="text-right">
                                    <div class="text-xl font-bold text-white font-mono"><?= date('H:i', strtotime($trip['arrival_time'])) ?></div>
                                    <div class="text-xs text-slate-400"><?= htmlspecialchars($trip['to_city']) ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Нарх ва тугмаи фармоиш -->
                        <div class="pt-3 border-t border-white/5 flex items-center justify-between">
                            <div>
                                <span class="text-[11px] text-slate-400 block">Аз нархи:</span>
                                <span class="text-base font-bold text-emerald-400 font-mono"><?= formatTJS($trip['base_price']) ?></span>
                            </div>
                            <a href="/train_ticket_system/select_seat.php?trip_id=<?= $trip['id'] ?>" class="btn btn-primary text-xs !py-2.5 !px-4">
                                Интихоби ҷой <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
