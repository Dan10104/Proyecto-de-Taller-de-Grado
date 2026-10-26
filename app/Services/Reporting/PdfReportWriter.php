<?php

namespace App\Services\Reporting;

use Dompdf\Adapter\CPDF;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfReportWriter
{
    private CPDF $canvas;

    private string $font;

    private string $bold;

    private array $widths;

    private array $columns;

    private float $y;

    public function write(ReportDataset $data, string $path, ReportBudget $budget): void
    {
        $options = new Options(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false, 'isFontSubsettingEnabled' => true,
            'tempDir' => dirname($path), 'fontCache' => dirname($path), 'chroot' => [base_path('vendor/dompdf/dompdf/lib/fonts')], 'allowedProtocols' => []]);
        $dompdf = new Dompdf($options);
        $this->canvas = new CPDF('a4', 'landscape', $dompdf);
        $this->font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $this->bold = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'bold');
        $this->columns = $data->metadata['columnas'];
        $weights = array_map(fn ($c) => match ($c['type']) {
            'datetime' => 1.18, 'integer', 'decimal', 'boolean' => 0.78, default => 1.2
        }, $this->columns);
        $total = array_sum($weights);
        $this->widths = array_map(fn ($w) => 777 * $w / $total, $weights);
        $this->canvas->add_info('Title', $data->metadata['titulo']);
        $this->canvas->add_info('Author', 'ESPACIO-U / FICCT');
        $this->header($data->metadata, true);
        $number = 0;
        foreach ($data->rows() as $row) {
            $lines = [];
            foreach ($this->columns as $index => $column) {
                $value = $row[$column['key']];
                $text = $value === null ? '—' : ($column['type'] === 'boolean' ? ($value ? 'Sí' : 'No') : (string) $value);
                $lines[] = $this->wrap($text, $this->widths[$index] - 10, 7.2);
            }
            $height = max(24, max(array_map('count', $lines)) * 10 + 9);
            if ($this->y + $height > 550) {
                $this->canvas->new_page();
                $this->header($data->metadata, false);
            }
            if ($number % 2 === 0) {
                $this->canvas->filled_rectangle(32, $this->y, 777, $height, [0.95, 0.97, 0.99]);
            }
            $x = 32;
            foreach ($lines as $index => $cell) {
                foreach ($cell as $line => $text) {
                    $this->canvas->text($x + 5, $this->y + 4 + $line * 10, $text, $this->font, 7.2, [0.12, 0.18, 0.25]);
                }
                $x += $this->widths[$index];
            }
            $this->y += $height;
            $this->canvas->line(32, $this->y, 809, $this->y, [0.85, 0.89, 0.93], 0.4);
            $number++;
            $budget->check();
        }
        $this->canvas->page_text(32, 568, 'ESPACIO-U / FICCT · Reporte autorizado', $this->font, 7, [0.35, 0.4, 0.48]);
        $this->canvas->page_text(680, 568, 'Página {PAGE_NUM} de {PAGE_COUNT}', $this->font, 7, [0.35, 0.4, 0.48]);
        $bytes = $this->canvas->output(['compress' => true]);
        $budget->check();
        if (file_put_contents($path, $bytes) !== strlen($bytes)) {
            throw new \RuntimeException('No se pudo completar el PDF.');
        }
        unset($bytes);
        $budget->check($path);
    }

    private function header(array $meta, bool $first): void
    {
        $this->canvas->text(32, 23, 'ESPACIO-U', $this->bold, 17, [0.07, 0.23, 0.39]);
        $this->canvas->text(723, 25, 'FICCT', $this->bold, 12, [0.07, 0.23, 0.39]);
        $this->canvas->text(32, 51, $meta['titulo'], $this->bold, 13, [0.12, 0.18, 0.25]);
        $this->canvas->text(32, 74, 'Generado: '.$meta['generado_en'].' (America/La_Paz)', $this->font, 8);
        $this->y = 90;
        if ($first) {
            foreach ($this->wrap('Generador: '.$meta['generador'], 777, 8) as $line) {
                $this->canvas->text(32, $this->y, $line, $this->font, 8);
                $this->y += 12;
            }
            foreach ($meta['filtros_visibles'] as $label => $value) {
                foreach ($this->wrap($label.': '.$value, 777, 8) as $line) {
                    $this->canvas->text(32, $this->y, $line, $this->font, 8, [0.3, 0.36, 0.43]);
                    $this->y += 11;
                }
            }
        }
        $this->y += 12;
        $labels = array_map(fn ($c, $w) => $this->wrap($c['label'], $w - 10, 7.5), $this->columns, $this->widths);
        $height = max(array_map('count', $labels)) * 11 + 12;
        $this->canvas->filled_rectangle(32, $this->y, 777, $height, [0.09, 0.24, 0.39]);
        $x = 32;
        foreach ($labels as $index => $cell) {
            foreach ($cell as $line => $text) {
                $this->canvas->text($x + 5, $this->y + 5 + $line * 11, $text, $this->font, 7.5, [1, 1, 1]);
            }
            $x += $this->widths[$index];
        }
        $this->y += $height;
    }

    private function wrap(string $text, float $width, float $size): array
    {
        // Literal PDF text: no HTML, URL, image, script, markup or user-selected font is interpreted.
        $text = trim(preg_replace('/[\\x00-\\x20\\x7f]+/u', ' ', $text) ?? '');
        if ($text === '') {
            return [''];
        }
        $lines = [];
        $current = '';
        foreach (explode(' ', $text) as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            if ($this->canvas->get_text_width($candidate, $this->font, $size) <= $width) {
                $current = $candidate;

                continue;
            }
            if ($current !== '') {
                $lines[] = $current;
                $current = '';
            }
            while ($this->canvas->get_text_width($word, $this->font, $size) > $width) {
                $low = 1;
                $high = mb_strlen($word);
                while ($low < $high) {
                    $middle = (int) ceil(($low + $high) / 2);
                    if ($this->canvas->get_text_width(mb_substr($word, 0, $middle), $this->font, $size) <= $width) {
                        $low = $middle;
                    } else {
                        $high = $middle - 1;
                    }
                }
                $lines[] = mb_substr($word, 0, $low);
                $word = mb_substr($word, $low);
            }
            $current = $word;
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: [''];
    }
}
