<?php

namespace App\Jobs;

use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyTaskCreated implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
    ) {}

    public function handle(): void
    {
        // Simulate sending a notification (email, Slack, etc.)
        // In a real app, you'd use Laravel Notifications here.
        Log::info('Task notification sent', [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'message' => "New task created: {$this->task->title}",
        ]);
    }
}
