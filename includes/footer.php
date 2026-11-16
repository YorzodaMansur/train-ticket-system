    </main>

    <!-- Поварақи саҳифа (Footer) -->
    <footer class="mt-16 bg-[#090e17] border-t border-white/5 py-10 text-slate-400 text-sm">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fa-solid fa-train text-cyan-400 text-xl"></i>
                        <span class="font-bold text-white text-base">Роҳи Оҳани Тоҷикистон</span>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed mb-4">
                        Системаи онлайнӣ барои фармоиш ва хариди чиптаҳои қатораҳои байнишаҳрӣ бо интихоби ҷой дар вагонҳо.
                    </p>
                    <div class="flex items-center gap-2 text-xs text-cyan-400 font-mono">
                        <i class="fa-solid fa-shield-halved"></i> Ҳимояи 15-дақиқагии ҷойҳо
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold text-white mb-3">Самтҳои асосӣ</h4>
                    <ul class="space-y-1.5 text-xs text-slate-400">
                        <li><a href="/train_ticket_system/trips.php?from=1&to=2" class="hover:text-cyan-400 transition">Душанбе — Хуҷанд</a></li>
                        <li><a href="/train_ticket_system/trips.php?from=2&to=1" class="hover:text-cyan-400 transition">Хуҷанд — Душанбе</a></li>
                        <li><a href="/train_ticket_system/trips.php?from=1&to=3" class="hover:text-cyan-400 transition">Душанбе — Бохтар</a></li>
                        <li><a href="/train_ticket_system/trips.php?from=1&to=4" class="hover:text-cyan-400 transition">Душанбе — Кӯлоб</a></li>
                        <li><a href="/train_ticket_system/trips.php?from=1&to=4" class="hover:text-cyan-400 transition">Хуҷанд — Панҷакент</a></li>

                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold text-white mb-3">Намуди вагонҳо</h4>
                    <ul class="space-y-1.5 text-xs text-slate-400">
                        <li><span class="text-emerald-400 font-semibold">СВ / Люкс</span> — 18 ҷой, бароҳатии муосир</li>
                        <li><span class="text-blue-400 font-semibold">Купе</span> — 36 ҷой, қисмҳои 4-нафара</li>
                        <li><span class="text-amber-400 font-semibold">Плацкарт</span> — 54 ҷой, нархи дастрас</li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold text-white mb-3">Дастгирии техникӣ</h4>
                    <p class="text-xs text-slate-400 mb-2">Маркази ягонаи алоқа ва хадамоти чиптафурӯшӣ:</p>
                    <div class="font-mono text-cyan-400 font-semibold mb-1">
                        <i class="fa-solid fa-phone mr-1"></i> +992 (99) 299-07-78
                    </div>
                    <div class="font-mono text-slate-400 text-xs">
                        <i class="fa-solid fa-envelope mr-1"></i> yorovmansur55@gmail.com
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-white/5 flex flex-col md:flex-row items-center justify-between text-xs text-slate-500 gap-4">
                <p>© <?= date('Y') ?> Кори курсӣ аз фанни «Технология ва забонҳои интернет-барномасозӣ». Ҳамаи ҳуқуқҳо маҳфузанд.</p>
                <div class="flex gap-4">
                    <span>Пардохти амн: Корти Миллӣ / Алиф / Душанбе Сити</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Скрипти асосӣ -->
    <script src="/train_ticket_system/assets/js/main.js"></script>
</body>
</html>
