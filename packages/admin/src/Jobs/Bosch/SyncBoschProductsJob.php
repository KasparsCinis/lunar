<?php

namespace Lunar\Admin\Jobs\Bosch;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Lunar\Admin\Actions\Bosch\SyncBoschProducts;
use Lunar\Facades\DB;
use Lunar\Models\Excel\Import;
use Throwable;

class SyncBoschProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 0;

    public $failOnTimeout = true;

    public int $tries = 1;

    protected string $importId;

    public function __construct(string $importId)
    {
        $this->importId = $importId;
    }

    public function handle(): void
    {
        DB::disableQueryLog();

        $import = Import::findOrFail($this->importId);

        try {
            $import->status = Import::STATUS_IN_PROGRESS;
            $import->progress = 'Fetching Bosch product feed';
            $import->saveOrFail();

            $result = app(SyncBoschProducts::class)(function (string $message) use ($import) {
                $import->progress = Str::limit($message, 200);
                $import->saveOrFail();
            });

            $import->status = Import::STATUS_SUCCESS;
            $import->progress = "Updated {$result['updated']} variant(s); {$result['in_feed']} item(s) in feed.";
            $import->saveOrFail();
        } catch (Throwable $e) {
            Log::error('Failed to sync Bosch products', [
                'import_id' => $this->importId,
                'error' => $e->getMessage(),
            ]);

            $this->markFailed($import, $e);
        }
    }

    public function failed(?Throwable $e): void
    {
        $import = Import::find($this->importId);

        if (! $import || $import->status === Import::STATUS_SUCCESS) {
            return;
        }

        $this->markFailed($import, $e);
    }

    private function markFailed(Import $import, ?Throwable $e): void
    {
        $import->status = Import::STATUS_ERROR;
        $import->progress = Str::limit('Failed - '.($e?->getMessage() ?? 'Unknown error'), 200);
        $import->save();
    }
}
