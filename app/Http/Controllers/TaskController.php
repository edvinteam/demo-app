<?php

namespace App\Http\Controllers;

use App\Events\TaskUpdated;
use App\Jobs\NotifyTaskCreated;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index(): JsonResponse
    {
        $tasks = Task::orderBy('created_at', 'desc')->get();

        return response()->json($tasks);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (config('scout.driver') === 'meilisearch' && $query !== '') {
            $tasks = Task::search($query)->get();
        } else {
            $tasks = Task::where('title', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return response()->json($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $data = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'status' => 'todo',
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $data['attachment_path'] = $file->store('attachments', 'public');
            $data['attachment_name'] = $file->getClientOriginalName();
        }

        $task = Task::create($data);

        // Dispatch a queue job to simulate sending a notification
        NotifyTaskCreated::dispatch($task);

        // Broadcast to all connected clients
        broadcast(new TaskUpdated('created', $task));

        return response()->json($task, 201);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:todo,in-progress,done',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
        ]);

        $task->update($validated);

        // Broadcast the change to all connected clients
        broadcast(new TaskUpdated('updated', $task));

        return response()->json($task);
    }

    public function destroy(Task $task): JsonResponse
    {
        $taskId = $task->id;

        if ($task->attachment_path) {
            Storage::disk('public')->delete($task->attachment_path);
        }

        $task->delete();

        // Broadcast the deletion
        broadcast(new TaskUpdated('deleted', taskId: $taskId));

        return response()->json(null, 204);
    }

    public function downloadAttachment(Task $task)
    {
        if (! $task->attachment_path || ! Storage::disk('public')->exists($task->attachment_path)) {
            return response()->json(['error' => 'No attachment found.'], 404);
        }

        return Storage::disk('public')->download($task->attachment_path, $task->attachment_name);
    }

    public function exportPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $gotenbergUrl = config('services.gotenberg.url');

        if (! $gotenbergUrl) {
            return response()->json(['error' => 'PDF export not available — Gotenberg is not configured.'], 503);
        }

        $tasks = Task::orderBy('status')->orderBy('created_at', 'desc')->get();

        $columns = [
            'todo' => ['label' => 'To Do', 'color' => '#3b82f6', 'bg' => '#eff6ff'],
            'in-progress' => ['label' => 'In Progress', 'color' => '#f59e0b', 'bg' => '#fffbeb'],
            'done' => ['label' => 'Done', 'color' => '#10b981', 'bg' => '#ecfdf5'],
        ];

        $html = view('pdf.report', compact('tasks', 'columns'))->render();

        $response = Http::timeout(30)
            ->attach('files', $html, 'index.html')
            ->post("{$gotenbergUrl}/forms/chromium/convert/html", [
                'marginTop' => '0.4',
                'marginBottom' => '0.4',
                'marginLeft' => '0.4',
                'marginRight' => '0.4',
                'printBackground' => 'true',
            ]);

        if (! $response->successful()) {
            return response()->json(['error' => 'PDF generation failed.'], 502);
        }

        $filename = 'task-board-report-' . now()->format('Y-m-d') . '.pdf';

        return response($response->body(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function features(): JsonResponse
    {
        return response()->json([
            'search' => config('scout.driver') === 'meilisearch',
            'pdf_export' => (bool) config('services.gotenberg.url'),
        ]);
    }
}
