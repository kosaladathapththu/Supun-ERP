<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use ZipArchive;

class XlsxReader
{
    public function rows(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['file' => 'The Excel workbook cannot be opened.']);
        }try {
            $shared = $this->sharedStrings($zip);
            $xml = $zip->getFromName($this->firstSheetPath($zip));
            if ($xml === false) {
                throw ValidationException::withMessages(['file' => 'The Excel workbook has no readable worksheet.']);
            }$sheet = simplexml_load_string($xml);
            if (! $sheet) {
                throw ValidationException::withMessages(['file' => 'The Excel worksheet XML is invalid.']);
            }$sheet->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $rows = [];
            foreach ($sheet->xpath('//x:sheetData/x:row') ?: [] as $row) {
                $values = [];
                foreach ($row->c as $cell) {
                    preg_match('/^[A-Z]+/', strtoupper((string) $cell['r']), $match);
                    $index = $this->columnIndex($match[0] ?? 'A');
                    $type = (string) $cell['t'];
                    if ($type === 's') {
                        $value = $shared[(int) $cell->v] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $value = $this->nodeText($cell->is);
                    } elseif ($type === 'b') {
                        $value = (string) $cell->v === '1' ? 'yes' : 'no';
                    } else {
                        $value = (string) $cell->v;
                    }$values[$index] = trim($value);
                }if ($values) {
                    $last = max(array_keys($values));
                    $rows[] = array_map(fn ($i) => $values[$i] ?? '', range(0, $last));
                }if (count($rows) > 5001) {
                    throw ValidationException::withMessages(['file' => 'The import is limited to 5,000 rows per workbook.']);
                }
            }

return$rows;
        } finally {
            $zip->close();
        }
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return[];
        }$doc = simplexml_load_string($xml);
        $doc->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $result = [];
        foreach ($doc->xpath('//x:si') ?: [] as $item) {
            $result[] = $this->nodeText($item);
        }

return$result;
    }

    private function firstSheetPath(ZipArchive $zip): string
    {
        $workbook = simplexml_load_string((string) $zip->getFromName('xl/workbook.xml'));
        $workbook->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $sheet = ($workbook->xpath('//x:sheets/x:sheet') ?: [])[0] ?? null;
        if (! $sheet) {
            return'xl/worksheets/sheet1.xml';
        }$attrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $id = (string) $attrs['id'];
        $rels = simplexml_load_string((string) $zip->getFromName('xl/_rels/workbook.xml.rels'));
        foreach ($rels->Relationship ?? [] as $rel) {
            if ((string) $rel['Id'] === $id) {
                return'xl/'.ltrim((string) $rel['Target'], '/');
            }
        }

return'xl/worksheets/sheet1.xml';
    }

    private function columnIndex(string $letters): int
    {
        $number = 0;
        foreach (str_split($letters)as$letter) {
            $number = $number * 26 + (ord($letter) - 64);
        }

return$number - 1;
    }

    private function nodeText($node): string
    {
        $dom = dom_import_simplexml($node);

        return$dom ? $dom->textContent : '';
    }
}
