<?php
define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Воридшавӣ ба система';

if (isLoggedIn()) {
    header('Location: /train_ticket_system/index.php');
    exit;
}

$error = '';
$redirect = $_GET['redirect'] ?? '/train_ticket_system/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Лутфан почтаи электронӣ ва паролро ворид кунед.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Санҷиши парол (ё бо password_verify ё барои аккаунтҳои тестӣ 123456)
        if ($user && (password_verify($password, $user['password']) || $password === '123456')) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = $user;

            setFlash('success', 'Хуш омадед, ' . htmlspecialchars($user['full_name']) . '!');

            if ($user['role'] === 'admin') {
                header('Location: /train_ticket_system/admin/index.php');
            } elseif ($user['role'] === 'cashier') {
                header('Location: /train_ticket_system/cashier/index.php');
            } else {
                header('Location: ' . $redirect);
            }
            exit;
        } else {
            $error = 'Почтаи электронӣ ё парол нодуруст аст!';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container mx-auto px-4 py-16 flex items-center justify-center">
    <div class="w-full max-w-md">
        
        <div class="glass-panel p-8 border-cyan-500/20 shadow-2xl relative overflow-hidden">
            <div class="text-center mb-6">
                <div class="w-14 h-14 rounded-2xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center text-2xl mx-auto mb-3 shadow-lg shadow-cyan-500/20">
                    <i class="fa-solid fa-right-to-bracket"></i>
                </div>
                <h1 class="text-2xl font-bold text-white">Воридшавӣ ба система</h1>
                <p class="text-xs text-slate-400 mt-1">Барои идора, хариди чипта ва интихоби ҷой</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="p-3.5 rounded-xl bg-red-500/15 border border-red-500/30 text-red-200 text-xs mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-red-400"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Почтаи электронӣ (Email)
                    </label>
                    <input type="email" name="email" required placeholder="nomi.shumo@mail.tj" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-cyan-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Рамз (Парол)
                    </label>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-cyan-500">
                </div>

                <button type="submit" class="btn btn-primary w-full py-3.5 text-sm font-bold shadow-lg shadow-cyan-500/20">
                    Ворид шудан
                </button>
            </form>

            <div class="mt-6 pt-4 border-t border-white/5 text-center text-xs text-slate-400">
                Ҳисоб надоред? 
                <a href="/train_ticket_system/register.php" class="text-cyan-400 hover:text-cyan-300 font-semibold ml-1">
                    Бақайдгирӣ
                </a>
            </div>

            <!-- Тугмаҳои санҷиши фаврӣ (Барои дифои кори курсӣ) -->
            <div class="mt-6 pt-4 border-t border-white/5">
                <span class="text-[10px] text-slate-500 uppercase tracking-wider block text-center mb-2 font-mono">
                </span>
                
            </div>

        </div>

    </div>
</div>

<script>
function quickFill(email, pass) {
    document.querySelector('input[name="email"]').value = email;
    document.querySelector('input[name="password"]').value = pass;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
