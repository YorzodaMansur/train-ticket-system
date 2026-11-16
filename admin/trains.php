<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$pageTitle = 'Идораи Қатораҳо ва Вагонҳо';

// Коркарди амалиётҳо (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Илова кардани қатораи нав
    if ($action === 'create_train') {
        $trainNumber = trim($_POST['train_number'] ?? '');
        $trainName   = trim($_POST['train_name'] ?? '');
        $trainType   = $_POST['train_type'] ?? 'express';

        if (!empty($trainNumber) && !empty($trainName)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO trains (train_number, train_name, train_type) VALUES (?, ?, ?)");
                $stmt->execute([$trainNumber, $trainName, $trainType]);
                setFlash('success', 'Қатораи нав бомуваффақият сабт шуд!');
            } catch (Exception $e) {
                setFlash('error', 'Хатогӣ: Рақами қатора бояд беҳамто бошад!');
            }
        }
        header('Location: /train_ticket_system/admin/trains.php');
        exit;
    }

    // 2. Илова кардани вагони нав ба қатора + Автоматӣ пур кардани ҷойҳо (seats)
    if ($action === 'create_car') {
        $trainId   = (int)($_POST['train_id'] ?? 0);
        $carNumber = (int)($_POST['car_number'] ?? 1);
        $carType   = $_POST['car_type'] ?? 'coupe';
        $multiplier = (float)($_POST['price_multiplier'] ?? 1.0);

        $totalSeats = 36;
        if ($carType === 'sv_lux') $totalSeats = 18;
        if ($carType === 'platskart') $totalSeats = 54;

        if ($trainId && $carNumber) {
            $stmt = $pdo->prepare("INSERT INTO cars (train_id, car_number, car_type, total_seats, price_multiplier) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$trainId, $carNumber, $carType, $totalSeats, $multiplier]);
            $newCarId = $pdo->lastInsertId();

            // Генерацияи худкори ҷойҳо барои ин вагон дар ҷадвали `seats`
            $seatInsert = $pdo->prepare("INSERT INTO seats (car_id, seat_number, seat_type) VALUES (?, ?, ?)");

            for ($s = 1; $s <= $totalSeats; $s++) {
                $sType = 'lower';
                if ($carType === 'platskart') {
                    if ($s > 36) {
                        $sType = ($s % 2 !== 0) ? 'side_lower' : 'side_upper';
                    } else {
                        $sType = ($s % 2 !== 0) ? 'lower' : 'upper';
                    }
                } elseif ($carType === 'coupe') {
                    $sType = ($s % 2 !== 0) ? 'lower' : 'upper';
                } else {
                    $sType = 'lower'; // Ҳамаи ҷойҳои СВ / Люкс поёнӣ мебошанд
                }
                $seatInsert->execute([$newCarId, $s, $sType]);
            }

            setFlash('success', "Вагони №$carNumber ($carType) бо $totalSeats ҷой бомуваффақият сохта шуд!");
        }
        header('Location: /train_ticket_system/admin/trains.php');
        exit;
    }

    // 3. Ҳазфи вагон
    if ($action === 'delete_car') {
        $carId = (int)($_POST['car_id'] ?? 0);
        if ($carId) {
            $pdo->prepare("DELETE FROM cars WHERE id = ?")->execute([$carId]);
            setFlash('success', 'Вагон ҳазф шуд!');
        }
        header('Location: /train_ticket_system/admin/trains.php');
        exit;
    }
}

// Гирифтани рӯйхати қатораҳо бо вагонҳояшон
$trains = $pdo->query("SELECT * FROM trains ORDER BY id DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    
    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="/train_ticket_system/admin/index.php" class="text-xs text-slate-400 hover:text-white transition inline-flex items-center gap-1 mb-2">
                <i class="fa-solid fa-arrow-left"></i> Ба панели асосӣ
            </a>
            <h1 class="text-2xl md:text-3xl font-extrabold text-white">Идораи қатораҳо ва вагонҳо</h1>
            <p class="text-xs text-slate-400 mt-1">Танзими парки қатораҳо, таркиби вагонҳо (купе, плацкарт, люкс) ва ҷойҳо</p>
        </div>
    </div>

    <!-- Формаҳои илова кардан -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        
        <!-- Формаи қатораи нав -->
        <div class="glass-panel p-6 border-white/10">
            <h2 class="text-base font-bold text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-train text-cyan-400"></i> Сабти қатораи нав
            </h2>

            <form method="POST" action="" class="space-y-4">
                <input type="hidden" name="action" value="create_train">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Рақами қатора <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="train_number" required placeholder="Масалан: 007/008" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Намуди қатора
                        </label>
                        <select name="train_type" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500">
                            <option value="highspeed">Баландсуръат (Highspeed)</option>
                            <option value="express" selected>Экспресс</option>
                            <option value="intercity">Байнишаҳрии оддӣ</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Номи брендии қатора <span class="text-red-400">*</span>
                    </label>
                    <input type="text" name="train_name" required placeholder="Масалан: Қатораи «Симург»" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500">
                </div>

                <button type="submit" class="btn btn-primary w-full py-2.5 text-xs font-bold">
                    <i class="fa-solid fa-plus"></i> Сабти қатора
                </button>
            </form>
        </div>

        <!-- Формаи вагон ба қатора -->
        <div class="glass-panel p-6 border-white/10">
            <h2 class="text-base font-bold text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-couch text-amber-400"></i> Иловаи вагон ба қатора
            </h2>

            <form method="POST" action="" class="space-y-4">
                <input type="hidden" name="action" value="create_car">

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Интихоби қатора <span class="text-red-400">*</span>
                    </label>
                    <select name="train_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500">
                        <option value="">Қатораро интихоб кунед...</option>
                        <?php foreach ($trains as $tr): ?>
                            <option value="<?= $tr['id'] ?>">№ <?= htmlspecialchars($tr['train_number']) ?> — <?= htmlspecialchars($tr['train_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Рақами вагон
                        </label>
                        <input type="number" name="car_number" min="1" max="25" value="1" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Навъи вагон
                        </label>
                        <select name="car_type" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2 py-2.5 text-white text-xs focus:outline-none focus:border-cyan-500">
                            <option value="coupe" selected>Купе (36 ҷой)</option>
                            <option value="platskart">Плацкарт (54 ҷой)</option>
                            <option value="sv_lux">СВ / Люкс (18 ҷой)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Коэф. нарх
                        </label>
                        <input type="number" step="0.05" name="price_multiplier" value="1.4" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500 font-mono">
                    </div>
                </div>

                <button type="submit" class="btn btn-emerald w-full py-2.5 text-xs font-bold">
                    <i class="fa-solid fa-plus"></i> Сабти вагон ва сохтани худкори ҷойҳо
                </button>
            </form>
        </div>

    </div>

    <!-- Рӯйхати қатораҳо ва вагонҳои таркиб -->
    <div class="space-y-6">
        <?php foreach ($trains as $tr): ?>
            <?php 
            $carsStmt = $pdo->prepare("SELECT * FROM cars WHERE train_id = ? ORDER BY car_number ASC");
            $carsStmt->execute([$tr['id']]);
            $trCars = $carsStmt->fetchAll();
            ?>
            <div class="glass-panel p-6 border-white/10">
                <div class="flex items-center justify-between pb-4 border-b border-white/5 mb-4">
                    <div class="flex items-center gap-3">
                        <span class="badge badge-primary text-sm font-mono">
                            <i class="fa-solid fa-train"></i> № <?= htmlspecialchars($tr['train_number']) ?>
                        </span>
                        <h3 class="text-lg font-bold text-white"><?= htmlspecialchars($tr['train_name']) ?></h3>
                        <span class="badge badge-emerald uppercase"><?= $tr['train_type'] ?></span>
                    </div>
                    <span class="text-xs text-slate-400">Шумораи вагонҳо: <strong class="text-cyan-400"><?= count($trCars) ?></strong></span>
                </div>

                <?php if (empty($trCars)): ?>
                    <p class="text-xs text-slate-500 py-2">Ба ин қатора ҳоло ягон вагон пайваст карда нашудааст.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <?php foreach ($trCars as $car): ?>
                            <div class="bg-slate-900/80 p-4 rounded-xl border border-white/5 flex flex-col justify-between">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-bold text-white font-mono text-sm">Вагони №<?= $car['car_number'] ?></span>
                                    <form method="POST" action="" onsubmit="return confirm('Оё ин вагон ва ҷойҳои онро ҳазф кардан мехоҳед?');">
                                        <input type="hidden" name="action" value="delete_car">
                                        <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
                                        <button type="submit" class="text-red-400 hover:text-red-300 text-xs">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                                <div class="text-xs text-cyan-400 font-semibold mb-1">
                                    <?= carTypeName($car['car_type']) ?>
                                </div>
                                <div class="text-[11px] text-slate-400 flex justify-between">
                                    <span>Ҷойҳо: <?= $car['total_seats'] ?></span>
                                    <span>Коэф: x<?= $car['price_multiplier'] ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
