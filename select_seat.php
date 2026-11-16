<?php
define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Интихоби вагон ва ҷой дар нақша';

$tripId = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;
if (!$tripId) {
    header('Location: /train_ticket_system/trips.php');
    exit;
}

// 1. Гирифтани маълумоти қатнав
$tripStmt = $pdo->prepare("
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
    WHERE t.id = ?
");
$tripStmt->execute([$tripId]);
$trip = $tripStmt->fetch();

if (!$trip) {
    die("Қатнав ёфт нашуд!");
}

// 2. Гирифтани ҳамаи вагонҳои қатора
$carsStmt = $pdo->prepare("SELECT * FROM cars WHERE train_id = ? ORDER BY car_number ASC");
$carsStmt->execute([$trip['train_id']]);
$cars = $carsStmt->fetchAll();

if (empty($cars)) {
    die("Барои ин қатора вагонҳо ворид нашудаанд.");
}

// Муайян кардани вагони интихобшуда (бо нобаёнӣ вагони якум)
$selectedCarId = isset($_GET['car_id']) ? (int)$_GET['car_id'] : $cars[0]['id'];
$activeCar = null;
foreach ($cars as $c) {
    if ($c['id'] == $selectedCarId) {
        $activeCar = $c;
        break;
    }
}
if (!$activeCar) {
    $activeCar = $cars[0];
    $selectedCarId = $activeCar['id'];
}

// Нархи вагони интихобшуда
$carPrice = round($trip['base_price'] * $activeCar['price_multiplier'], 2);

// 3. Гирифтани ҳамаи ҷойҳои ин вагон
$seatsStmt = $pdo->prepare("SELECT * FROM seats WHERE car_id = ? ORDER BY seat_number ASC");
$seatsStmt->execute([$selectedCarId]);
$seats = $seatsStmt->fetchAll();

// 4. Гирифтани ҳолати ҷойҳо дар ин қатнав (банд ё фурӯхташуда)
// Талаботи ТЗ: 15 дақиқа банд, пас аз он холӣ мешавад.
$ticketsStmt = $pdo->prepare("
    SELECT seat_id, status, payment_status, expires_at, passenger_id
    FROM tickets
    WHERE trip_id = ? AND car_id = ? AND status IN ('confirmed', 'reserved')
");
$ticketsStmt->execute([$tripId, $selectedCarId]);
$bookedTickets = $ticketsStmt->fetchAll();

// Харитаи ҳолати ҷойҳо
$seatStatuses = [];
$now = time();
$currentUserId = currentUser()['id'] ?? null;

foreach ($bookedTickets as $bt) {
    $expTime = strtotime($bt['expires_at']);
    if ($bt['status'] === 'confirmed' && $bt['payment_status'] === 'paid') {
        $seatStatuses[$bt['seat_id']] = 'sold'; // Фурӯхташуда (Сурх)
    } elseif ($bt['status'] === 'reserved' && $bt['payment_status'] === 'pending') {
        if ($expTime > $now) {
            $seatStatuses[$bt['seat_id']] = 'reserved'; // Бандшудаи муваққатӣ 15-дақиқа (Зард)
        }
    }
}

// Агар форма барои банд кардани ҷой фиристода шуда бошад (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reserve_seat') {
    if (!isLoggedIn()) {
        setFlash('error', 'Барои интихоби ҷой ва хариди чипта лутфан аввал вориди система шавед ё худро номнавис кунед!');
        header("Location: /train_ticket_system/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }

    $seatId = (int)($_POST['seat_id'] ?? 0);

    // Санҷиши дастрас будани ҷой
    if (!isset($seatStatuses[$seatId])) {
        // Ҷой холӣ аст, онро ба муддати 15 дақиқа банд мекунем
        $ticketNumber = generateTicketNumber();
        $user = currentUser();
        
        $insertTicket = $pdo->prepare("
            INSERT INTO tickets 
            (ticket_number, trip_id, car_id, seat_id, passenger_id, passenger_name, passenger_passport, passenger_phone, price, status, payment_status, reserved_at, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'reserved', 'pending', NOW(), NOW() + INTERVAL 15 MINUTE)
        ");
        $insertTicket->execute([
            $ticketNumber,
            $tripId,
            $selectedCarId,
            $seatId,
            $user['id'],
            $user['full_name'],
            $user['passport_series'] ?? 'A000000',
            $user['phone'] ?? '',
            $carPrice
        ]);

        $newTicketId = $pdo->lastInsertId();
        header("Location: /train_ticket_system/checkout.php?ticket_id=" . $newTicketId);
        exit;
    } else {
        setFlash('error', 'Ин ҷой аллакай банд ё фурӯхта шудааст. Лутфан ҷойи дигарро интихоб кунед.');
        header("Location: /train_ticket_system/select_seat.php?trip_id=$tripId&car_id=$selectedCarId");
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    
    <!-- Панели маълумот оид ба қатнав -->
    <div class="glass-panel p-6 mb-8 border-cyan-500/20">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="badge badge-primary">Қатораи № <?= htmlspecialchars($trip['train_number']) ?></span>
                    <span class="text-sm font-semibold text-slate-300"><?= htmlspecialchars($trip['train_name']) ?></span>
                </div>
                <h1 class="text-2xl font-bold text-white"><?= htmlspecialchars($trip['route_name']) ?></h1>
                <p class="text-xs text-slate-400 mt-1">
                    <i class="fa-solid fa-clock mr-1"></i> Вақти ҳаракат: <strong class="text-slate-200"><?= date('d.m.Y — H:i', strtotime($trip['departure_time'])) ?></strong>
                    <span class="mx-2">|</span>
                    <i class="fa-solid fa-location-dot mr-1"></i> Расидан: <strong class="text-slate-200"><?= date('H:i', strtotime($trip['arrival_time'])) ?></strong>
                </p>
            </div>
            <div class="bg-slate-900/80 px-6 py-4 rounded-xl border border-white/5 text-right">
                <span class="text-xs text-slate-400 block">Нархи асосӣ барои ин вагон:</span>
                <span class="text-2xl font-black text-emerald-400 font-mono"><?= formatTJS($carPrice) ?></span>
            </div>
        </div>
    </div>

    <!-- Интихоби вагон (Car Selection Tabs) -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold text-white">Интихоби вагон:</h2>
            <span class="text-xs text-slate-400">Шумораи умумии вагонҳо: <?= count($cars) ?></span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <?php foreach ($cars as $car): ?>
                <?php 
                $isSelected = ($car['id'] == $selectedCarId);
                $thisPrice = round($trip['base_price'] * $car['price_multiplier'], 2);
                ?>
                <a href="/train_ticket_system/select_seat.php?trip_id=<?= $tripId ?>&car_id=<?= $car['id'] ?>" 
                   class="glass-panel p-4 flex flex-col justify-between text-center transition-all <?= $isSelected ? 'border-cyan-400 bg-cyan-950/30 ring-2 ring-cyan-500/30' : 'hover:border-slate-600' ?>">
                    <div class="text-xs uppercase font-mono font-bold <?= $isSelected ? 'text-cyan-400' : 'text-slate-400' ?>">
                        Вагони №<?= $car['car_number'] ?>
                    </div>
                    <div class="text-sm font-bold text-white my-1">
                        <?= carTypeName($car['car_type']) ?>
                    </div>
                    <div class="text-xs font-mono font-semibold text-emerald-400">
                        <?= formatTJS($thisPrice) ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Роҳнамои рангҳои ҷойҳо (Legend) -->
    <div class="glass-panel p-4 mb-6 flex flex-wrap items-center justify-between gap-4 text-xs">
        <div class="flex flex-wrap items-center gap-6">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-md bg-slate-800 border border-slate-600"></div>
                <span class="text-slate-300">Ҷойи холӣ (Дастрас)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-md bg-cyan-500 border border-cyan-300 shadow-md shadow-cyan-500/50"></div>
                <span class="text-white font-semibold">Ҷойи интихобшуда</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-md bg-amber-500/30 border border-amber-500/60"></div>
                <span class="text-amber-400 font-semibold">Бандшуда (15 дақиқа)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-md bg-red-500/20 border border-red-500/50"></div>
                <span class="text-red-400 font-semibold">Фурӯхташуда</span>
            </div>
        </div>
        <div class="text-slate-400 flex items-center gap-2">
            <i class="fa-solid fa-circle-info text-cyan-400"></i>
            <span>Барои интихоб болои чоркунҷаи ҷой клик кунед</span>
        </div>
    </div>

    <!-- Нақшаи графикии вагон (Wagon Graphic Blueprint) -->
    <div class="wagon-wrapper mb-8 shadow-2xl">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-white/10 text-xs text-slate-400 font-mono">
            <span>◄ САРИ ҚАТОРА (ЛОКОМОТИВ)</span>
            <span class="text-cyan-400 font-bold uppercase">ВАГОНИ №<?= $activeCar['car_number'] ?> — <?= carTypeName($activeCar['car_type']) ?></span>
            <span>ОХИРИ ВАГОН ►</span>
        </div>

        <!-- Ҷойгиршавии ҷойҳо аз рӯи навъи вагон -->
        <div class="py-4">
            <?php if ($activeCar['car_type'] === 'coupe' || $activeCar['car_type'] === 'sv_lux'): ?>
                <!-- Намуди Купе ва СВ (Блокҳои 4-ҷоя ё 2-ҷоя) -->
                <div class="grid grid-cols-9 gap-3">
                    <?php 
                    // Гурӯҳбандии ҷойҳо ба купеҳо (дар ҳар як купе 4 ҷой барои купе ё 2 ҷой барои СВ)
                    $seatsPerComp = ($activeCar['car_type'] === 'sv_lux') ? 2 : 4;
                    $totalComps = ceil(count($seats) / $seatsPerComp);

                    for ($comp = 0; $comp < $totalComps; $comp++): 
                        $compSeats = array_slice($seats, $comp * $seatsPerComp, $seatsPerComp);
                    ?>
                        <div class="bg-slate-900/60 p-3 rounded-xl border border-slate-700/60 flex flex-col items-center">
                            <span class="text-[10px] text-slate-400 font-mono mb-2">Купе <?= $comp + 1 ?></span>
                            
                            <div class="grid grid-cols-2 gap-2">
                                <?php foreach ($compSeats as $s): ?>
                                    <?php 
                                    $st = $seatStatuses[$s['id']] ?? 'available';
                                    $statusClass = 'seat-available';
                                    if ($st === 'sold') $statusClass = 'seat-sold';
                                    if ($st === 'reserved') $statusClass = 'seat-reserved';
                                    ?>
                                    <div class="seat-box <?= $statusClass ?>" 
                                         data-seat-id="<?= $s['id'] ?>"
                                         data-seat-number="<?= $s['seat_number'] ?>"
                                         data-seat-type="<?= seatTypeName($s['seat_type']) ?>"
                                         title="Ҷойи №<?= $s['seat_number'] ?> (<?= seatTypeName($s['seat_type']) ?>)">
                                        <span class="text-xs"><?= $s['seat_number'] ?></span>
                                        <span class="text-[8px] opacity-75 font-normal uppercase"><?= $s['seat_type'] == 'lower' ? 'Поён' : 'Боло' ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <!-- Роҳрави вагон (Aisle) -->
                <div class="mt-4 pt-2 border-t-2 border-dashed border-slate-700/60 text-center text-[10px] text-slate-400 tracking-widest font-mono uppercase">
                    ── РОҲРАВИ ВАГОН (ДАРОМАДГОҲИ МУСОФИРОН) ──
                </div>

            <?php else: ?>
                <!-- Намуди Плацкарт (Ҷойҳои асосӣ 1-36 ва ҷойҳои паҳлӯӣ 37-54) -->
                <div class="space-y-4">
                    <!-- Ҷойҳои купейии плацкарт -->
                    <div class="grid grid-cols-9 gap-3">
                        <?php 
                        $mainSeats = array_filter($seats, fn($s) => $s['seat_number'] <= 36);
                        $comps = array_chunk($mainSeats, 4);
                        foreach ($comps as $idx => $compSeats): 
                        ?>
                            <div class="bg-slate-900/60 p-2.5 rounded-xl border border-slate-700/60 flex flex-col items-center">
                                <span class="text-[10px] text-slate-400 font-mono mb-1.5">Бахши <?= $idx + 1 ?></span>
                                <div class="grid grid-cols-2 gap-1.5">
                                    <?php foreach ($compSeats as $s): ?>
                                        <?php 
                                        $st = $seatStatuses[$s['id']] ?? 'available';
                                        $statusClass = 'seat-available';
                                        if ($st === 'sold') $statusClass = 'seat-sold';
                                        if ($st === 'reserved') $statusClass = 'seat-reserved';
                                        ?>
                                        <div class="seat-box <?= $statusClass ?>" 
                                             data-seat-id="<?= $s['id'] ?>"
                                             data-seat-number="<?= $s['seat_number'] ?>"
                                             data-seat-type="<?= seatTypeName($s['seat_type']) ?>"
                                             title="Ҷойи №<?= $s['seat_number'] ?> (<?= seatTypeName($s['seat_type']) ?>)">
                                            <span class="text-xs"><?= $s['seat_number'] ?></span>
                                            <span class="text-[8px] opacity-75 font-normal"><?= $s['seat_type'] == 'lower' ? 'Поён' : 'Боло' ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Роҳрави мобайн -->
                    <div class="py-1 border-y border-dashed border-slate-700 text-center text-[10px] text-slate-400 tracking-widest font-mono uppercase">
                        ── РОҲРАВИ ВАГОНИ ПЛАЦКАРТ ──
                    </div>

                    <!-- Ҷойҳои паҳлӯӣ (Боковые места 37-54) -->
                    <div class="grid grid-cols-9 gap-3">
                        <?php 
                        $sideSeats = array_filter($seats, fn($s) => $s['seat_number'] > 36);
                        $sideComps = array_chunk($sideSeats, 2);
                        foreach ($sideComps as $idx => $compSeats): 
                        ?>
                            <div class="bg-slate-900/40 p-2 rounded-xl border border-slate-700/40 flex flex-col items-center">
                                <div class="grid grid-cols-2 gap-1.5">
                                    <?php foreach ($compSeats as $s): ?>
                                        <?php 
                                        $st = $seatStatuses[$s['id']] ?? 'available';
                                        $statusClass = 'seat-available';
                                        if ($st === 'sold') $statusClass = 'seat-sold';
                                        if ($st === 'reserved') $statusClass = 'seat-reserved';
                                        ?>
                                        <div class="seat-box <?= $statusClass ?>" 
                                             data-seat-id="<?= $s['id'] ?>"
                                             data-seat-number="<?= $s['seat_number'] ?>"
                                             data-seat-type="Паҳлӯӣ (<?= seatTypeName($s['seat_type']) ?>)"
                                             title="Ҷойи паҳлӯии №<?= $s['seat_number'] ?>">
                                            <span class="text-xs"><?= $s['seat_number'] ?></span>
                                            <span class="text-[7px] opacity-75">Паҳлӯ</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Панели тасдиқи интихоб ва фармоиш (Bottom Floating Action Bar) -->
    <div class="glass-panel p-6 border-cyan-500/30 sticky bottom-4 z-40 bg-slate-950/95 backdrop-blur-2xl shadow-2xl">
        <form method="POST" action="" class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <input type="hidden" name="action" value="reserve_seat">
            <input type="hidden" name="seat_id" id="selectedSeatInput" value="">
            <input type="hidden" id="baseCarPrice" value="<?= $carPrice ?>">

            <div class="flex items-center gap-4 text-sm">
                <div>
                    <span class="text-xs text-slate-400 block">Ҷойи интихобшуда:</span>
                    <span id="selectedSeatNumberDisplay" class="font-bold text-cyan-400 text-lg">
                        Ҳанӯз интихоб нашудааст
                    </span>
                </div>
                <div class="border-l border-white/10 pl-4">
                    <span class="text-xs text-slate-400 block">Маблағи пардохт:</span>
                    <span id="seatPriceDisplay" class="font-black text-emerald-400 text-xl font-mono">
                        <?= formatTJS($carPrice) ?>
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <button type="submit" id="submitSeatBtn" disabled class="btn btn-primary w-full sm:w-auto opacity-50 cursor-not-allowed">
                    <i class="fa-solid fa-lock"></i> Банд кардани ҷой (15 дақиқа)
                </button>
            </div>
        </form>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
