<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$pageTitle = 'Идораи Қатнавҳо (Ҷадвали ҳаракат)';

// Гирифтани маълумот барои интихоб
$routes = $pdo->query("SELECT * FROM routes ORDER BY route_name ASC")->fetchAll();
$trains = $pdo->query("SELECT * FROM trains WHERE status = 'active' ORDER BY train_number ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_trip') {
        $routeId = (int)($_POST['route_id'] ?? 0);
        $trainId = (int)($_POST['train_id'] ?? 0);
        $depTime = trim($_POST['departure_time'] ?? '');
        $arrTime = trim($_POST['arrival_time'] ?? '');
        $basePrice = (float)($_POST['base_price'] ?? 80.00);

        if ($routeId && $trainId && !empty($depTime) && !empty($arrTime)) {
            $stmt = $pdo->prepare("INSERT INTO trips (route_id, train_id, departure_time, arrival_time, base_price, status) VALUES (?, ?, ?, ?, ?, 'scheduled')");
            $stmt->execute([$routeId, $trainId, $depTime, $arrTime, $basePrice]);
            setFlash('success', 'Қатнави нав бомуваффақият ба ҷадвал илова карда шуд!');
        } else {
            setFlash('error', 'Ҳамаи майдонҳоро дуруст пур кунед!');
        }
        header('Location: /train_ticket_system/admin/trips.php');
        exit;
    }

    if ($action === 'delete_trip') {
        $tripId = (int)($_POST['trip_id'] ?? 0);
        if ($tripId) {
            $pdo->prepare("DELETE FROM trips WHERE id = ?")->execute([$tripId]);
            setFlash('success', 'Қатнав аз система ҳазф карда шуд!');
        }
        header('Location: /train_ticket_system/admin/trips.php');
        exit;
    }
}

// Рӯйхати ҳамаи қатнавҳо
$tripsQuery = "
    SELECT t.*, r.route_name, tr.train_number, tr.train_name,
           s1.city AS from_city, s2.city AS to_city
    FROM trips t
    JOIN routes r ON t.route_id = r.id
    JOIN trains tr ON t.train_id = tr.id
    JOIN stations s1 ON r.from_station_id = s1.id
    JOIN stations s2 ON r.to_station_id = s2.id
    ORDER BY t.departure_time DESC
";
$trips = $pdo->query($tripsQuery)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    
    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="/train_ticket_system/admin/index.php" class="text-xs text-slate-400 hover:text-white transition inline-flex items-center gap-1 mb-2">
                <i class="fa-solid fa-arrow-left"></i> Ба панели асосӣ
            </a>
            <h1 class="text-2xl md:text-3xl font-extrabold text-white">Идораи қатнавҳо (Рейсҳо)</h1>
            <p class="text-xs text-slate-400 mt-1">Танзими ҷадвали ҳаракати қатораҳо ва таъини нархҳо</p>
        </div>
    </div>

    <!-- Формаи банақшагирии қатнави нав -->
    <div class="glass-panel p-6 border-white/10 mb-8">
        <h2 class="text-base font-bold text-white mb-4 flex items-center gap-2">
            <i class="fa-solid fa-calendar-plus text-cyan-400"></i> Банақшагирии қатнави нав
        </h2>

        <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <input type="hidden" name="action" value="create_trip">

            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                    Хатсайр <span class="text-red-400">*</span>
                </label>
                <select name="route_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-xs focus:outline-none focus:border-cyan-500">
                    <option value="">Интихоби хатсайр...</option>
                    <?php foreach ($routes as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['route_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                    Қатора <span class="text-red-400">*</span>
                </label>
                <select name="train_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-xs focus:outline-none focus:border-cyan-500">
                    <option value="">Интихоби қатора...</option>
                    <?php foreach ($trains as $tr): ?>
                        <option value="<?= $tr['id'] ?>">№ <?= htmlspecialchars($tr['train_number']) ?> (<?= htmlspecialchars($tr['train_name']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                    Вақти ҳаракат <span class="text-red-400">*</span>
                </label>
                <input type="datetime-local" name="departure_time" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2 py-2 text-white text-xs focus:outline-none focus:border-cyan-500">
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                    Вақти расидан <span class="text-red-400">*</span>
                </label>
                <input type="datetime-local" name="arrival_time" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2 py-2 text-white text-xs focus:outline-none focus:border-cyan-500">
            </div>

            <div class="md:col-span-1">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                    Нарх
                </label>
                <input type="number" step="1" name="base_price" value="100" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-2 py-2 text-white text-xs focus:outline-none focus:border-cyan-500 font-mono">
            </div>

            <div class="md:col-span-1">
                <button type="submit" class="btn btn-primary w-full py-2.5 text-xs font-bold">
                    Сабт
                </button>
            </div>
        </form>
    </div>

    <!-- Ҷадвали ҳамаи қатнавҳо -->
    <div class="glass-panel p-6 border-white/10">
        <h2 class="text-base font-bold text-white mb-4">Рӯйхати қатнавҳо (<?= count($trips) ?>)</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-900/80 text-slate-400 uppercase font-mono border-b border-white/5">
                    <tr>
                        <th class="p-3">Қатора</th>
                        <th class="p-3">Хатсайр</th>
                        <th class="p-3">Ҳаракат (Рафтан)</th>
                        <th class="p-3">Расидан</th>
                        <th class="p-3">Нархи асосӣ</th>
                        <th class="p-3">Ҳолат</th>
                        <th class="p-3 text-right">Амал</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($trips as $t): ?>
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="p-3">
                                <span class="badge badge-primary font-mono">№ <?= htmlspecialchars($t['train_number']) ?></span>
                            </td>
                            <td class="p-3 font-semibold text-white"><?= htmlspecialchars($t['route_name']) ?></td>
                            <td class="p-3 font-mono"><?= date('d.m.Y — H:i', strtotime($t['departure_time'])) ?></td>
                            <td class="p-3 font-mono"><?= date('d.m.Y — H:i', strtotime($t['arrival_time'])) ?></td>
                            <td class="p-3 font-mono font-bold text-emerald-400"><?= formatTJS($t['base_price']) ?></td>
                            <td class="p-3">
                                <span class="badge badge-emerald"><?= $t['status'] ?></span>
                            </td>
                            <td class="p-3 text-right">
                                <form method="POST" action="" onsubmit="return confirm('Оё ин қатнавро нест кардан мехоҳед?');" class="inline">
                                    <input type="hidden" name="action" value="delete_trip">
                                    <input type="hidden" name="trip_id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="text-red-400 hover:text-red-300 p-1" title="Ҳазф">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
