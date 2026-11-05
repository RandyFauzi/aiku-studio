@php
    $rupiah = fn($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $evidence = $evidence_data ?? [];
    $files = app(\App\Services\PayrollFiles::class);
@endphp
<header><p class="eyebrow">AIKU STUDIO · PAYROLL</p><h1>Slip Pembayaran Kontributor</h1><p>Referensi #{{ $id }}</p></header>
<section class="identity"><h2>{{ $employee_name }}</h2><p>Periode: {{ $period }}</p><p>Status pembayaran: <strong>{{ ($status ?? 'belum') === 'sudah' ? 'Sudah Dibayar' : 'Belum Dibayar' }}</strong></p></section>
<table class="summary"><tr><th>Total durasi tercatat</th><td>{{ number_format($total_logged_hours, 2, ',', '.') }} jam</td></tr>
<tr><th>Durasi disetujui</th><td>{{ number_format($total_approved_hours, 2, ',', '.') }} jam</td></tr>
<tr><th>Tarif per jam disetujui</th><td>{{ $rupiah(config('payroll.hourly_rate')) }}</td></tr>
<tr><th>Upah disetujui</th><td>{{ $rupiah($total_thp - ($bonus ?? 0) - ($pending_carry_over_amount ?? 0)) }}</td></tr>
@if(!empty($pending_carry_over_amount) && $pending_carry_over_amount > 0)
<tr><th>Pencairan sisa pending periode lalu</th><td>{{ $rupiah($pending_carry_over_amount) }}</td></tr>
@endif
<tr><th>Bonus</th><td>{{ $rupiah($bonus ?? 0) }}</td></tr>
<tr class="total"><th>Total Take Home Pay</th><td>{{ $rupiah($total_thp) }}</td></tr></table>
<h2>Rincian aktivitas harian</h2>
<p class="note">Durasi pending akan direkonsiliasi setelah hasil QA Atlas diterbitkan. Seluruh waktu rekaman ditampilkan dalam WITA.</p>
@forelse($evidence as $day)
<section class="day"><h3>{{ \Carbon\CarbonImmutable::parse($day['date'])->locale('id')->translatedFormat('l, j M Y') }}</h3>
<table class="tasks"><thead><tr><th>Tugas / waktu WITA</th><th>Total<br>menit</th><th>Approved<br>menit</th><th>Rejected<br>menit</th><th>Pending<br>menit</th></tr></thead><tbody>
@php
    $tasks = !empty($day['tasks']) ? $day['tasks'] : [['task_name' => $day['task_name'] ?? 'Daily Video Log', 'approved_mins' => $day['approved_mins'] ?? 0, 'rejected_mins' => $day['rejected_mins'] ?? 0, 'pending_mins' => $day['pending_mins'] ?? 0, 'total_video_mins' => $day['total_video_mins'] ?? 0, 'notes' => $day['notes'] ?? '']];
@endphp
@foreach($tasks as $task)
<tr><td><strong>{{ $task['task_name'] ?? 'Rekaman Atlas' }}</strong><br><small>{{ $task['time_str'] ?? '' }} {{ $task['notes'] ?? '' }}</small></td>
@foreach(['total_video_mins','approved_mins','rejected_mins','pending_mins'] as $key)<td>{{ number_format((float)($task[$key] ?? 0), 2, ',', '.') }}</td>@endforeach</tr>
@endforeach
</tbody></table>
@foreach($day['image_urls'] ?? (empty($day['image_url']) ? [] : [$day['image_url']]) as $image)
@php($src = !empty($forPdf) ? $files->imageData($image) : ($files->evidencePath($image) ? $image : null))
@if($src)<img class="evidence" src="{{ $src }}" alt="Bukti aktivitas tanggal {{ $day['date'] }}">@endif
@endforeach
</section>
@empty<p>Tidak ada catatan aktivitas pada periode ini.</p>@endforelse
<footer>Dokumen pembayaran AIKu Studio · Seluruh nominal dalam Rupiah.</footer>
