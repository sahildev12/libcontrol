<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Services\BranchStudentCodePrefixService;
use Illuminate\Console\Command;

class BackfillBranchStudentCodePrefixes extends Command
{
    protected $signature = 'library:backfill-branch-prefixes';

    protected $description = 'Generate student ID prefixes for branches that do not have one yet';

    public function handle(BranchStudentCodePrefixService $prefixes): int
    {
        $updated = 0;

        Branch::query()->orderBy('id')->each(function (Branch $branch) use ($prefixes, &$updated) {
            if (filled($branch->student_code_prefix)) {
                $this->line("  {$branch->name}: {$branch->student_code_prefix} (unchanged)");

                return;
            }

            $prefix = $prefixes->ensureForBranch($branch);
            $updated++;
            $this->line("  {$branch->name}: {$prefix}");
        });

        $this->info("Done. Updated {$updated} branch(es).");

        return self::SUCCESS;
    }
}
