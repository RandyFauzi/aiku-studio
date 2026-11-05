<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PayrollPeriod
{
    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public function cutoff(?CarbonImmutable $date = null): array
    {
        $date = ($date ?? CarbonImmutable::now())->setTimezone('Asia/Makassar');
        $start = $date->startOfMonth()->day(22);
        if ($date->day < 22) {
            $start = $start->subMonthNoOverflow();
        }

        return $this->range($start->toDateString(), $start->addMonthNoOverflow()->day(21)->toDateString());
    }

    public function range(string $start, string $end): array
    {
        try {
            $s = CarbonImmutable::createFromFormat('!Y-m-d', $start, 'Asia/Makassar');
            $e = CarbonImmutable::createFromFormat('!Y-m-d', $end, 'Asia/Makassar');
            if ($s->toDateString() !== $start || $e->toDateString() !== $end || $s->gt($e)) {
                throw new \InvalidArgumentException;
            }
        } catch (\Throwable) {
            throw ValidationException::withMessages(['period' => 'Rentang tanggal tidak valid.']);
        }

        return [
            'id' => $start.'_'.$end,
            'name' => $s->day.' '.self::MONTHS[$s->month - 1].' '.$s->year.' - '.$e->day.' '.self::MONTHS[$e->month - 1].' '.$e->year,
            'startDate' => $start,
            'endDate' => $end,
            'startUtc' => $s->utc()->format('Y-m-d H:i:s'),
            'endUtc' => $e->endOfDay()->utc()->format('Y-m-d H:i:s'),
            'isCurrent' => false,
        ];
    }

    public function resolve(array $input): array
    {
        $input = Validator::make($input, [
            'period' => 'sometimes|nullable|string|max:255',
            'periodId' => 'sometimes|nullable|string|max:255',
            'startDate' => 'required_with:endDate|nullable|date_format:Y-m-d',
            'endDate' => 'required_with:startDate|nullable|date_format:Y-m-d',
        ])->validate();
        $name = $input['period'] ?? '';
        $id = $input['periodId'] ?? '';
        if (! empty($input['startDate']) && ! empty($input['endDate'])) {
            return $this->range($input['startDate'], $input['endDate']);
        }
        if ($id === 'training_2026' || str_contains(strtolower($name), 'training')) {
            return array_replace($this->range('2026-09-18', '2026-09-20'), [
                'id' => 'training_2026', 'name' => 'Training (18 Sep 2026 - 20 Sep 2026)', 'isTraining' => true,
            ]);
        }
        if (preg_match('/^(\d{4}-\d{2}-\d{2})_(\d{4}-\d{2}-\d{2})$/', $id, $m)) {
            return $this->range($m[1], $m[2]);
        }
        if (preg_match('/(\d{1,2})\s+([A-Za-z]+)(?:\s+(\d{4}))?\s*[-–]\s*(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/', $name, $m)) {
            $months = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'mei' => 5, 'may' => 5, 'jun' => 6, 'jul' => 7, 'agu' => 8, 'aug' => 8, 'sep' => 9, 'okt' => 10, 'oct' => 10, 'nov' => 11, 'des' => 12, 'dec' => 12];
            $sm = $months[strtolower(substr($m[2], 0, 3))] ?? null;
            $em = $months[strtolower(substr($m[5], 0, 3))] ?? null;
            if ($sm && $em) {
                $sy = ($m[3] ?? '') !== '' ? (int) $m[3] : (int) $m[6] - ($sm > $em ? 1 : 0);

                return $this->range(sprintf('%04d-%02d-%02d', $sy, $sm, $m[1]), sprintf('%04d-%02d-%02d', $m[6], $em, $m[4]));
            }
        }
        if ($id !== '' || $name !== '') {
            throw ValidationException::withMessages(['period' => 'Periode tidak dikenali.']);
        }

        return $this->cutoff(CarbonImmutable::now('Asia/Makassar')->subMonthNoOverflow());
    }
}
