<?php
define('APP_INIT', true);
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$pageTitle = 'Идораи Истгоҳҳо';

// Амалиёти POST: Илова кардан ё таҳрир
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $city = trim($_POST['city'] ?? '');

        if (!empty($name) && !empty($code) && !empty($city)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO stations (name, code, city, is_active) VALUES (?, ?, ?, 1)");
                $stmt->execute([$name, $code, $city]);
                setFlash('success', 'Истгоҳи нав бомуваффақият сабт шуд!');
            } catch (Exception $e) {
                setFlash('error', 'Хатогӣ: Коди истгоҳ бояд такрорнашаванда (уникальный) бошад!');
            }
        } else {
            setFlash('error', 'Ҳамаи майдонҳоро пур кунед!');
        }
        header('Location: /train_ticket_system/admin/stations.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM stations WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Истгоҳ ҳазф карда шуд!');
        }
        header('Location: /train_ticket_system/admin/stations.php');
        exit;
    }
}

$stations = $pdo->query("SELECT * FROM stations ORDER BY id DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    
    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="/train_ticket_system/admin/index.php" class="text-xs text-slate-400 hover:text-white transition inline-flex items-center gap-1 mb-2">
                <i class="fa-solid fa-arrow-left"></i> Ба панели асосӣ
            </a>
            <h1 class="text-2xl md:text-3xl font-extrabold text-white">Идораи истгоҳҳои роҳи оҳан</h1>
            <p class="text-xs text-slate-400 mt-1">Илова, таҳрир ва идоракунии вокзалҳо ва шаҳрҳо</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Формаи илова кардани истгоҳ (Сутуни чап) -->
        <div class="lg:col-span-4">
            <div class="glass-panel p-6 border-white/10">
                <h2 class="text-base font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-plus-circle text-cyan-400"></i> Иловаи истгоҳи нав
                </h2>

                <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="action" value="create">

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Номи шаҳр <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="city" required placeholder="Масалан: Истаравшан" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Номи пурраи истгоҳ/вокзал <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="name" required placeholder="Масалан: Истгоҳи роҳи оҳани Истаравшан" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Коди кӯтоҳ (3-4 ҳарф) <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="code" required maxlength="10" placeholder="Масалан: IST" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500 font-mono uppercase">
                    </div>

                    <button type="submit" class="btn btn-primary w-full py-3 text-xs font-bold shadow-lg shadow-cyan-500/20">
                        <i class="fa-solid fa-check"></i> Сабт кардан
                    </button>
                </form>
            </div>
        </div>

        <!-- Рӯйхати истгоҳҳо (Сутуни рост) -->
        <div class="lg:col-span-8">
            <div class="glass-panel p-6 border-white/10">
                <h2 class="text-base font-bold text-white mb-4 flex items-center justify-between">
                    <span>Истгоҳҳои мавҷуда (<?= count($stations) ?>)</span>
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-900/80 text-slate-400 uppercase font-mono border-b border-white/5">
                            <tr>
                                <th class="p-3">ID</th>
                                <th class="p-3">Шаҳр</th>
                                <th class="p-3">Номи вокзал</th>
                                <th class="p-3">Код</th>
                                <th class="p-3">Ҳолат</th>
                                <th class="p-3 text-right">Амал</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($stations as $st): ?>
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="p-3 font-mono text-slate-500"><?= $st['id'] ?></td>
                                    <td class="p-3 font-bold text-white"><?= htmlspecialchars($st['city']) ?></td>
                                    <td class="p-3"><?= htmlspecialchars($st['name']) ?></td>
                                    <td class="p-3">
                                        <span class="badge badge-primary font-mono"><?= htmlspecialchars($st['code']) ?></span>
                                    </td>
                                    <td class="p-3">
                                        <span class="badge badge-emerald">Фаъол</span>
                                    </td>
                                    <td class="p-3 text-right">
                                        <form method="POST" action="" onsubmit="return confirm('Оё мехоҳед ин истгоҳро ҳазф кунед?');" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $st['id'] ?>">
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
