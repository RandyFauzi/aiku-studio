<?php

namespace App\Http\Controllers;

use App\Models\PayrollStatus;
use App\Models\Payslip;
use App\Services\ContributorIdentity;
use App\Services\PayrollCalculator;
use App\Services\PayrollFiles;
use App\Services\PayrollPaymentStatus;
use App\Services\PayrollPeriod;
use App\Services\PayslipPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class PayslipController extends Controller
{
    private function validateSlipOwner(Request $request, string $employeeName): void
    {
        if ($request->user()->role === 'contributor') {
            abort_unless($employeeName === app(ContributorIdentity::class)->payrollName($request->user()), 403, 'Anda tidak berhak mengakses slip ini.');
        }
    }

    public function mine(Request $request, PayrollPeriod $resolver, PayrollCalculator $calculator)
    {
        abort_unless($request->user()->role === 'contributor', 403);
        $workerName = app(ContributorIdentity::class)->payrollName($request->user());
        $validNames = [$workerName];

        $slips = Payslip::whereIn('employee_name', $validNames)
            ->orderByDesc('id')
            ->get();

        $currentPeriod = $resolver->cutoff();
        $liveSlip = null;
        try {
            $liveSlip = $calculator->liveSlip($currentPeriod, $workerName);
        } catch (\Throwable $e) {
            // Live calculation is optional if recordings don't exist
        }

        return response()->json([
            'worker_name' => $workerName,
            'slips' => $slips,
            'current_period' => $currentPeriod,
            'live_slip' => $liveSlip,
        ]);
    }

    public function index()
    {
        return Payslip::orderByDesc('created_at')->get();
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

    public function store(Request $request, PayrollCalculator $calculator)
    {
        $fields = $this->fields($request);
        $slip = DB::transaction(function () use ($fields, $request, $calculator) {
            $status = $fields['status'] ?? PayrollStatus::where('worker_name', $fields['employee_name'])->where('period', $fields['period'])->value('status') ?? 'belum';
            $carryAmount = (int) ($fields['pending_carry_over_amount'] ?? 0);
            $carryData = $fields['pending_carry_over_data'] ?? null;
            $bonus = (int) ($fields['bonus'] ?? 0);
            $isLocked = $fields['is_locked'] ?? ($status === 'sudah');

            $calculated = $calculator->calculate($request->input('evidence', []), $bonus, $carryAmount, $carryData);

            $slip = Payslip::create(array_merge([
                'employee_name' => $fields['employee_name'],
                'period' => $fields['period'],
                'status' => $status,
                'is_locked' => $isLocked,
            ], $calculated));

            app(PayrollPaymentStatus::class)->set($slip->employee_name, $slip->period, $status);

            return $slip;
        });

        return response()->json(['success' => true, 'message' => 'Slip saved successfully', 'data' => ['id' => $slip->id]]);
    }

    public function update(Request $request, Payslip $payslip, PayrollCalculator $calculator)
    {
        $fields = $this->fields($request);
        DB::transaction(function () use ($fields, $request, $payslip, $calculator) {
            $carryAmount = isset($fields['pending_carry_over_amount']) ? (int) $fields['pending_carry_over_amount'] : (int) $payslip->pending_carry_over_amount;
            $carryData = array_key_exists('pending_carry_over_data', $fields) ? $fields['pending_carry_over_data'] : $payslip->pending_carry_over_data;
            $bonus = (int) ($fields['bonus'] ?? $payslip->bonus);
            $status = $fields['status'] ?? $payslip->status;
            $isLocked = $fields['is_locked'] ?? ($status === 'sudah' || $payslip->is_locked);

            $calculated = $calculator->calculate($request->input('evidence', []), $bonus, $carryAmount, $carryData);

            $payslip->update(array_merge([
                'employee_name' => $fields['employee_name'],
                'period' => $fields['period'],
                'status' => $status,
                'is_locked' => $isLocked,
                'file_path' => null,
            ], $calculated));

            app(PayrollPaymentStatus::class)->set($payslip->employee_name, $payslip->period, $payslip->status);
        });

        // Images may be shared by historical slips. Retain files rather than delete references from another slip.
        return response()->json(['success' => true, 'id' => $payslip->id]);
    }

    public function destroy(Payslip $payslip)
    {
        $payslip->delete();

        return response()->json(['success' => true]);
    }

    public function status(Request $request, Payslip $payslip)
    {
        $data = $request->validate(['status' => 'required|in:belum,sudah']);
        DB::transaction(function () use ($data, $payslip) {
            app(PayrollPaymentStatus::class)->set($payslip->employee_name, $payslip->period, $data['status']);
            $payslip->refresh();
        });

        return response()->json(['success' => true, 'id' => $payslip->id, 'status' => $payslip->status]);
    }

    public function page(Request $request, Payslip $payslip, PayrollCalculator $calculator)
    {
        $this->validateSlipOwner($request, $payslip->employee_name);

        return view('slips.web', $calculator->displaySlip($payslip));
    }

    public function live(Request $request, string $worker, PayrollPeriod $resolver, PayrollCalculator $calculator)
    {
        $this->validateSlipOwner($request, $worker);

        return view('slips.web', $calculator->liveSlip($resolver->resolve($request->query()), $worker));
    }

    public function export(Request $request, Payslip $payslip, PayrollCalculator $calculator, PayslipPdf $pdf)
    {
        $this->validateSlipOwner($request, $payslip->employee_name);

        $result = $pdf->export($calculator->displaySlip($payslip));
        $payslip->update(['file_path' => $result['url']]);

        return response()->json($result);
    }

    public function exportLive(Request $request, string $worker, PayrollPeriod $resolver, PayrollCalculator $calculator, PayslipPdf $pdf)
    {
        $this->validateSlipOwner($request, $worker);

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
            $name = app(ContributorIdentity::class)->payrollName($request->user());
            $url = '/'.$type.'/'.$path;
            $slips = Payslip::where('employee_name', $name)->get();
            $allowed = $type === 'pdf_slips'
                ? $slips->contains('file_path', $url)
                : $slips->contains(fn ($slip) => in_array($url, app(PayrollFiles::class)->evidenceUrls($slip->evidence_data ?? []), true));
            if (! $allowed && $type === 'pdf_slips' && $disk->exists($key.'.owner.json')) {
                $owner = json_decode($disk->get($key.'.owner.json'), true);
                $allowed = ($owner['employee_name'] ?? null) === $name
                    && ($owner['contributor_id'] ?? null) === $request->user()->contributor_id;
            }
            abort_unless($allowed, 403, 'Anda tidak berhak mengakses file ini.');
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
