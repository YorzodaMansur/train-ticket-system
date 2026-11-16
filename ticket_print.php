<?php
define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';

$ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
if (!$ticketId) {
    die("Чипта муайян нашудааст.");
}

$stmt = $pdo->prepare("
    SELECT tk.*, 
           t.departure_time, t.arrival_time,
           r.route_name, r.duration_hours, r.distance_km,
           s1.name AS from_station, s1.city AS from_city, s1.code AS from_code,
           s2.name AS to_station, s2.city AS to_city, s2.code AS to_code,
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
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die("Чипта дар система ёфт нашуд.");
}
?>
<!DOCTYPE html>
<html lang="tg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чиптаи қатора — <?= htmlspecialchars($ticket['ticket_number']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .ticket-container {
                box-shadow: none !important;
                border: 2px solid #000000 !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-slate-900 min-h-screen py-10 px-4 font-sans text-slate-800">

    <!-- Панели амалҳо (Барои чоп ва баргаштан) -->
    <div class="max-w-3xl mx-auto mb-6 flex items-center justify-between no-print">
        <a href="/train_ticket_system/my_tickets.php" class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition text-sm">
            <i class="fa-solid fa-arrow-left"></i> Бозгашт ба чиптаҳо
        </a>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm shadow-lg flex items-center gap-2 transition">
                <i class="fa-solid fa-print"></i> Чопи чипта / Сабт ба PDF
            </button>
        </div>
    </div>

    <!-- Корти чиптаи расмӣ (Boarding Pass Format) -->
    <div class="ticket-container max-w-3xl mx-auto bg-white rounded-3xl overflow-hidden shadow-2xl border border-slate-200">
        
        <!-- Сарлавҳаи чипта -->
        <div class="bg-gradient-to-r from-blue-900 to-indigo-900 text-white p-6 md:p-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center text-3xl text-cyan-300">
                    <i class="fa-solid fa-train-subway"></i>
                </div>
                <div>
                    <span class="text-xs uppercase font-semibold tracking-widest text-cyan-300 block">
                        Корхонаи воҳиди давлатии
                    </span>
                    <h1 class="text-2xl font-black tracking-tight">РОҲИ ОҲАНИ ТОҶИКИСТОН</h1>
                    <span class="text-xs text-slate-300">ЧИПТАИ ЭЛЕКТРОНИИ САФАРӢ (ЭЛЕКТРОННЫЙ ПРОЕЗДНОЙ ДОКУМЕНТ)</span>
                </div>
            </div>
            <div class="text-right">
                <span class="text-xs text-cyan-200 block">Рақами чипта:</span>
                <span class="text-xl font-black font-mono tracking-wider"><?= htmlspecialchars($ticket['ticket_number']) ?></span>
                <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                    Пардохт шудааст
                </span>
            </div>
        </div>

        <!-- Асоси чипта -->
        <div class="p-6 md:p-8 space-y-6">
            
            <!-- Маълумоти мусофир -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pb-6 border-b border-slate-200 text-sm">
                <div>
                    <span class="text-xs text-slate-500 uppercase font-semibold block">Ному насаби мусофир:</span>
                    <strong class="text-base text-slate-900"><?= htmlspecialchars($ticket['passenger_name']) ?></strong>
                </div>
                <div>
                    <span class="text-xs text-slate-500 uppercase font-semibold block">Ҳуҷҷат (Шиноснома):</span>
                    <strong class="text-base font-mono text-slate-900"><?= htmlspecialchars($ticket['passenger_passport']) ?></strong>
                </div>
                <div>
                    <span class="text-xs text-slate-500 uppercase font-semibold block">Санаи харид:</span>
                    <span class="text-sm text-slate-700 font-mono"><?= date('d.m.Y H:i', strtotime($ticket['purchased_at'] ?? $ticket['created_at'])) ?></span>
                </div>
            </div>

            <!-- Хатсайр ва вақтҳо -->
            <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-700">
                        <i class="fa-solid fa-train mr-1"></i> Қатораи № <?= htmlspecialchars($ticket['train_number']) ?> (<?= htmlspecialchars($ticket['train_name']) ?>)
                    </span>
                    <span class="text-xs text-slate-500 font-mono">
                        Масофа: <?= $ticket['distance_km'] ?> км | Давомнокӣ: <?= htmlspecialchars($ticket['duration_hours']) ?>
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
                    <!-- Истгоҳи рафтан -->
                    <div class="sm:col-span-5">
                        <span class="text-xs text-slate-400 uppercase font-semibold block">Истгоҳи рафтан:</span>
                        <h2 class="text-xl font-black text-slate-900"><?= htmlspecialchars($ticket['from_city']) ?></h2>
                        <span class="text-xs text-slate-600 block"><?= htmlspecialchars($ticket['from_station']) ?></span>
                        <div class="mt-2 text-2xl font-black text-indigo-700 font-mono">
                            <?= date('H:i', strtotime($ticket['departure_time'])) ?>
                        </div>
                        <span class="text-xs text-slate-500 font-semibold"><?= date('d.m.Y', strtotime($ticket['departure_time'])) ?></span>
                    </div>

                    <!-- Тири мобайнӣ -->
                    <div class="sm:col-span-2 flex flex-col items-center justify-center">
                        <i class="fa-solid fa-arrow-right-long text-2xl text-slate-400"></i>
                    </div>

                    <!-- Истгоҳи расидан -->
                    <div class="sm:col-span-5 text-right sm:text-right">
                        <span class="text-xs text-slate-400 uppercase font-semibold block">Истгоҳи расидан:</span>
                        <h2 class="text-xl font-black text-slate-900"><?= htmlspecialchars($ticket['to_city']) ?></h2>
                        <span class="text-xs text-slate-600 block"><?= htmlspecialchars($ticket['to_station']) ?></span>
                        <div class="mt-2 text-2xl font-black text-indigo-700 font-mono">
                            <?= date('H:i', strtotime($ticket['arrival_time'])) ?>
                        </div>
                        <span class="text-xs text-slate-500 font-semibold"><?= date('d.m.Y', strtotime($ticket['arrival_time'])) ?></span>
                    </div>
                </div>
            </div>

            <!-- Маълумоти вагон ва ҷой -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pb-6 border-b border-slate-200">
                <div class="p-4 bg-slate-100 rounded-xl text-center">
                    <span class="text-xs text-slate-500 uppercase font-semibold block">Вагон</span>
                    <span class="text-2xl font-black text-slate-900 font-mono">№ <?= $ticket['car_number'] ?></span>
                    <span class="text-[11px] text-slate-600 block"><?= carTypeName($ticket['car_type']) ?></span>
                </div>

                <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-xl text-center">
                    <span class="text-xs text-indigo-600 uppercase font-semibold block">Ҷой</span>
                    <span class="text-2xl font-black text-indigo-900 font-mono">№ <?= $ticket['seat_number'] ?></span>
                    <span class="text-[11px] text-indigo-700 block"><?= seatTypeName($ticket['seat_type']) ?></span>
                </div>

                <div class="p-4 bg-slate-100 rounded-xl text-center">
                    <span class="text-xs text-slate-500 uppercase font-semibold block">Усули пардохт</span>
                    <span class="text-sm font-bold text-slate-800 block mt-2">
                        <?php 
                        if ($ticket['payment_method'] === 'alif_mobi') echo 'Алиф Моби';
                        elseif ($ticket['payment_method'] === 'dushanbe_city') echo 'Душанбе Сити';
                        elseif ($ticket['payment_method'] === 'cash') echo 'Нақдӣ (Касса)';
                        else echo 'Корти Миллӣ';
                        ?>
                    </span>
                </div>

                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-center">
                    <span class="text-xs text-emerald-700 uppercase font-semibold block">Нархи чипта</span>
                    <span class="text-xl font-black text-emerald-800 font-mono block mt-1"><?= formatTJS($ticket['price']) ?></span>
                    <span class="text-[10px] text-emerald-600">Бо назардошти ААҲ</span>
                </div>
            </div>

            <!-- Штрих-код ва QR-код барои назорат -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-6 pt-2">
                <div class="space-y-2">
                    <div class="text-xs font-semibold uppercase text-slate-500">Штрих-коди назорати электронӣ:</div>
                    <!-- Намоиши рамзи штрихӣ бо CSS SVG -->
                    <div class="flex items-center gap-1 h-12 bg-white px-2 py-1 border border-slate-300 rounded">
                        <div class="w-1 h-10 bg-black"></div><div class="w-2 h-10 bg-black"></div><div class="w-0.5 h-10 bg-black"></div>
                        <div class="w-1.5 h-10 bg-black"></div><div class="w-3 h-10 bg-black"></div><div class="w-1 h-10 bg-black"></div>
                        <div class="w-0.5 h-10 bg-black"></div><div class="w-2 h-10 bg-black"></div><div class="w-1 h-10 bg-black"></div>
                        <div class="w-2.5 h-10 bg-black"></div><div class="w-1 h-10 bg-black"></div><div class="w-3 h-10 bg-black"></div>
                        <div class="w-0.5 h-10 bg-black"></div><div class="w-2 h-10 bg-black"></div><div class="w-1.5 h-10 bg-black"></div>
                        <div class="w-1 h-10 bg-black"></div><div class="w-3 h-10 bg-black"></div><div class="w-2 h-10 bg-black"></div>
                    </div>
                    <span class="font-mono text-xs text-slate-500 tracking-widest block text-center"><?= htmlspecialchars($ticket['ticket_number']) ?></span>
                </div>

                <!-- QR код -->
                <div class="flex items-center gap-4 bg-slate-50 p-3 rounded-2xl border border-slate-200">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode('ROHI_OHAN_TJ:' . $ticket['ticket_number'] . ':' . $ticket['passenger_passport']) ?>" alt="QR Code" class="w-20 h-20 rounded-lg">
                    <div class="text-xs text-slate-500 max-w-[200px] leading-tight">
                        <strong class="text-slate-800 block mb-1">Санҷиши чипта</strong>
                        Ин QR-кодро ба роҳбалад (проводник) ҳангоми нишастан ба қатора нишон диҳед.
                    </div>
                </div>
            </div>

            <!-- Қоидаҳои сафар -->
            <div class="text-[11px] text-slate-500 border-t border-slate-200 pt-4 leading-relaxed">
                <p><strong>Эзоҳ барои мусофир:</strong> Нишастан ба қатора на дертар аз 20 дақиқа то вақти ҳаракат сурат мегирад. Бо худ доштани аслии шиносномаи шаҳрванд ҳатмист. Баргардонидани чипта мутобиқи қоидаҳои КВД «Роҳи оҳани Тоҷикистон» ба роҳ монда мешавад.</p>
            </div>

        </div>

    </div>

</body>
</html>
