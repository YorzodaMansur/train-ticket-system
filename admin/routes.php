<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$pageTitle = 'Идораи Хатсайрҳо';

// Истгоҳҳо барои интихоб
$stations = $pdo->query("SELECT * FROM stations WHERE is_active = 1 ORDER BY city ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $routeName = trim($_POST['route_name'] ?? '');
        $fromId = (int)($_POST['from_station_id'] ?? 0);
        $toId   = (int)($_POST['to_station_id'] ?? 0);
        $distance = (float)($_POST['distance_km'] ?? 0);
        $duration = trim($_POST['duration_hours'] ?? '');

        if (!empty($routeName) && $fromId && $toId && $fromId !== $toId) {
            $stmt = $pdo->prepare("INSERT INTO routes (route_name, from_station_id, to_station_id, distance_km, duration_hours) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$routeName, $fromId, $toId, $distance, $duration]);
            setFlash('success', 'Хатсайри нав бомуваффақият сабт шуд!');
        } else {
            setFlash('error', 'Истгоҳи ибтидоӣ ва ниҳоӣ бояд гуногун бошанд!');
        }
        header('Location: /train_ticket_system/admin/routes.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM routes WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Хатсайр ҳазф карда шуд!');
        }
        header('Location: /train_ticket_system/admin/routes.php');
        exit;
    }
}

$routesQuery = "
    SELECT r.*, s1.city AS from_city, s2.city AS to_city
    FROM routes r
    JOIN stations s1 ON r.from_station_id = s1.id
    JOIN stations s2 ON r.to_station_id = s2.id
    ORDER BY r.id DESC
";
$routes = $pdo->query($routesQuery)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    
    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="/train_ticket_system/admin/index.php" class="text-xs text-slate-400 hover:text-white transition inline-flex items-center gap-1 mb-2">
                <i class="fa-solid fa-arrow-left"></i> Ба панели асосӣ
            </a>
            <h1 class="text-2xl md:text-3xl font-extrabold text-white">Идораи хатсайрҳои байнишаҳрӣ</h1>
            <p class="text-xs text-slate-400 mt-1">Танзими самтҳо, масофаҳо ва давомнокии сафар</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Формаи сабти хатсайр -->
        <div class="lg:col-span-4">
            <div class="glass-panel p-6 border-white/10">
                <h2 class="text-base font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-plus-circle text-emerald-400"></i> Иловаи хатсайри нав
                </h2>

                <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="action" value="create">

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Номи хатсайр <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="route_name" required placeholder="Масалан: Душанбе — Хуҷанд" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Истгоҳи ибтидоӣ (Аз куҷо) <span class="text-red-400">*</span>
                        </label>
                        <select name="from_station_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500">
                            <option value="">Интихоб кунед...</option>
                            <?php foreach ($stations as $st): ?>
                                <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['city']) ?> (<?= htmlspecialchars($st['name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Истгоҳи ниҳоӣ (Ба куҷо) <span class="text-red-400">*</span>
                        </label>
                        <select name="to_station_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500">
                            <option value="">Интихоб кунед...</option>
                            <?php foreach ($stations as $st): ?>
                                <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['city']) ?> (<?= htmlspecialchars($st['name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                                Масофа (км)
                            </label>
                            <input type="number" step="0.1" name="distance_km" placeholder="340" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500 font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                                Давомнокӣ
                            </label>
                            <input type="text" name="duration_hours" placeholder="4 соат 45 дақ" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-emerald w-full py-3 text-xs font-bold shadow-lg shadow-emerald-500/20">
                        <i class="fa-solid fa-check"></i> Сабти хатсайр
                    </button>
                </form>
            </div>
        </div>

        <!-- Рӯйхати хатсайрҳо -->
        <div class="lg:col-span-8">
            <div class="glass-panel p-6 border-white/10">
                <h2 class="text-base font-bold text-white mb-4">Хатсайрҳои мавҷуда (<?= count($routes) ?>)</h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-900/80 text-slate-400 uppercase font-mono border-b border-white/5">
                            <tr>
                                <th class="p-3">Номи хатсайр</th>
                                <th class="p-3">Самт</th>
                                <th class="p-3">Масофа</th>
                                <th class="p-3">Вақти сафар</th>
                                <th class="p-3 text-right">Амал</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($routes as $r): ?>
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="p-3 font-bold text-white"><?= htmlspecialchars($r['route_name']) ?></td>
                                    <td class="p-3 font-semibold text-cyan-400">
                                        <?= htmlspecialchars($r['from_city']) ?> → <?= htmlspecialchars($r['to_city']) ?>
                                    </td>
                                    <td class="p-3 font-mono"><?= $r['distance_km'] ?> км</td>
                                    <td class="p-3"><?= htmlspecialchars($r['duration_hours']) ?></td>
                                    <td class="p-3 text-right">
                                        <form method="POST" action="" onsubmit="return confirm('Оё ин хатсайрро нест кардан мехоҳед?');" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
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

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
