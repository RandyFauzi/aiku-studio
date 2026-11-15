<?php

namespace App\Http\Controllers;

use App\Models\PayrollStatus;
use App\Models\Payslip;
use App\Services\PayrollCalculator;
use App\Services\PayrollFiles;
use App\Services\PayrollPaymentStatus;
use App\Services\PayrollPeriod;
use App\Services\PayslipPdf;
use App\Services\PayslipWriter;
use App\Services\WorkerSlipArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class PayslipController extends Controller
{
    private function requireAdministrator(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403, 'Informasi penghasilan hanya tersedia untuk admin.');
    }

    private function validateArchiveAccess(Request $request, Payslip $slip): void
    {
        if ($request->user()->role === 'admin') return;
        abort_unless(app(WorkerSlipArchive::class)->canView($request->user(), $slip), 403, 'Slip ini belum tersedia dalam arsip Anda.');
    }

    public function mine(Request $request, WorkerSlipArchive $archive)
    {
        abort_unless($request->user()->role === 'contributor', 403);
        $slips = $archive->forUser($request->user());
        return response()->json(['slips' => $slips->map(fn ($slip) => $slip->only(['id', 'employee_name', 'period', 'total_thp', 'status', 'created_at']))])
            ->header('Cache-Control', 'private, no-store');
    }

    public function index(PayslipWriter $writer)
    {
        $slips = Payslip::orderByDesc('id')->get();
        $counts = $slips->whereNull('superseded_by_id')->groupBy(fn ($slip) => $writer->key($slip->employee_name, $slip->period))->map->count();
        return $slips->each(fn ($slip) => $slip->setAttribute('duplicate_count', $slip->superseded_by_id ? 0 : $counts[$writer->key($slip->employee_name, $slip->period)]));
    }

    public function show(Payslip $payslip)
    {
        return $payslip;
    }

    private function fields(Request $request): array
    {
        return $request->validate([
            'employee_name' => 'required|string|max:255',
            'period' => 'required|string|max:255',
            'bonus' => 'sometimes|nullable|integer|min:0|max:100000000000',
            'status' => 'sometimes|in:belum,sudah',
            'is_locked' => 'sometimes|nullable|boolean',
            'pending_carry_over_amount' => 'sometimes|nullable|integer|min:0',
            'pending_carry_over_hours' => 'sometimes|nullable|numeric|min:0',
            'pending_carry_over_data' => 'sometimes|nullable|array',
            'evidence' => 'present|array|max:366',
            'evidence.*.notes' => 'nullable|string|max:10000',
            'evidence.*.tasks.*.notes' => 'nullable|string|max:10000',
            'evidence.*.tasks.*.time_str' => 'nullable|string|max:64',
            'evidence.*.date' => 'required|date_format:Y-m-d',
            'evidence.*.approved_mins' => 'nullable|numeric|min:0',
            'evidence.*.rejected_mins' => 'nullable|numeric|min:0',
            'evidence.*.pending_mins' => 'nullable|numeric|min:0',
            'evidence.*.total_video_mins' => 'nullable|numeric|min:0',
            'evidence.*.tasks' => 'sometimes|array|max:1000',
            'evidence.*.tasks.*.task_name' => 'nullable|string|max:1000',
            'evidence.*.tasks.*.approved_mins' => 'nullable|numeric|min:0',
            'evidence.*.tasks.*.rejected_mins' => 'nullable|numeric|min:0',
            'evidence.*.tasks.*.pending_mins' => 'nullable|numeric|min:0',
            'evidence.*.tasks.*.total_video_mins' => 'nullable|numeric|min:0',
            'evidence.*.image_urls' => 'sometimes|array|max:10',
            'evidence.*.image_urls.*' => 'string|regex:#^/evidence_images/[A-Za-z0-9_./-]+$#',
        ]);
    }

    public function store(Request $request, PayrollCalculator $calculator, PayslipWriter $writer)
    {
        $fields = $this->fields($request);
        $fields['employee_name'] = trim($fields['employee_name']);
        $fields['period'] = $writer->period($fields['period']);
        $slip = DB::transaction(function () use ($fields, $calculator, $writer) {
            $writer->lock($fields['employee_name']);
            // A repeated POST never overwrites an existing snapshot or creates another version.
            if ($existing = $writer->unique($fields['employee_name'], $fields['period'])) return $existing;
            $status = $fields['status'] ?? PayrollStatus::where('worker_name', $fields['employee_name'])->where('period', $fields['period'])->value('status') ?? 'belum';
            [$carryAmount, $carryData] = $writer->carry($fields);
            $slip = Payslip::create(array_merge([
                'employee_name' => $fields['employee_name'], 'period' => $fields['period'],
                'active_key' => $writer->key($fields['employee_name'], $fields['period']),
                'status' => $status, 'is_locked' => $status === 'sudah' || ($fields['is_locked'] ?? false),
            ], $calculator->calculate($fields['evidence'], (int) ($fields['bonus'] ?? 0), $carryAmount, $carryData)));
            app(PayrollPaymentStatus::class)->set($slip->employee_name, $slip->period, $status);
            return $slip;
        }, 3);
        return response()->json(['success' => true, 'message' => 'Slip tersimpan', 'data' => ['id' => $slip->id]]);
    }

    public function update(Request $request, Payslip $payslip, PayrollCalculator $calculator, PayslipWriter $writer)
    {
        $fields = $this->fields($request);
        DB::transaction(function () use ($fields, $payslip, $calculator, $writer) {
            $writer->lock($payslip->employee_name);
            $payslip->refresh();
            $writer->editable($payslip);
            abort_unless($writer->key($fields['employee_name'], $fields['period']) === $writer->key($payslip->employee_name, $payslip->period), 422, 'Pekerja/periode slip tidak dapat dipindahkan. Buat slip untuk periode tujuan.');
            $writer->unique($payslip->employee_name, $payslip->period);
            [$carryAmount, $carryData] = $writer->carry($fields, $payslip);
            $status = $fields['status'] ?? $payslip->status;
            $payslip->update(array_merge([
                'status' => $status,
                'is_locked' => $status === 'sudah' || ($fields['is_locked'] ?? false),
                'active_key' => $writer->key($payslip->employee_name, $payslip->period),
                'file_path' => null,
            ], $calculator->calculate($fields['evidence'], (int) ($fields['bonus'] ?? $payslip->bonus), $carryAmount, $carryData)));
            app(PayrollPaymentStatus::class)->set($payslip->employee_name, $payslip->period, $payslip->status);
        }, 3);
        return response()->json(['success' => true, 'id' => $payslip->id]);
    }

    public function destroy(Payslip $payslip, PayslipWriter $writer)
    {
        DB::transaction(function () use ($payslip, $writer) {
            $writer->lock($payslip->employee_name);
            $payslip->refresh();
            $writer->editable($payslip);
            abort_if(Payslip::where('superseded_by_id', $payslip->id)->exists(), 409, 'Slip utama memiliki arsip duplikat; tidak dapat dihapus.');
            $payslip->delete();
        }, 3);
        return response()->json(['success' => true]);
    }

    public function resolveDuplicates(Request $request, Payslip $payslip, PayslipWriter $writer)
    {
        $writer->resolveDuplicates($payslip, $request->user()->id);
        return response()->json(['success' => true, 'id' => $payslip->id]);
    }

    public function status(Request $request, Payslip $payslip)
    {
        abort_if($payslip->superseded_by_id !== null, 409, 'Arsip duplikat tidak dapat diubah.');
        $data = $request->validate(['status' => 'required|in:belum,sudah']);
        DB::transaction(function () use ($data, $payslip) {
            app(PayslipWriter::class)->lock($payslip->employee_name);
            $payslip->refresh();
            abort_if($payslip->superseded_by_id !== null, 409, 'Arsip duplikat tidak dapat diubah.');
            app(PayrollPaymentStatus::class)->set($payslip->employee_name, $payslip->period, $data['status']);
            $payslip->refresh();
        }, 3);

        return response()->json(['success' => true, 'id' => $payslip->id, 'status' => $payslip->status]);
    }

    public function page(Request $request, Payslip $payslip, PayrollCalculator $calculator)
    {
        $this->validateArchiveAccess($request, $payslip);

        return view('slips.web', $request->user()->role === 'admin' ? $calculator->displaySlip($payslip) : $payslip->toArray());
    }

    public function live(Request $request, string $worker, PayrollPeriod $resolver, PayrollCalculator $calculator)
    {
        $this->requireAdministrator($request);

        return view('slips.web', $calculator->liveSlip($resolver->resolve($request->query()), $worker));
    }

    public function export(Request $request, Payslip $payslip, PayrollCalculator $calculator, PayslipPdf $pdf)
    {
        $this->validateArchiveAccess($request, $payslip);

        if ($request->user()->role !== 'admin' && $payslip->file_path && preg_match('#^/pdf_slips/[A-Za-z0-9_. -]+\.pdf$#D', $payslip->file_path)
            && Storage::disk('local')->exists('payroll'.$payslip->file_path)) {
            return response()->json(['success' => true, 'url' => $payslip->file_path, 'fileName' => basename($payslip->file_path)]);
        }

        $result = $pdf->export($request->user()->role === 'admin' ? $calculator->displaySlip($payslip) : $payslip->toArray());
        $payslip->update(['file_path' => $result['url']]);

        return response()->json($result);
    }

    public function exportLive(Request $request, string $worker, PayrollPeriod $resolver, PayrollCalculator $calculator, PayslipPdf $pdf)
    {
        $this->requireAdministrator($request);

        return response()->json($pdf->export($calculator->liveSlip($resolver->resolve($request->query()), $worker)));
    }

    public function upload(Request $request)
    {
        $request->validate(['images' => 'required|array|min:1|max:10', 'images.*' => 'required|image|mimes:jpeg,jpg,png,webp,gif|max:10240',
            'worker_name' => 'nullable|string|max:255', 'date' => 'nullable|date_format:Y-m-d']);
        $worker = preg_replace('/[^a-zA-Z0-9]/', '_', $request->input('worker_name', 'Unknown'));
        $date = $request->input('date', 'no_date');
        $urls = [];
        foreach ($request->file('images') as $file) {
            $name = bin2hex(random_bytes(16)).'.'.$file->extension();
            $file->storeAs('payroll/evidence_images/'.$worker.'/'.$date, $name, 'local');
            $urls[] = '/evidence_images/'.$worker.'/'.$date.'/'.$name;
        }

        return response()->json(['success' => true, 'urls' => $urls]);
    }

    public function file(Request $request, string $type, string $path)
    {
        abort_unless(in_array($type, ['pdf_slips', 'evidence_images']) && ! str_contains($path, '..') && preg_match('#^[a-zA-Z0-9_./ -]+$#D', $path), 404);
        $disk = Storage::disk('local');
        $key = 'payroll/'.$type.'/'.$path;
        abort_unless($disk->exists($key), 404);
        if ($request->user()->role !== 'admin') {
            $url = '/'.$type.'/'.$path;
            $allowed = app(WorkerSlipArchive::class)->forUser($request->user())->contains(function ($slip) use ($type, $url) {
                return $type === 'pdf_slips' ? $slip->file_path === $url : in_array($url, app(PayrollFiles::class)->evidenceUrls($slip->evidence_data ?? []), true);
            });
            abort_unless($allowed, 403, 'File ini belum tersedia dalam arsip Anda.');
        }
        $mime = $disk->mimeType($key);
        abort_unless(in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp']), 404);

        return response()->file($disk->path($key), ['Content-Type' => $mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function email(Request $request, Payslip $payslip, PayrollCalculator $calculator, PayslipPdf $pdf)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        abort_if(in_array(config('mail.default'), ['log', 'array']), 422, 'Konfigurasi SMTP belum diatur; email belum dikirim.');
        $result = $pdf->export($calculator->displaySlip($payslip));
        Mail::raw('Halo '.$payslip->employee_name.', terlampir slip pembayaran AIKu Studio untuk '.$payslip->period.'.', function ($message) use ($data, $payslip, $result) {
            $message->to($data['email'])->subject('Payslip AIKu Studio: '.$payslip->period.' - '.$payslip->employee_name)
                ->attach(Storage::disk('local')->path('payroll'.$result['url']), ['as' => $result['fileName'], 'mime' => 'application/pdf']);
        });

        return response()->json(['success' => true]);
    }
}
