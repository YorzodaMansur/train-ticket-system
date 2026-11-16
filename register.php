<?php
define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Бақайдгирии мусофири нав';

if (isLoggedIn()) {
    header('Location: /train_ticket_system/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $passport = trim($_POST['passport_series'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($fullName) || empty($email) || empty($phone) || empty($passport) || empty($password)) {
        $error = 'Лутфан ҳамаи майдонҳоро пур кунед!';
    } elseif ($password !== $confirmPassword) {
        $error = 'Паролҳо мувофиқат намекунанд!';
    } elseif (strlen($password) < 6) {
        $error = 'Парол бояд на камтар аз 6 аломат бошад!';
    } else {
        // Санҷиши мавҷудияти email
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->rowCount() > 0) {
            $error = 'Ин суроғаи почтаи электронӣ аллакай сабт шудааст!';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO users (full_name, email, phone, passport_series, password, role) 
                VALUES (?, ?, ?, ?, ?, 'passenger')
            ");
            $stmt->execute([$fullName, $email, $phone, $passport, $hashedPassword]);

            $newUserId = $pdo->lastInsertId();
            
            // Воридшавии худкор
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user'] = [
                'id' => $newUserId,
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'passport_series' => $passport,
                'role' => 'passenger'
            ];

            setFlash('success', 'Шумо бомуваффақият сабти ном шудед!');
            header('Location: /train_ticket_system/index.php');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container mx-auto px-4 py-12 flex items-center justify-center">
    <div class="w-full max-w-lg">
        
        <div class="glass-panel p-8 border-cyan-500/20 shadow-2xl">
            <div class="text-center mb-6">
                <div class="w-14 h-14 rounded-2xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center text-2xl mx-auto mb-3">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h1 class="text-2xl font-bold text-white">Бақайдгирии мусофир</h1>
                <p class="text-xs text-slate-400 mt-1">Ҳисоби шахсӣ барои фармоиши осони чиптаҳо</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="p-3.5 rounded-xl bg-red-500/15 border border-red-500/30 text-red-200 text-xs mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-red-400"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Ному насаби пурра <span class="text-red-400">*</span>
                    </label>
                    <input type="text" name="full_name" required placeholder="Масалан: Содиқов Ҷамшед" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Почтаи электронӣ <span class="text-red-400">*</span>
                        </label>
                        <input type="email" name="email" required placeholder="nom@mail.tj" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Рақами телефон <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="phone" required placeholder="+992 90 123 4567" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Силсила ва рақами шиноснома (Паспорт) <span class="text-red-400">*</span>
                    </label>
                    <input type="text" name="passport_series" required placeholder="Масалан: A12345678" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500 font-mono">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Парол <span class="text-red-400">*</span>
                        </label>
                        <input type="password" name="password" required placeholder="••••••••" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Тасдиқи парол <span class="text-red-400">*</span>
                        </label>
                        <input type="password" name="confirm_password" required placeholder="••••••••" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-cyan-500">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full py-3.5 text-sm font-bold shadow-lg shadow-cyan-500/20 mt-2">
                    <i class="fa-solid fa-check"></i> Бақайд гирифтан
                </button>
            </form>

            <div class="mt-6 pt-4 border-t border-white/5 text-center text-xs text-slate-400">
                Аллакай ҳисоб доред? 
                <a href="/train_ticket_system/login.php" class="text-cyan-400 hover:text-cyan-300 font-semibold ml-1">
                    Ворид шавед
                </a>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
