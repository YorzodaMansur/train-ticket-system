<?php
if (!defined('APP_INIT')) {
    require_once __DIR__ . '/../config/db.php';
}
$currentUser = currentUser();
?>
<!DOCTYPE html>
<html lang="tg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?>Роҳи Оҳани Тоҷикистон — Фурӯши чиптаҳои қатора</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Муосиртарин стилҳо -->
    <link rel="stylesheet" href="/train_ticket_system/assets/css/style.css">
</head>
<body class="bg-[#070d18] text-slate-100 min-h-screen flex flex-col font-sans">

    <!-- Сарлавҳаи асосӣ (Header / Navbar) -->
    <header class="sticky top-0 z-50 bg-[#0b1329]/80 backdrop-blur-xl border-b border-white/10 shadow-lg">
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            
            <!-- Логотип ва Номи ширкат -->
            <a href="/train_ticket_system/index.php" class="flex items-center gap-3 group">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-cyan-500 to-blue-600 flex items-center justify-center text-white shadow-lg shadow-cyan-500/30 group-hover:scale-105 transition">
                    <i class="fa-solid fa-train-subway text-xl"></i>
                </div>
                <div>
                    <span class="text-xl font-extrabold tracking-tight bg-gradient-to-r from-white via-slate-100 to-cyan-400 bg-clip-text text-transparent">
                        Роҳи Оҳани Тоҷикистон
                    </span>
                    <span class="block text-xs text-cyan-400 font-semibold tracking-widest uppercase">
                        Чиптаҳои байнишаҳрӣ 2026-2027
                    </span>
                </div>
            </a>

            <!-- Менюи пайвандҳо -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="/train_ticket_system/index.php" class="hover:text-cyan-400 transition flex items-center gap-2">
                    <i class="fa-solid fa-house text-slate-400"></i> Асосӣ
                </a>
                <a href="/train_ticket_system/trips.php" class="hover:text-cyan-400 transition flex items-center gap-2">
                    <i class="fa-solid fa-route text-slate-400"></i> Ҷадвали қатнавҳо
                </a>
                
                <?php if (isLoggedIn()): ?>
                    <a href="/train_ticket_system/my_tickets.php" class="hover:text-cyan-400 transition flex items-center gap-2">
                        <i class="fa-solid fa-ticket text-slate-400"></i> Чиптаҳои ман
                    </a>

                    <?php if ($currentUser['role'] === 'cashier' || $currentUser['role'] === 'admin'): ?>
                        <a href="/train_ticket_system/cashier/index.php" class="text-amber-400 hover:text-amber-300 transition flex items-center gap-1.5 font-semibold bg-amber-500/10 px-3 py-1.5 rounded-lg border border-amber-500/20">
                            <i class="fa-solid fa-cash-register"></i> Касса
                        </a>
                    <?php endif; ?>

                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <a href="/train_ticket_system/admin/index.php" class="text-cyan-400 hover:text-cyan-300 transition flex items-center gap-1.5 font-semibold bg-cyan-500/10 px-3 py-1.5 rounded-lg border border-cyan-500/20">
                            <i class="fa-solid fa-sliders"></i> Панели Маъмур
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>

            <!-- Қисмати профил / Вход -->
            <div class="flex items-center gap-3">
                <?php if (isLoggedIn()): ?>
                    <div class="hidden sm:flex flex-col text-right">
                        <span class="text-sm font-semibold text-slate-200"><?= htmlspecialchars($currentUser['full_name']) ?></span>
                        <span class="text-xs text-cyan-400 uppercase font-mono font-bold">
                            <?php 
                                if ($currentUser['role'] === 'admin') echo '★ Маъмур';
                                elseif ($currentUser['role'] === 'cashier') echo '♦ Кассир';
                                else echo '● Мусофир';
                            ?>
                        </span>
                    </div>
                    <a href="/train_ticket_system/logout.php" class="p-2.5 rounded-xl bg-slate-800 hover:bg-red-500/20 hover:text-red-400 text-slate-300 transition border border-white/5" title="Баромад">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </a>
                <?php else: ?>
                    <a href="/train_ticket_system/login.php" class="text-sm font-semibold text-slate-300 hover:text-white px-3 py-2 transition">
                        Воридшавӣ
                    </a>
                    <a href="/train_ticket_system/register.php" class="btn btn-primary text-sm !py-2 !px-4">
                        <i class="fa-solid fa-user-plus text-xs"></i> Бақайдгирӣ
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </header>

    <!-- Огоҳиномаҳо (Flash Messages) -->
    <div class="container mx-auto px-4 mt-4">
        <?php if ($flashError = getFlash('error')): ?>
            <div class="p-4 rounded-xl bg-red-500/15 border border-red-500/30 text-red-200 flex items-center justify-between mb-4 animate-fade">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-red-400 text-lg"></i>
                    <span><?= htmlspecialchars($flashError) ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-200"><i class="fa-solid fa-xmark"></i></button>
            </div>
        <?php endif; ?>

        <?php if ($flashSuccess = getFlash('success')): ?>
            <div class="p-4 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-200 flex items-center justify-between mb-4 animate-fade">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-400 text-lg"></i>
                    <span><?= htmlspecialchars($flashSuccess) ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-200"><i class="fa-solid fa-xmark"></i></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Асоси саҳифа -->
    <main class="flex-grow">
