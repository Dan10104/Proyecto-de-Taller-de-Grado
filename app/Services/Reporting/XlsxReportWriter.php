<?php

namespace App\Services\Reporting;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\BooleanCell;
use OpenSpout\Common\Entity\Cell\DateTimeCell;
use OpenSpout\Common\Entity\Cell\EmptyCell;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

class XlsxReportWriter
{
    public function write(ReportDataset $data, string $path, ReportBudget $budget): void
    {
        $normal = new Style(fontSize: 10, shouldWrapText: true);
        $heading = new Style(fontBold: true, fontColor: 'FFFFFF', backgroundColor: '173E64', shouldWrapText: true);
        $date = new Style(format: 'yyyy-mm-dd hh:mm:ss');
        $day = new Style(format: 'yyyy-mm-dd');
        $writer = new Writer(new Options(FALLBACK_STYLE: $normal, SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY: false, DEFAULT_COLUMN_WIDTH: 24, tempFolder: dirname($path), SHOULD_USE_INLINE_STRINGS: true));
        $writer->openToFile($path);
        try {
            $sheet = $writer->getCurrentSheet();
            $sheet->setName('Reporte');
            $meta = $data->metadata;
            $columns = $meta['columnas'];
            $writer->addRow(new Row([new StringCell('ESPACIO-U / FICCT', $heading)]));
            $writer->addRow(new Row([new StringCell($meta['titulo'])]));
            $writer->addRow(new Row([new StringCell('Generado'), new DateTimeCell(new \DateTimeImmutable($meta['generado_en'], new \DateTimeZone('America/La_Paz')), $date), new StringCell('America/La_Paz')]));
            $writer->addRow(new Row([new StringCell('Generador'), new StringCell($meta['generador'])]));
            $writer->addRow(new Row([new StringCell('Filtros aplicados', $heading)]));
            foreach ($meta['filtros_visibles'] as $label => $value) {
                $writer->addRow(new Row([new StringCell($label), new StringCell((string) $value)]));
            }
            $writer->addRow(new Row([]));
            $headerRow = 7 + count($meta['filtros_visibles']);
            $writer->addRow(new Row(array_map(fn ($c) => new StringCell($c['label'], $heading), $columns), 30));
            $sheet->setAutoFilter(new AutoFilter(0, $headerRow, count($columns) - 1, $headerRow + $data->count));
            $sheet->setPrintTitleRows($headerRow.':'.$headerRow);
            foreach ($data->rows() as $row) {
                $cells = [];
                foreach ($columns as $c) {
                    $value = $row[$c['key']];
                    $cells[] = $value === null ? new EmptyCell(null) : match ($c['type']) {
                        'integer', 'decimal' => new NumericCell($value),
                        'boolean' => new BooleanCell($value),
                        'datetime', 'date' => new DateTimeCell(new \DateTimeImmutable($value, new \DateTimeZone('America/La_Paz')), $c['type'] === 'date' ? $day : $date),
                        // Never use Cell::fromValue: it can interpret user strings as formulas.
                        default => new StringCell((string) $value),
                    };
                }
                $writer->addRow(new Row($cells));
                $budget->check($path);
            }
        } finally {
            $writer->close();
        }
        $budget->check($path);
    }
}
