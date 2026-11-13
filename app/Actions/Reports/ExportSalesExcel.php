<?php

namespace App\Actions\Reports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;
use XMLWriter;
use ZipArchive;

class ExportSalesExcel
{
    private const NAMESPACE = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    public function execute(Builder $sales): string
    {
        $worksheetPath = tempnam(sys_get_temp_dir(), 'mpb-sheet-');
        $workbookPath = tempnam(sys_get_temp_dir(), 'mpb-excel-');

        if ($worksheetPath === false || $workbookPath === false) {
            throw new RuntimeException('Não foi possível preparar o relatório Excel.');
        }

        try {
            $this->writeWorksheet($worksheetPath, $sales);
            $archive = new ZipArchive;
            if ($archive->open($workbookPath, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Não foi possível criar o relatório Excel.');
            }

            try {
                foreach ($this->workbookParts() as $name => $xml) {
                    if (! $archive->addFromString($name, $xml)) {
                        throw new RuntimeException('Não foi possível gravar o relatório Excel.');
                    }
                }
                if (! $archive->addFile($worksheetPath, 'xl/worksheets/sheet1.xml')) {
                    throw new RuntimeException('Não foi possível gravar as vendas no relatório.');
                }
            } finally {
                if (! $archive->close()) {
                    throw new RuntimeException('Não foi possível concluir o relatório Excel.');
                }
            }

            return $workbookPath;
        } catch (Throwable $exception) {
            unlink($workbookPath);
            throw $exception;
        } finally {
            unlink($worksheetPath);
        }
    }

    private function writeWorksheet(string $path, Builder $sales): void
    {
        $writer = new XMLWriter;
        if (! $writer->openUri($path)) {
            throw new RuntimeException('Não foi possível gravar a planilha.');
        }
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElementNS(null, 'worksheet', self::NAMESPACE);
        $writer->startElement('sheetViews');
        $writer->startElement('sheetView');
        $writer->writeAttribute('workbookViewId', '0');
        $writer->startElement('pane');
        $writer->writeAttribute('ySplit', '1');
        $writer->writeAttribute('topLeftCell', 'A2');
        $writer->writeAttribute('activePane', 'bottomLeft');
        $writer->writeAttribute('state', 'frozen');
        $writer->endElement();
        $writer->endElement();
        $writer->endElement();
        $writer->startElement('cols');
        foreach ([10, 28, 32, 28, 16, 18, 16, 16, 16, 24] as $index => $width) {
            $writer->startElement('col');
            $writer->writeAttribute('min', (string) ($index + 1));
            $writer->writeAttribute('max', (string) ($index + 1));
            $writer->writeAttribute('width', (string) $width);
            $writer->writeAttribute('customWidth', '1');
            $writer->endElement();
        }
        $writer->endElement();
        $writer->startElement('sheetData');
        $this->writeRow($writer, 1, ['Venda', 'Data (Brasília)', 'Cliente', 'Operador', 'Terminal', 'Pagamento', 'Total', 'Devolvido', 'Líquido', 'Situação']);
        $row = 1;
        foreach ($sales->lazyById(500) as $sale) {
            if ($row >= 1048576) {
                throw ValidationException::withMessages(['from' => 'O período excede o limite de linhas do Excel. Selecione um período menor.']);
            }
            $soldAt = $sale->created_at->timezone('America/Sao_Paulo');
            $excelDate = ($soldAt->getTimestamp() + $soldAt->getOffset()) / 86400 + 25569;
            $this->writeRow($writer, ++$row, [
                $sale->id, $excelDate, $sale->customer_name ?? '', $sale->user->name,
                $sale->terminal, $sale->payment_method, $sale->total_cents / 100,
                $sale->returned_cents / 100, ($sale->total_cents - $sale->returned_cents) / 100,
                $sale->status_label,
            ]);
        }
        $writer->endElement();
        $writer->startElement('autoFilter');
        $writer->writeAttribute('ref', 'A1:J'.$row);
        $writer->endElement();
        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
    }

    private function writeRow(XMLWriter $writer, int $row, array $values): void
    {
        $writer->startElement('row');
        $writer->writeAttribute('r', (string) $row);
        foreach ($values as $index => $value) {
            $style = $row === 1 ? 1 : match ($index) {
                1 => 3,
                6, 7, 8 => 2,
                default => 0,
            };
            $writer->startElement('c');
            $writer->writeAttribute('r', chr(65 + $index).$row);
            $writer->writeAttribute('s', (string) $style);
            if (is_int($value) || is_float($value)) {
                $writer->writeElement('v', is_float($value) ? number_format($value, 10, '.', '') : (string) $value);
            } else {
                $writer->writeAttribute('t', 'inlineStr');
                $writer->startElement('is');
                $writer->writeElement('t', preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value));
                $writer->endElement();
            }
            $writer->endElement();
        }
        $writer->endElement();
    }

    private function workbookParts(): array
    {
        return [
            '[Content_Types].xml' => <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>
XML,
            '_rels/.rels' => <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML,
            'xl/workbook.xml' => <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets><sheet name="Vendas" sheetId="1" r:id="rId1"/></sheets>
</workbook>
XML,
            'xl/_rels/workbook.xml.rels' => <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML,
            'xl/styles.xml' => <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <numFmts count="2">
        <numFmt numFmtId="164" formatCode="&quot;R$&quot; #,##0.00"/>
        <numFmt numFmtId="165" formatCode="dd/mm/yyyy hh:mm:ss"/>
    </numFmts>
    <fonts count="2">
        <font><sz val="11"/><name val="Calibri"/></font>
        <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    </fonts>
    <fills count="3">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF145B3D"/><bgColor indexed="64"/></patternFill></fill>
    </fills>
    <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
    <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
    <cellXfs count="4">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>
        <xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>
        <xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>
    </cellXfs>
    <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>
XML,
        ];
    }
}
