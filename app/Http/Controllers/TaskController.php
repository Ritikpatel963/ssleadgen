<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        // Load tasks and notes (newest tasks first, or sorted by due date)
        $tasks = Task::with('notes')->orderBy('due_date', 'asc')->get();
        $initialClients = Client::validNames()
            ->whereNotNull('first_name')
            ->where('first_name', '!=', '')
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(50)
            ->get();

        return view('pages.utilities.tasks', compact('tasks', 'initialClients'));
    }

    public function clientSearch(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $clientsQuery = Client::validNames()
            ->whereNotNull('first_name')
            ->where('first_name', '!=', '')
            ->select(['id', 'first_name', 'last_name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($clientQuery) use ($search): void {
                    $clientQuery
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                });
            })
            ->orderBy('first_name')
            ->orderBy('last_name');

        $clients = $clientsQuery->paginate(30);

        return response()->json([
            'results' => collect($clients->items())->map(function (Client $client): array {
                $name = trim($client->first_name . ' ' . $client->last_name);
                return [
                    'id' => $name,
                    'text' => $name,
                ];
            })->values(),
            'pagination' => ['more' => $clients->hasMorePages()],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'due_date' => 'required|date',
            'due_time' => 'nullable',
            'priority' => 'required|in:High,Medium,Low',
            'note' => 'nullable|string|max:5000',
        ]);

        // $initialNote = $validated['note'] ?? null;
        // unset($validated['note']);

        // $task = Task::create($validated);

        // // Add initial note
        // $task->notes()->create([
        //     'note' => $initialNote ? 'Converted from lead note: '.$initialNote : 'Task created.',
        // ]);
        //
            $initialNote = isset($validated['note']) ? trim($validated['note']) : null;
            unset($validated['note']);
        if ($initialNote && Task::where('client_name', $validated['client_name'] ?? null)
             ->where('title', $validated['title'])->exists()) {
            return response()->json(['success' => false, 'message' => 'This note was already converted to a task.'], 422);
                }
            $task = Task::create($validated);

            $task->notes()->create([
        'note' => $initialNote ?: 'Task created.',
            ]);
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Task added successfully.',
                'task' => $task->load('notes'),
            ]);
        }
//
        return redirect()->back()->with('success', 'Task added successfully.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $task->update([
            'status' => $task->status === 'Completed' ? 'Pending' : 'Completed',
        ]);

        // Add a note about the status change
        $task->notes()->create([
            'note' => 'Status updated: '.$task->status.'.',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Task status updated.',
                'task' => $task->load('notes'),
            ]);
        }

        return redirect()->back()->with('success', 'Task status updated.');
    }

    public function storeNote(Request $request, Task $task)
    {
        $validated = $request->validate([
            'note' => 'required|string',
        ]);

        $note = $task->notes()->create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Task note added successfully.',
                'note' => $note,
            ]);
        }

        return redirect()->back()->with('success', 'Task note added.');
    }

    public function destroy(Request $request, Task $task)
    {
        $task->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Task deleted successfully.');
    }
}
