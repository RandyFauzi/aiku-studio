<?php

namespace App\Http\Controllers;

use App\Models\AdminNote;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $today = CarbonImmutable::now('Asia/Makassar')->toDateString();
        $date = $request->input('date', $today);
        $allDates = $request->boolean('all_dates');

        $query = AdminNote::query()->with([
            'user:id,name,email',
            'assignedTo:id,name,email',
            'completedBy:id,name,email',
        ]);

        if (! $allDates && $date) {
            $query->where('note_date', $date);
        }

        if ($request->filled('type') && $request->type !== 'all') {
            if ($request->type === 'todo_and_delegation') {
                $query->whereIn('type', ['todo', 'delegation']);
            } else {
                $query->where('type', $request->type);
            }
        }

        if ($request->filled('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'completed') {
                $query->where('is_completed', true);
            } elseif ($request->status === 'pending') {
                $query->where('is_completed', false);
            }
        }

        if ($request->filled('assigned_to') && $request->assigned_to !== 'all') {
            if ($request->assigned_to === 'me') {
                $query->where('assigned_to_user_id', $request->user()->id);
            } elseif ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to_user_id');
            } elseif (is_numeric($request->assigned_to)) {
                $query->where('assigned_to_user_id', (int) $request->assigned_to);
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $notes = $query->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();

        // Calculate stats for the current date view (or today if all_dates)
        $statsDate = (! $allDates && $date) ? $date : $today;
        $statsBase = AdminNote::where('note_date', $statsDate);

        $stats = [
            'totalNotes' => (clone $statsBase)->count(),
            'pendingTodos' => (clone $statsBase)->whereIn('type', ['todo', 'delegation'])->where('is_completed', false)->count(),
            'completedTodos' => (clone $statsBase)->whereIn('type', ['todo', 'delegation'])->where('is_completed', true)->count(),
            'urgentCount' => (clone $statsBase)->whereIn('priority', ['high', 'urgent'])->where('is_completed', false)->count(),
        ];

        $admins = User::where('role', 'admin')
            ->where('is_active', true)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json([
            'notes' => $notes,
            'stats' => $stats,
            'today' => $today,
            'currentDate' => $date,
            'admins' => $admins,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->input('assigned_to_user_id') === '' || $request->input('assigned_to_user_id') === '0') {
            $request->merge(['assigned_to_user_id' => null]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'type' => 'required|string|in:note,todo,delegation',
            'priority' => 'required|string|in:normal,high,urgent',
            'note_date' => 'nullable|date',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'is_pinned' => 'nullable|boolean',
            'checklist_items' => 'nullable|array',
            'checklist_items.*.text' => 'required|string|max:255',
            'checklist_items.*.completed' => 'nullable|boolean',
        ]);

        $today = CarbonImmutable::now('Asia/Makassar')->toDateString();
        $validated['note_date'] = $validated['note_date'] ?? $today;
        $validated['user_id'] = $request->user()->id;
        $validated['is_completed'] = false;
        $validated['is_pinned'] = (bool) ($validated['is_pinned'] ?? false);

        if (isset($validated['checklist_items']) && is_array($validated['checklist_items'])) {
            $validated['checklist_items'] = array_values(array_map(function ($item, $idx) {
                return [
                    'id' => (string) ($idx + 1),
                    'text' => trim($item['text']),
                    'completed' => (bool) ($item['completed'] ?? false),
                ];
            }, $validated['checklist_items'], array_keys($validated['checklist_items'])));
        }

        $note = AdminNote::create($validated);
        $note->load(['user:id,name,email', 'assignedTo:id,name,email', 'completedBy:id,name,email']);

        return response()->json($note, 201);
    }

    public function update(Request $request, AdminNote $adminNote): JsonResponse
    {
        if ($request->input('assigned_to_user_id') === '' || $request->input('assigned_to_user_id') === '0') {
            $request->merge(['assigned_to_user_id' => null]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'type' => 'required|string|in:note,todo,delegation',
            'priority' => 'required|string|in:normal,high,urgent',
            'note_date' => 'required|date',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'is_pinned' => 'nullable|boolean',
            'is_completed' => 'nullable|boolean',
            'checklist_items' => 'nullable|array',
            'checklist_items.*.text' => 'required|string|max:255',
            'checklist_items.*.completed' => 'nullable|boolean',
        ]);

        if (isset($validated['checklist_items']) && is_array($validated['checklist_items'])) {
            $validated['checklist_items'] = array_values(array_map(function ($item, $idx) {
                return [
                    'id' => (string) ($idx + 1),
                    'text' => trim($item['text']),
                    'completed' => (bool) ($item['completed'] ?? false),
                ];
            }, $validated['checklist_items'], array_keys($validated['checklist_items'])));
        }

        // Handle is_completed state
        if (array_key_exists('is_completed', $validated)) {
            if ($validated['is_completed'] && ! $adminNote->is_completed) {
                $validated['completed_at'] = now();
                $validated['completed_by_user_id'] = $request->user()->id;
            } elseif (! $validated['is_completed'] && $adminNote->is_completed) {
                $validated['completed_at'] = null;
                $validated['completed_by_user_id'] = null;
            }
        }

        $adminNote->update($validated);
        $adminNote->load(['user:id,name,email', 'assignedTo:id,name,email', 'completedBy:id,name,email']);

        return response()->json($adminNote);
    }

    public function toggleComplete(Request $request, AdminNote $adminNote): JsonResponse
    {
        $newStatus = ! $adminNote->is_completed;
        $adminNote->update([
            'is_completed' => $newStatus,
            'completed_at' => $newStatus ? now() : null,
            'completed_by_user_id' => $newStatus ? $request->user()->id : null,
        ]);

        $adminNote->load(['user:id,name,email', 'assignedTo:id,name,email', 'completedBy:id,name,email']);

        return response()->json($adminNote);
    }

    public function togglePin(Request $request, AdminNote $adminNote): JsonResponse
    {
        $adminNote->update([
            'is_pinned' => ! $adminNote->is_pinned,
        ]);

        $adminNote->load(['user:id,name,email', 'assignedTo:id,name,email', 'completedBy:id,name,email']);

        return response()->json($adminNote);
    }

    public function destroy(Request $request, AdminNote $adminNote): JsonResponse
    {
        $adminNote->delete();

        return response()->json(['status' => 'success', 'message' => 'Catatan berhasil dihapus.']);
    }
}
