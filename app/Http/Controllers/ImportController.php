<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportUploadRequest;
use App\Models\ImportBatch;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    private const HEADERS = ['supplier_code','supplier_name','supplier_phone','item_code','product_name','barcode','brand','unit','category','cost_price','retail_price','wholesale_price','minimum_stock','reorder_level','warranty_months','serial_tracking'];

    public function index(Request $request)
    {
        $batches = ImportBatch::where('company_id', $request->user()->company_id)->latest()->paginate(15);
        return view('imports.index', compact('batches'));
    }

    public function template()
    {
        $lines = [self::HEADERS, ['SUP-001','Demo Supplier','0110000000','ITEM-001','Demo Product','890000000001','Demo Brand','PCS','Electronics','1000.00','1250.00','1150.00','2','5','12','yes']];
        $callback = function () use ($lines) { $out = fopen('php://output', 'w'); foreach ($lines as $line) fputcsv($out, $line); fclose($out); };
        return response()->streamDownload($callback, 'supun-erp-master-data-template.csv', ['Content-Type'=>'text/csv']);
    }

    public function store(ImportUploadRequest $request)
    {
        [$handle, $delimiter] = $this->openCsv($request->file('file')->getRealPath());
        $headers = array_map(fn ($v) => Str::snake(trim((string) $v)), fgetcsv($handle, 0, $delimiter) ?: []);
        $missing = array_diff(['item_code','product_name','unit','category','cost_price','retail_price','wholesale_price'], $headers);
        if ($missing) return back()->withErrors(['file'=>'Missing required columns: '.implode(', ', $missing)]);
        $batch = ImportBatch::create(['uuid'=>(string)Str::uuid(),'company_id'=>$request->user()->company_id,'user_id'=>$request->user()->id,'type'=>'master_data','original_filename'=>$request->file('file')->getClientOriginalName(),'status'=>'validated']);
        $total=$valid=$invalid=0;
        while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (++$total > 5000) { fclose($handle); $batch->delete(); return back()->withErrors(['file'=>'The import is limited to 5,000 rows per batch.']); }
            $values = array_pad($values, count($headers), null); $data = array_combine($headers, array_slice($values, 0, count($headers)));
            if (!array_filter($data, fn($v)=>trim((string)$v)!=='')) { $total--; continue; }
            $errors = $this->validateRow($data, $request->user()->company_id); $status=$errors?'invalid':'valid'; $errors?$invalid++:$valid++;
            $batch->rows()->create(['row_number'=>$total+1,'data'=>$data,'errors'=>$errors?:null,'status'=>$status]);
        }
        fclose($handle); $batch->update(['total_rows'=>$total,'valid_rows'=>$valid,'invalid_rows'=>$invalid]);
        DB::table('import_history')->insert(['import_batch_id'=>$batch->id,'user_id'=>$request->user()->id,'action'=>'validated','summary'=>json_encode(compact('total','valid','invalid')),'created_at'=>now(),'updated_at'=>now()]);
        return redirect()->route('imports.show',$batch);
    }

    private function openCsv(string $path): array
    {
        $content = (string) file_get_contents($path);
        if (str_starts_with($content, "\xFF\xFE")) $content = iconv('UTF-16LE', 'UTF-8//IGNORE', substr($content, 2));
        elseif (str_starts_with($content, "\xFE\xFF")) $content = iconv('UTF-16BE', 'UTF-8//IGNORE', substr($content, 2));
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $firstLine = strtok($content, "\n") ?: '';
        $candidates = [',', ';', "\t"];
        $delimiter = collect($candidates)->sortByDesc(fn($candidate) => count(str_getcsv($firstLine, $candidate)))->first();
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);
        return [$handle, $delimiter];
    }

    public function show(Request $request, ImportBatch $batch)
    {
        abort_unless($batch->company_id === $request->user()->company_id, 404);
        return view('imports.show',['batch'=>$batch,'rows'=>$batch->rows()->orderBy('row_number')->paginate(30)]);
    }

    public function confirm(Request $request, ImportBatch $batch)
    {
        abort_unless($batch->company_id === $request->user()->company_id, 404);
        abort_if($batch->status !== 'validated', 422, 'This batch is not available for import.');
        if ($batch->invalid_rows > 0) return back()->withErrors(['import'=>'Correct every invalid row and upload a new file before importing.']);
        DB::transaction(function () use ($batch, $request) {
            foreach ($batch->rows()->where('status','valid')->orderBy('row_number')->cursor() as $row) $this->importRow($row->data, $batch->company_id, $request->user()->id);
            $batch->update(['status'=>'imported','confirmed_at'=>now()]);
            DB::table('import_history')->insert(['import_batch_id'=>$batch->id,'user_id'=>$request->user()->id,'action'=>'imported','summary'=>json_encode(['rows'=>$batch->valid_rows]),'created_at'=>now(),'updated_at'=>now()]);
        });
        return redirect()->route('imports.show',$batch)->with('success',"{$batch->valid_rows} rows imported successfully.");
    }

    private function validateRow(array $d, int $companyId): array
    {
        $e=[]; foreach(['item_code','product_name','unit','category'] as $f) if(trim((string)($d[$f]??''))==='')$e[]="$f is required";
        foreach(['cost_price','retail_price','wholesale_price'] as $f) if(!is_numeric($d[$f]??null)||$d[$f]<0)$e[]="$f must be a non-negative number";
        if (($d['barcode']??'')!=='' && DB::table('products')->where('company_id',$companyId)->where('barcode',(string)$d['barcode'])->where('item_code','!=',(string)($d['item_code']??''))->exists()) $e[]='barcode belongs to another product';
        return $e;
    }

    private function importRow(array $d, int $companyId, int $userId): void
    {
        $now=now();
        if(trim((string)($d['supplier_code']??''))!=='') DB::table('suppliers')->updateOrInsert(['company_id'=>$companyId,'code'=>(string)$d['supplier_code']],['name'=>$d['supplier_name']?:$d['supplier_code'],'phone'=>$d['supplier_phone']?:null,'is_active'=>true,'updated_at'=>$now,'created_at'=>$now]);
        foreach ([['product_categories','category','CAT'],['brands','brand','BRD'],['units','unit','UNIT']] as [$table,$field,$prefix]) {
            $name=trim((string)($d[$field]??'')); if($name==='')continue; $code=Str::upper(Str::slug($name,'-')); DB::table($table)->updateOrInsert(['company_id'=>$companyId,'code'=>Str::limit($code?:$prefix,50,'')],['name'=>$name,'is_active'=>true,'updated_at'=>$now,'created_at'=>$now]+($table==='units'?['decimal_places'=>0]:[]));
        }
        $categoryId=DB::table('product_categories')->where('company_id',$companyId)->where('code',Str::upper(Str::slug($d['category'],'-')))->value('id');
        $brandId=trim((string)($d['brand']??''))===''?null:DB::table('brands')->where('company_id',$companyId)->where('code',Str::upper(Str::slug($d['brand'],'-')))->value('id');
        $unitId=DB::table('units')->where('company_id',$companyId)->where('code',Str::upper(Str::slug($d['unit'],'-')))->value('id');
        $product=Product::updateOrCreate(['company_id'=>$companyId,'item_code'=>(string)$d['item_code']],['product_category_id'=>$categoryId,'brand_id'=>$brandId,'unit_id'=>$unitId,'barcode'=>($d['barcode']??'')!==''?(string)$d['barcode']:null,'name'=>$d['product_name'],'average_cost'=>$d['cost_price'],'minimum_stock'=>$d['minimum_stock']?:0,'reorder_level'=>$d['reorder_level']?:0,'warranty_months'=>$d['warranty_months']?:0,'serial_tracking'=>in_array(strtolower((string)($d['serial_tracking']??'')),['yes','true','1'],true),'is_active'=>true]);
        foreach(['retail','wholesale'] as $type) { $amount=$d[$type.'_price']; $current=$product->prices()->where('price_type',$type)->where('is_active',true)->first(); if(!$current||bccomp((string)$current->amount,(string)$amount,2)!==0){if($current)$current->update(['is_active'=>false,'effective_until'=>$now]);ProductPrice::create(['product_id'=>$product->id,'price_type'=>$type,'amount'=>$amount,'effective_from'=>$now,'is_active'=>true,'created_by'=>$userId]);}}
    }
}
