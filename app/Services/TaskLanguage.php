<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TaskLanguage
{
    private ?array $dictionary = null;
    private ?array $translations = null;

    public function dictionary(): array
    {
        return $this->dictionary ??= json_decode(file_get_contents(resource_path('data/task-language.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public function translations(): array
    {
        if ($this->translations !== null) return $this->translations;
        $this->translations = [];
        if (Schema::hasTable('report_task_translations')) {
            foreach (DB::table('report_task_translations')->get() as $row) {
                $this->translations[$row->normalized_key] = ['label' => $row->label, 'keywords' => json_decode($row->keywords, true) ?? []];
            }
        }
        return $this->translations;
    }

    public function describe(string $title): array
    {
        $override = $this->translations()[ReportTasks::key($title)] ?? null;
        if ($override) return $override + ['label_status' => 'reviewed'];
        $dictionary = $this->dictionary();
        $normalized = mb_strtolower(ReportTasks::title($title));
        if (isset($dictionary['titles'][$normalized])) {
            return ['label' => $dictionary['titles'][$normalized], 'keywords' => [], 'label_status' => 'reviewed'];
        }
        $phrases = $dictionary['phrases'];
        uksort($phrases, fn ($a, $b) => strlen($b) <=> strlen($a));
        $pattern = '/\b('.implode('|', array_map(fn ($phrase) => preg_quote($phrase, '/'), array_keys($phrases))).')\b/iu';
        $label = preg_replace_callback($pattern, fn ($match) => $phrases[mb_strtolower($match[0])], $normalized);
        $label = ReportTasks::title($label);
        return ['label' => ucfirst($label ?: $title), 'keywords' => [], 'label_status' => $label === $normalized ? 'original' : 'suggested'];
    }

    private function words(string $value): array
    {
        $words = preg_split('/[^a-z0-9]+/', strtolower(Str::ascii($value)), -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_diff($words, ['dan', 'yang', 'di', 'ke', 'dari', 'untuk', 'dengan', 'and', 'a', 'an', 'the', 'to', 'of', 'on', 'in', 'for']));
    }

    public function score(string $search, array $option): int
    {
        if (trim($search) === '') return 1;
        $aliases = [];
        foreach ($this->dictionary()['synonyms'] as $concept => $words) {
            foreach ($words as $word) $aliases[$word] = $concept;
        }
        $canonical = fn ($word) => $aliases[$word] ?? $word;
        $words = $this->words($option['title'].' '.$option['label'].' '.implode(' ', $option['keywords'] ?? []));
        $terms = array_unique(array_merge($words, array_map($canonical, $words)));
        $query = array_unique(array_map($canonical, $this->words($search)));
        if (! $query) return 1;
        $score = 0;
        foreach ($query as $word) {
            $best = 0;
            foreach ($terms as $term) {
                if ($term === $word) $best = max($best, 5);
                elseif (strlen($word) >= 3 && str_contains($term, $word)) $best = max($best, 3);
                elseif (strlen($word) >= 4 && abs(strlen($word) - strlen($term)) <= 1 && levenshtein($word, $term) <= 1) $best = max($best, 1);
            }
            if (! $best) return 0;
            $score += $best;
        }
        return $score;
    }
}
