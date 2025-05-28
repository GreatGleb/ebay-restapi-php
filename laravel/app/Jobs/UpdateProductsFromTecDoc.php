<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Http\Controllers\API\UpdateProducts;

class UpdateProductsFromTecDoc implements ShouldQueue
{
    use Queueable;

    protected $logTraceId;

    /**
     * Create a new job instance.
     */
    public function __construct($logTraceId)
    {
        $this->logTraceId = $logTraceId;
    }

    /**
     * Execute the job.
     */
    public function handle(): bool
    {
        $updater = new UpdateProducts();
        $isUpdatedFromGoogleSheets = $updater->fromGoogleSheets($this->logTraceId);

        if(!$isUpdatedFromGoogleSheets) {
            return false;
        }

        $isUpdatedFromTecDoc = $updater->fromTecDoc($this->logTraceId);

        if(!$isUpdatedFromTecDoc) {
            return false;
        }

        $isUpdatedToGoogleSheets = $updater->toGoogleSheets($this->logTraceId);

        return $isUpdatedToGoogleSheets;
    }
}
