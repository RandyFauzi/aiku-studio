<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>Slip {{ $employee_name }} — AIKu Studio</title><style>@include('slips.style') body{max-width:960px;margin:32px auto;padding:0 20px}</style></head><body>
<nav class="toolbar"><a href="/payslips.html">Kembali</a><a href="{{ $editUrl }}">Edit slip</a><button id="export" type="button">Unduh PDF</button><button onclick="window.print()">Cetak</button></nav>
@include('slips.content')
<script>
document.getElementById('export').addEventListener('click', async function () {
    this.disabled = true; this.textContent = 'Membuat PDF…';
    try {
        const isLive = location.pathname.startsWith('/slip/live/');
        const endpoint = isLive ? '/api/payroll/export-live/' + encodeURIComponent(@json($employee_name)) + location.search : '/api/payslips/' + @json($id) + '/export';
        const response = await fetch(endpoint, {headers: {'Accept':'application/json'}});
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || data.error || 'Gagal membuat PDF');
        const a = document.createElement('a'); a.href = data.url; a.download = data.fileName; a.click();
    } catch (error) { alert(error.message); }
    finally { this.disabled = false; this.textContent = 'Unduh PDF'; }
});
</script></body></html>
