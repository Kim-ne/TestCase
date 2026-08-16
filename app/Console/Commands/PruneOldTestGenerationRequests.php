<?php

namespace App\Console\Commands;

use App\Models\TestGenerationRequest;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:prune-old-test-generation-requests')]
#[Description('Delete test generation requests older than the configured number of days.')]
class PruneOldTestGenerationRequests extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = config('testcase.retention_days');

        TestGenerationRequest::where('created_at', '<', now()->subDays($days))->delete();
    }
}
