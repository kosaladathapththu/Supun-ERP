<?php
namespace App\Http\Controllers;
use App\Services\ManagementReportService;use Illuminate\Http\Request;use Symfony\Component\HttpFoundation\StreamedResponse;
class ReportController extends Controller
{
 private function dates(Request $r):array{$to=$r->input('to',now()->toDateString());$from=$r->input('from',date('Y-m-01',strtotime($to)));abort_unless(strtotime($from)!==false&&strtotime($to)!==false&&$from<=$to,422,'Invalid report date range.');return [$from,$to];}
 public function index(Request $r,ManagementReportService $s){[$from,$to]=$this->dates($r);$data=$s->dashboard($r->user()->company_id,$from,$to);return view('reports.index',compact('from','to','data'));}
 public function profitability(Request $r,ManagementReportService $s){[$from,$to]=$this->dates($r);$rows=$s->profitability($r->user()->company_id,$from,$to);return view('reports.profitability',compact('from','to','rows'));}
 public function inventory(Request $r,ManagementReportService $s){$rows=$s->inventory($r->user()->company_id);return view('reports.inventory',compact('rows'));}
 public function exportProfitability(Request $r,ManagementReportService $s):StreamedResponse{[$from,$to]=$this->dates($r);$rows=$s->profitability($r->user()->company_id,$from,$to);return response()->streamDownload(function()use($rows){$out=fopen('php://output','w');fputcsv($out,['Item Code','Product','Category','Brand','Net Quantity','Net Sales','Cost','Gross Profit','Margin %']);foreach($rows as $x)fputcsv($out,[$x->product->item_code,$x->product->name,$x->product->category?->name,$x->product->brand?->name,$x->quantity,$x->sales,$x->cost,$x->profit,$x->margin]);fclose($out);},"product-profitability-{$from}-to-{$to}.csv",['Content-Type'=>'text/csv']);}
 public function exportInventory(Request $r,ManagementReportService $s):StreamedResponse{$rows=$s->inventory($r->user()->company_id);return response()->streamDownload(function()use($rows){$out=fopen('php://output','w');fputcsv($out,['Item Code','Product','Category','Brand','Quantity','Average Cost','Stock Value','Status']);foreach($rows as $x)fputcsv($out,[$x->product->item_code,$x->product->name,$x->product->category?->name,$x->product->brand?->name,$x->quantity,$x->average_cost,$x->value,$x->status]);fclose($out);},'inventory-valuation-'.now()->format('Y-m-d').'.csv',['Content-Type'=>'text/csv']);}
}
