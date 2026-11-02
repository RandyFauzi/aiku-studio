"""One-time source migration. Does not modify the legacy project."""
from pathlib import Path
import sys, re, shutil, subprocess, json

source = Path(sys.argv[1])
target = Path(__file__).resolve().parents[1]
panel = target / 'resources/views/panel'
panel.mkdir(parents=True, exist_ok=True)
assets = target / 'public/assets'
assets.mkdir(parents=True, exist_ok=True)
for name in ['style.css', 'robot-banner.png', 'robot-banner.webp']:
    shutil.copy2(source / 'frontend/assets' / name, assets / name)
shutil.copy2(source / 'frontend/favicon.ico', target / 'public/favicon.ico')

csrf = '''<meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.AIKU_CONFIG = { hourlyRate: @json(config('payroll.hourly_rate')) };</script>
    <script src="/assets/session.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])'''
logout = '''<form method="post" action="/logout" class="fixed bottom-3 left-3 z-50">@csrf<button class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-600 shadow" title="Keluar dari akun panel">Keluar akun</button></form>'''
for name in ['index', 'contributors', 'create', 'payslips', 'profit', 'recordings', 'shifts']:
    content = (source / f'frontend/{name}.html').read_text(encoding='utf-8-sig')
    content = content.replace('http://localhost:3000', '')
    content = re.sub(r'\s*<script[^>]+src="https://(?:cdn.tailwindcss.com|cdn.jsdelivr.net)[^"]*"[^>]*></script>', '', content)
    content = re.sub(r'<script>\s*tailwind.config\s*=.*?</script>', '', content, flags=re.S)
    content = content.replace('</head>', csrf + '\n</head>')
    content = content.replace('</body>', logout + '\n</body>')
    content = content.replace('href="assets/', 'href="/assets/').replace('src="assets/', 'src="/assets/')
    if name == 'recordings':
        content = re.sub(r'\s*<!-- 2\. Tombol Mematikan.*?</button>', '', content, flags=re.S)
        content = re.sub(r'\s*confirmShutdown\(\)\s*\{.*?\n\s*async initApp\(\)', '\n\n                async initApp()', content, flags=re.S)
        content = content.replace('Data baru otomatis masuk ke Web & Excel!', 'Sinkronisasi dijadwalkan melalui server.')
        content = content.replace('terbaru telah tersinkronkan ke Web & Excel.', 'terbaru telah tersinkronkan ke database web.')
        content = content.replace('3. Excel Sync', '3. Database Sync')
    if name == 'payslips':
        content = content.replace('day.approved_hours * 50000', 'day.approved_hours * window.AIKU_CONFIG.hourlyRate')
    (panel / f'{name}.blade.php').write_text(content, encoding='utf-8')

app = (source / 'frontend/assets/app.js').read_text(encoding='utf-8-sig').replace('http://localhost:3000', '')
app = app.replace('(totalMins / 60) * 50000', '(totalMins / 60) * window.AIKU_CONFIG.hourlyRate')
# Replace opaque Apps Script sending with an authenticated SMTP endpoint and an honest result.
start = app.index('                const pdfRes =', app.index('let emailTarget'))
end = app.index("                Swal.fire('Terkirim!'", start)
end = app.index('\n', end)
app = app[:start] + '''                const response = await fetch(`/api/payslips/${slip.id}/email`, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: emailTarget })
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Email belum dikirim. Periksa konfigurasi SMTP.');
                Swal.fire('Terkirim!', 'Slip PDF berhasil dikirim melalui SMTP.', 'success');''' + app[end:]
app = app.replace("'Terjadi kesalahan saat menghubungi Apps Script atau membuat PDF.'", "error.message || 'Gagal mengirim email.'")
app = app.replace('Meneruskan file PDF ke Google Apps Script...', 'Mengirim slip melalui SMTP...')
app = app.replace('Mengambil data dari server dan menyusun PDF...', 'Menyiapkan slip pembayaran...')
# Persist financial variables on the server; localStorage remains a browser cache.
app = app.replace("            // Watch sidebarOpen in profitData", """            try {
                const res = await fetch('/api/finance/settings');
                const data = await res.json();
                if (res.ok && data.settings) Object.assign(this, data.settings);
            } catch (e) { console.error('Gagal memuat pengaturan profit', e); }
            // Watch sidebarOpen in profitData""")
needle = "            localStorage.setItem('aiku_op_expense', this.operationalExpense);"
app = app.replace(needle, needle + """
            clearTimeout(this._saveFinanceTimer);
            this._saveFinanceTimer = setTimeout(async () => {
                try {
                    const res = await fetch('/api/finance/settings', { method: 'POST', headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({rateMode: this.rateMode, clientRateUsd: this.clientRateUsd, kursUsd: this.kursUsd,
                            clientRateIdr: this.clientRateIdr, operationalExpense: this.operationalExpense}) });
                    if (!res.ok) throw new Error('Pengaturan profit belum tersimpan di server.');
                } catch (error) { Swal.fire('Gagal menyimpan', error.message, 'error'); }
            }, 500);""")
(assets / 'app.js').write_text(app, encoding='utf-8')

server = (source / 'backend/server.js').read_text(encoding='utf-8')
default = re.search(r'const defaultShiftConfig = (.*?);\s*// GET /api/shifts/config', server, re.S).group(1)
result = subprocess.run(['node', '-e', 'process.stdout.write(JSON.stringify(' + default + '))'], capture_output=True, check=True, encoding='utf-8')
data_dir = target / 'resources/data'
data_dir.mkdir(parents=True, exist_ok=True)
(data_dir / 'default-shifts.json').write_text(json.dumps(json.loads(result.stdout), ensure_ascii=False, indent=2), encoding='utf-8')
print('Migrated 7 pages, frontend logic, assets, and default shift settings.')
