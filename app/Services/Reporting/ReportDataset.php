<?php

namespace App\Services\Reporting;

final readonly class ReportDataset
{
    public function __construct(public array $metadata, public string $path, public int $count) {}

    public function rows(): \Generator
    {
        $handle = fopen($this->path, 'rb');
        if (! $handle) {
            throw new \RuntimeException('No se pudo leer el conjunto de reporte.');
        }
        try {
            while (($line = fgets($handle)) !== false) {
                yield json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            }
            if (! feof($handle)) {
                throw new \RuntimeException('Lectura incompleta del reporte.');
            }
        } finally {
            fclose($handle);
        }
    }
}
