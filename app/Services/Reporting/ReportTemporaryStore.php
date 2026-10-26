<?php

namespace App\Services\Reporting;

use Illuminate\Support\Facades\File;

class ReportTemporaryStore
{
    private array $locks = [];

    public function base(): string
    {
        return storage_path('app/private/reports');
    }

    public function create(): string
    {
        File::ensureDirectoryExists($this->base(), 0700);
        $path = $this->base().DIRECTORY_SEPARATOR.bin2hex(random_bytes(16));
        if (! mkdir($path, 0700)) {
            throw new \RuntimeException('No se pudo preparar el almacenamiento privado.');
        }
        $lock = fopen($path.DIRECTORY_SEPARATOR.'lease', 'x+');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            throw new \RuntimeException('No se pudo preparar la exportación.');
        }
        $this->locks[$path] = $lock;

        return $path;
    }

    public function remove(string $path): void
    {
        if (! $this->owned($path)) {
            return;
        }
        if (isset($this->locks[$path])) {
            flock($this->locks[$path], LOCK_UN);
            fclose($this->locks[$path]);
            unset($this->locks[$path]);
        }
        File::deleteDirectory($path);
    }

    public function cleanup(): int
    {
        if (! is_dir($this->base())) {
            return 0;
        }
        // Sweep one minute before TTL so the every-minute schedule stays within one hour.
        $ttl = min(3600, max(60, (int) config('reporting.temp_ttl_seconds')));
        $cutoff = time() - max(0, $ttl - 60);
        $removed = 0;
        foreach (File::directories($this->base()) as $path) {
            if (! $this->owned($path) || isset($this->locks[$path]) || filemtime($path) > $cutoff) {
                continue;
            }
            $lease = $path.DIRECTORY_SEPARATOR.'lease';
            $handle = fopen($lease, 'c+');
            if (! $handle) {
                continue;
            }
            if (flock($handle, LOCK_EX | LOCK_NB)) {
                flock($handle, LOCK_UN);
                fclose($handle);
                // Jobs are never reopened/reused; a released old directory is an orphan.
                $this->remove($path);
                $removed++;
            } else {
                fclose($handle);
            }
        }

        return $removed;
    }

    private function owned(string $path): bool
    {
        if (is_link($path) || ! preg_match('/^[a-f0-9]{32}$/D', basename($path))) {
            return false;
        }
        $base = realpath($this->base());
        $parent = realpath(dirname($path));

        return $base !== false && $parent !== false && $base === $parent;
    }
}
