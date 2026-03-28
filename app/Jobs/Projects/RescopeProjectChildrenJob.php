<?php

declare(strict_types=1);

namespace App\Jobs\Projects;

use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\CostRecord;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RescopeProjectChildrenJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $projectId,
    ) {}

    public function handle(): void
    {
        $project = Project::query()->find($this->projectId);

        if (! $project instanceof Project) {
            return;
        }

        Task::query()->where('project_id', $project->id)->update([
            'organization_id' => $project->organization_id,
        ]);

        TimeEntry::query()->where('project_id', $project->id)->update([
            'organization_id' => $project->organization_id,
        ]);

        CostRecord::query()->where('project_id', $project->id)->update([
            'organization_id' => $project->organization_id,
        ]);

        Comment::query()
            ->where('commentable_type', Project::class)
            ->where('commentable_id', $project->id)
            ->update([
                'organization_id' => $project->organization_id,
            ]);

        Attachment::query()
            ->where('attachable_type', Project::class)
            ->where('attachable_id', $project->id)
            ->update([
                'organization_id' => $project->organization_id,
            ]);

        AuditLog::query()
            ->where('subject_type', Project::class)
            ->where('subject_id', $project->id)
            ->update([
                'organization_id' => $project->organization_id,
            ]);
    }
}
