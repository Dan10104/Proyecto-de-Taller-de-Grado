<?php

namespace App\Services\Reporting;

use Illuminate\Validation\ValidationException;
use ZipArchive;

class ReportExportService
{
    public function render(ReportDataset $data, string $format, string $directory, ReportBudget $budget): array
    {
        if (! in_array($format, ['pdf', 'xlsx'], true)) {
            throw ValidationException::withMessages(['formato' => 'Formato de exportación no permitido.']);
        }
        $file = $directory.DIRECTORY_SEPARATOR.'reporte.'.$format;
        $writer = $format === 'pdf' ? app(PdfReportWriter::class) : app(XlsxReportWriter::class);
        $writer->write($data, $file, $budget);
        $budget->check($file);
        if (! is_file($file) || filesize($file) === 0) {
            throw new \RuntimeException('El archivo no pudo generarse.');
        }
        if ($format === 'pdf') {
            $h = fopen($file, 'rb');
            $signature = fread($h, 5);
            fclose($h);
            if ($signature !== '%PDF-') {
                throw new \RuntimeException('El archivo PDF no es válido.');
            }
        } else {
            $zip = new ZipArchive;
            if ($zip->open($file, ZipArchive::RDONLY) !== true) {
                throw new \RuntimeException('El libro no es válido.');
            }
            try {
                if ($zip->locateName('xl/workbook.xml') === false || $zip->locateName('xl/worksheets/sheet1.xml') === false) {
                    throw new \RuntimeException('El libro está incompleto.');
                }
            } finally {
                $zip->close();
            }
        }
        $stamp = str_replace(['-', ' ', ':'], ['', '-', ''], $data->metadata['generado_en']);
        $name = 'espacio-u_'.$data->metadata['reporte'].'_'.$stamp.'.'.$format;
        $mime = $format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return compact('file', 'name', 'mime');
    }
}
