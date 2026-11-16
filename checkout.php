<?php
define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Пардохт ва тасдиқи чипта (15 дақиқа)';

if (!isLoggedIn()) {
    setFlash('error', 'Лутфан аввал ба система ворид шавед.');
    header('Location: /train_ticket_system/login.php');
    exit;
}

$ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
if (!$ticketId) {
    header('Location: /train_ticket_system/trips.php');
    exit;
}

// Гирифтани маълумоти чипта ва қатнав
$ticketStmt = $pdo->prepare("
    SELECT tk.*, 
           t.departure_time, t.arrival_time,
           r.route_name, r.duration_hours,
           s1.name AS from_station, s1.city AS from_city,
           s2.name AS to_station, s2.city AS to_city,
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
    WHERE tk.id = ?
");
$ticketStmt->execute([$ticketId]);
$ticket = $ticketStmt->fetch();

if (!$ticket) {
    die("Чипта ёфт нашуд!");
}

$user = currentUser();

// Санҷиши дастрасӣ (танҳо худи мусофир ё кассир/админ)
if ($user['role'] === 'passenger' && $ticket['passenger_id'] != $user['id']) {
    die("Шумо ба ин фармоиш дастрасӣ надоред.");
}

// Санҷиши муҳлати 15-дақиқагӣ
$expiresAt = strtotime($ticket['expires_at']);
$now = time();

if ($ticket['status'] === 'confirmed' && $ticket['payment_status'] === 'paid') {
    // Чипта аллакай пардохт шудааст
    header("Location: /train_ticket_system/ticket_success.php?ticket_id=" . $ticket['id']);
    exit;
}

if ($ticket['status'] === 'cancelled' || $ticket['payment_status'] === 'expired' || $now > $expiresAt) {
    // Муҳлат гузашт ё бекор шуд
    $updateStmt = $pdo->prepare("UPDATE tickets SET status = 'cancelled', payment_status = 'expired' WHERE id = ?");
    $updateStmt->execute([$ticketId]);

    setFlash('error', 'Муҳлати 15-дақиқагии банд кардани ин ҷой ба охир расид ва ҷой худкор холӣ карда шуд.');
    header("Location: /train_ticket_system/select_seat.php?trip_id=" . $ticket['trip_id']);
    exit;
}

// Коркарди пардохт (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'cancel_reservation') {
        // Бекор кардани пеш аз муҳлат
        $cancelStmt = $pdo->prepare("UPDATE tickets SET status = 'cancelled', payment_status = 'expired' WHERE id = ?");
        $cancelStmt->execute([$ticketId]);
        setFlash('success', 'Бандкунии ҷой бекор карда шуд ва ҷой барои дигарон холӣ гардид.');
        header("Location: /train_ticket_system/select_seat.php?trip_id=" . $ticket['trip_id']);
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'confirm_payment') {
        $passengerName = trim($_POST['passenger_name'] ?? '');
        $passengerPassport = trim($_POST['passenger_passport'] ?? '');
        $passengerPhone = trim($_POST['passenger_phone'] ?? '');
        $paymentMethod = $_POST['payment_method'] ?? 'online_card';

        if (empty($passengerName) || empty($passengerPassport)) {
            $error = 'Лутфан ному насаб ва рақами шиносномаро ворид кунед!';
        } else {
            // Муваффақона пардохт шуд!
            $payStmt = $pdo->prepare("
                UPDATE tickets 
                SET status = 'confirmed', 
                    payment_status = 'paid', 
                    payment_method = ?,
                    passenger_name = ?,
                    passenger_passport = ?,
                    passenger_phone = ?,
                    purchased_at = NOW()
                WHERE id = ?
            ");
            $payStmt->execute([
                $paymentMethod,
                $passengerName,
                $passengerPassport,
                $passengerPhone,
                $ticketId
            ]);

            setFlash('success', 'Чипта бомуваффақият пардохт ва сабт гардид!');
            header("Location: /train_ticket_system/ticket_success.php?ticket_id=" . $ticketId);
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    
    <!-- Ҳисобкунаки 15 дақиқагӣ (Countdown Bar) -->
    <div class="glass-panel p-4 mb-8 border-amber-500/40 bg-amber-950/20 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-2xl animate-pulse">
                <i class="fa-solid fa-stopwatch"></i>
            </div>
            <div>
                <h3 class="font-bold text-white text-base">Ҷойи шумо ба муддати 15 дақиқа банд карда шудааст</h3>
                <p class="text-xs text-amber-300">
                    Лутфан пардохтро то ба итмом расидани вақт анҷом диҳед, вагарна ҷой ба таври худкор озод карда мешавад.
                </p>
            </div>
        </div>
        <div class="bg-slate-900 px-6 py-2.5 rounded-xl border border-amber-500/40 text-center">
            <span class="text-[10px] text-slate-400 uppercase font-mono block">Вақти боқимонда:</span>
            <span id="reservationCountdown" data-expires-timestamp="<?= $expiresAt ?>" class="text-3xl font-black font-mono text-amber-400">
                15:00
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Тафсилоти чипта ва қатнав (Сутуни чап) -->
        <div class="lg:col-span-5 space-y-6">
            <div class="glass-panel p-6 border-white/10">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/5">
                    <span class="badge badge-primary">
                        <i class="fa-solid fa-train"></i> <?= htmlspecialchars($ticket['train_number']) ?>
                    </span>
                    <span class="text-xs font-mono text-cyan-400">
                        № <?= htmlspecialchars($ticket['ticket_number']) ?>
                    </span>
                </div>

                <h2 class="text-xl font-bold text-white mb-4"><?= htmlspecialchars($ticket['route_name']) ?></h2>

                <!-- Ҷадвали вақтҳо -->
                <div class="bg-slate-900/60 p-4 rounded-xl border border-white/5 space-y-3 mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 block uppercase">Ҳаракат (Рафтан):</span>
                            <span class="text-lg font-bold text-white font-mono"><?= date('H:i', strtotime($ticket['departure_time'])) ?></span>
                            <span class="text-xs text-slate-300 block"><?= htmlspecialchars($ticket['from_city']) ?></span>
                            <span class="text-[10px] text-slate-500"><?= htmlspecialchars($ticket['from_station']) ?></span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 block uppercase">Расидан:</span>
                            <span class="text-lg font-bold text-white font-mono"><?= date('H:i', strtotime($ticket['arrival_time'])) ?></span>
                            <span class="text-xs text-slate-300 block"><?= htmlspecialchars($ticket['to_city']) ?></span>
                            <span class="text-[10px] text-slate-500"><?= htmlspecialchars($ticket['to_station']) ?></span>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-white/5 text-xs text-slate-400 flex justify-between">
                        <span>Санаи сафар:</span>
                        <strong class="text-white"><?= date('d.m.Y', strtotime($ticket['departure_time'])) ?></strong>
                    </div>
                </div>

                <!-- Тафсилоти ҷой -->
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="p-3 bg-slate-900/40 rounded-xl border border-white/5">
                        <span class="text-[10px] text-slate-400 uppercase block">Вагон:</span>
                        <span class="text-base font-bold text-white">№ <?= $ticket['car_number'] ?></span>
                        <span class="text-xs text-cyan-400 block"><?= carTypeName($ticket['car_type']) ?></span>
                    </div>
                    <div class="p-3 bg-slate-900/40 rounded-xl border border-white/5">
                        <span class="text-[10px] text-slate-400 uppercase block">Ҷой:</span>
                        <span class="text-base font-bold text-white">№ <?= $ticket['seat_number'] ?></span>
                        <span class="text-xs text-emerald-400 block"><?= seatTypeName($ticket['seat_type']) ?></span>
                    </div>
                </div>

                <!-- Маблағи умумӣ -->
                <div class="p-4 bg-emerald-950/20 border border-emerald-500/30 rounded-xl flex items-center justify-between">
                    <div>
                        <span class="text-xs text-slate-300 block">Маблағи умумии чипта:</span>
                        <span class="text-[11px] text-slate-400">Бо назардошти ААҲ (НДС)</span>
                    </div>
                    <span class="text-2xl font-black text-emerald-400 font-mono">
                        <?= formatTJS($ticket['price']) ?>
                    </span>
                </div>
            </div>

            <!-- Бекор кардани фармоиш -->
            <form method="POST" action="" onsubmit="return confirm('Оё мутмаин ҳастед, ки мехоҳед бандкунии ҷойро бекор кунед?');">
                <input type="hidden" name="action" value="cancel_reservation">
                <button type="submit" class="btn btn-secondary w-full text-xs text-red-400 hover:text-red-300 border-red-500/20">
                    <i class="fa-solid fa-ban"></i> Бекор кардани бандкунӣ ва озод кардани ҷой
                </button>
            </form>
        </div>

        <!-- Формаи пардохт ва тасдиқ (Сутуни рост) -->
        <div class="lg:col-span-7">
            <div class="glass-panel p-6 md:p-8 border-cyan-500/20">
                <h2 class="text-xl font-bold text-white mb-2">Тасдиқи маълумоти мусофир ва пардохт</h2>
                <p class="text-xs text-slate-400 mb-6 leading-relaxed">
                    Мутобиқи қоидаҳои роҳи оҳан, маълумоти шиносномаи мусофир ҳангоми нишастан ба қатора санҷида мешавад.
                </p>

                <?php if (isset($error)): ?>
                    <div class="p-3 bg-red-500/20 border border-red-500/40 text-red-200 rounded-xl text-xs mb-4">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-5">
                    <input type="hidden" name="action" value="confirm_payment">

                    <!-- Ному насаб -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Ному насаби пурраи мусофир <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="passenger_name" required value="<?= htmlspecialchars($ticket['passenger_name']) ?>" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-cyan-500">
                    </div>

                    <!-- Шиноснома ва телефон -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                Силсила ва рақами шиноснома <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="passenger_passport" required value="<?= htmlspecialchars($ticket['passenger_passport']) ?>" placeholder="Масалан: A12345678" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-cyan-500 font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                Рақами телефони тамос
                            </label>
                            <input type="text" name="passenger_phone" value="<?= htmlspecialchars($ticket['passenger_phone'] ?? '') ?>" placeholder="+992 90 000 0000" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-cyan-500 font-mono">
                        </div>
                    </div>

                    <!-- Усули пардохт (Payment Methods) -->
                    <div class="pt-4 border-t border-white/5">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-3">
                            Интихоби усули пардохт:
                        </label>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_method" value="online_card" checked class="peer sr-only">
                                <div class="glass-panel p-4 rounded-xl border border-slate-700 peer-checked:border-cyan-400 peer-checked:bg-cyan-950/30 transition text-center">
                                    <i class="fa-solid fa-credit-card text-2xl text-cyan-400 mb-2"></i>
                                    <div class="text-xs font-bold text-white">Корти Миллӣ</div>
                                    <div class="text-[10px] text-slate-400">Ҳамаи бонкҳо</div>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input type="radio" name="payment_method" value="alif_mobi" class="peer sr-only">
                                <div class="glass-panel p-4 rounded-xl border border-slate-700 peer-checked:border-emerald-400 peer-checked:bg-emerald-950/30 transition text-center">
                                    <i class="fa-solid fa-wallet text-2xl text-emerald-400 mb-2"></i>
                                    <div class="text-xs font-bold text-white">Алиф Моби</div>
                                    <div class="text-[10px] text-slate-400">QR / Ҳамён</div>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input type="radio" name="payment_method" value="dushanbe_city" class="peer sr-only">
                                <div class="glass-panel p-4 rounded-xl border border-slate-700 peer-checked:border-blue-400 peer-checked:bg-blue-950/30 transition text-center">
                                    <i class="fa-solid fa-mobile-screen-button text-2xl text-blue-400 mb-2"></i>
                                    <div class="text-xs font-bold text-white">Душанбе Сити</div>
                                    <div class="text-[10px] text-slate-400">City Pay</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Тугмаи пардохт -->
                    <div class="pt-4">
                        <button type="submit" class="btn btn-emerald w-full py-4 text-base font-bold shadow-lg shadow-emerald-500/20">
                            <i class="fa-solid fa-circle-check"></i> Пардохт кардан (<?= formatTJS($ticket['price']) ?>)
                        </button>
                        <p class="text-[11px] text-center text-slate-400 mt-2">
                            Пас аз пахши тугма пардохт ба таври фаврӣ тасдиқ гардида, чиптаи Шумо тавлид мешавад.
                        </p>
                    </div>

                </form>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
