<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

class InventoryExcelExporter
{
    /**
     * @param  array<string, list<list<string|int|float|null>>>  $sheets
     */
    public function create(array $sheets): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekspor XLSX memerlukan ekstensi PHP ZipArchive.');
        }

        $path = tempnam(sys_get_temp_dir(), 'inventory-report-');

        if ($path === false) {
            throw new RuntimeException('Tidak dapat membuat file sementara untuk ekspor Excel.');
        }

        $archive = new ZipArchive;
        if ($archive->open($path, ZipArchive::OVERWRITE) !== true) {
            @unlink($path);
            throw new RuntimeException('Tidak dapat membuat arsip XLSX.');
        }

        $sheetNames = array_keys($sheets);
        $archive->addFromString('[Content_Types].xml', $this->contentTypes(count($sheetNames)));
        $archive->addFromString('_rels/.rels', $this->rootRelationships());
        $archive->addFromString('xl/workbook.xml', $this->workbook($sheetNames));
        $archive->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships(count($sheetNames)));

        foreach (array_values($sheets) as $index => $rows) {
            $archive->addFromString('xl/worksheets/sheet'.($index + 1).'.xml', $this->worksheet($rows));
        }

        if (! $archive->close()) {
            @unlink($path);
            throw new RuntimeException('Gagal menyelesaikan file ekspor Excel.');
        }

        return $path;
    }

    private function contentTypes(int $sheetCount): string
    {
        $overrides = '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';

        for ($index = 1; $index <= $sheetCount; $index++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'.$overrides.'</Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    /**
     * @param  list<string>  $sheetNames
     */
    private function workbook(array $sheetNames): string
    {
        $sheets = '';

        foreach ($sheetNames as $index => $name) {
            $sheets .= '<sheet name="'.$this->escape($name).'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$sheets.'</sheets></workbook>';
    }

    private function workbookRelationships(int $sheetCount): string
    {
        $relationships = '';

        for ($index = 1; $index <= $sheetCount; $index++) {
            $relationships .= '<Relationship Id="rId'.$index.'" '
                .'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" '
                .'Target="worksheets/sheet'.$index.'.xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$relationships.'</Relationships>';
    }

    /**
     * @param  list<list<string|int|float|null>>  $rows
     */
    private function worksheet(array $rows): string
    {
        $xmlRows = '';

        foreach ($rows as $rowIndex => $row) {
            $cells = '';

            foreach ($row as $columnIndex => $value) {
                $reference = $this->columnName($columnIndex + 1).($rowIndex + 1);
                if (is_int($value) || is_float($value)) {
                    $cells .= '<c r="'.$reference.'"><v>'.$value.'</v></c>';
                } elseif ($value !== null) {
                    $cells .= '<c r="'.$reference.'" t="inlineStr"><is><t xml:space="preserve">'
                        .$this->escape((string) $value).'</t></is></c>';
                }
            }

            $xmlRows .= '<row r="'.($rowIndex + 1).'">'.$cells.'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.$xmlRows.'</sheetData></worksheet>';
    }

    private function columnName(int $number): string
    {
        $name = '';

        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
