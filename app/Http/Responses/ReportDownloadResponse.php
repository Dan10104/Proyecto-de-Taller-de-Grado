<?php

namespace App\Http\Responses;

use App\Services\Reporting\ReportTemporaryStore;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportDownloadResponse extends BinaryFileResponse
{
    private bool $cleaned = false;

    public function __construct(string $file, string $name, string $mime, private ReportTemporaryStore $store, private string $directory)
    {
        parent::__construct($file, 200, ['Content-Type' => $mime, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'], false, 'attachment', false, false);
        $this->setContentDisposition('attachment', $name);
    }

    public function sendContent(): static
    {
        try {
            return parent::sendContent();
        } finally {
            $this->cleanup();
        }
    }

    public function cleanup(): void
    {
        if (! $this->cleaned) {
            $this->store->remove($this->directory);
            $this->cleaned = true;
        }
    }

    public function __destruct()
    {
        $this->cleanup();
    }
}
