<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportSalesExcel;
use App\Actions\Reports\SalesReport;
use App\Actions\Sales\SearchSales;
use App\Http\Requests\ReportRequest;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(ReportRequest $r, SalesReport $a): View
    {
        return view('relatorios.index', array_merge(['title' => 'Relatório de vendas', 'module' => 'relatorios', 'asset' => 'acessos'], $a->execute($r->validated(), $r->user())));
    }

    public function export(ReportRequest $r, SearchSales $a): StreamedResponse
    {
        $query = $a->query($r->user(), $r->validated())->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Venda', 'Data (Brasília)', 'Cliente', 'Operador', 'Terminal', 'Pagamento', 'Total', 'Devolvido', 'Líquido', 'Situação'], ';', '"', '');
            $safe = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : (string) $v;
            foreach ($query->cursor() as $sale) {
                $soldAt = $sale->created_at->timezone('America/Sao_Paulo');
                $date = $soldAt->format('d/m/Y').' às '.$soldAt->format('H:i:s');
                fputcsv($out, [$sale->id, $date, $safe($sale->customer_name), $safe($sale->user->name), $safe($sale->terminal), $sale->payment_method, number_format($sale->total_cents / 100, 2, ',', ''), number_format($sale->returned_cents / 100, 2, ',', ''), number_format(($sale->total_cents - $sale->returned_cents) / 100, 2, ',', ''), $sale->status_label], ';', '"', '');
            }
            fclose($out);
        }, 'vendas-'.now('America/Sao_Paulo')->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function excel(ReportRequest $request, SearchSales $search, ExportSalesExcel $export): BinaryFileResponse
    {
        $file = $export->execute($search->query($request->user(), $request->validated()));

        return response()->download($file, 'vendas-'.now('America/Sao_Paulo')->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
