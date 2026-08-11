<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportUploadRequest;
use App\Models\ImportBatch;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\XlsxReader;
use App\Models\{AccountingPeriod,GoodsReceivedNote,PurchaseOrder,SupplierInvoice};
use App\Services\{DocumentNumberService,InventoryReceivingService,JournalPostingService};

class ImportController extends Controller
{
    private const HEADERS = ['supplier_code','supplier_name','supplier_phone','supplier_invoice_number','purchase_date','payment_due_date','item_code','product_name','barcode','brand','unit','category','cost_price','retail_price','wholesale_price','minimum_stock','reorder_level','warranty_months','serial_tracking','quantity'];

    public function index(Request $request)
    {
        $batches = ImportBatch::where('company_id', $request->user()->company_id)->latest()->paginate(15);
        return view('imports.index', compact('batches'));
    }

    public function template()
    {
        $lines = [self::HEADERS, ['SUP-001','Demo Supplier','0110000000','INV-001',now()->toDateString(),now()->addDays(30)->toDateString(),'ITEM-001','Demo Product','890000000001','Demo Brand','PCS','Electronics','1000.00','1250.00','1150.00','2','5','12','yes','10']];
        $callback = function () use ($lines) { $out = fopen('php://output', 'w'); foreach ($lines as $line) fputcsv($out, $line); fclose($out); };
        return response()->streamDownload($callback, 'supun-erp-master-data-template.csv', ['Content-Type'=>'text/csv']);
    }

    public function store(ImportUploadRequest $request)
    {
        $uploaded = $request->file('file');
        $excel = strtolower($uploaded->getClientOriginalExtension()) === 'xlsx';
        $handle = $delimiter = null;
        $excelRows = $excel ? app(XlsxReader::class)->rows($uploaded->getRealPath()) : null;
        if ($excel) $firstRow = array_shift($excelRows) ?: [];
        else { [$handle, $delimiter] = $this->openCsv($uploaded->getRealPath()); $firstRow = fgetcsv($handle, 0, $delimiter) ?: []; }
        $headers = array_map(fn ($v) => Str::snake(trim((string) $v)), $firstRow);
        $missing = array_diff(['item_code','product_name','unit','category','cost_price','retail_price','wholesale_price'], $headers);
        if ($missing) return back()->withErrors(['file'=>'Missing required columns: '.implode(', ', $missing)]);
        $batch = ImportBatch::create(['uuid'=>(string)Str::uuid(),'company_id'=>$request->user()->company_id,'user_id'=>$request->user()->id,'type'=>'master_data','original_filename'=>$request->file('file')->getClientOriginalName(),'status'=>'validated']);
        $total=$valid=$invalid=0;
        $nextRow = function() use ($excel,&$excelRows,$handle,$delimiter){return $excel?(count($excelRows)?array_shift($excelRows):false):fgetcsv($handle,0,$delimiter);};
        while (($values = $nextRow()) !== false) {
            if (++$total > 5000) { if(!$excel)fclose($handle); $batch->delete(); return back()->withErrors(['file'=>'The import is limited to 5,000 rows per batch.']); }
            $values = array_pad($values, count($headers), null); $data = array_combine($headers, array_slice($values, 0, count($headers)));
            if (!array_filter($data, fn($v)=>trim((string)$v)!=='')) { $total--; continue; }
            $errors = $this->validateRow($data, $request->user()->company_id); $status=$errors?'invalid':'valid'; $errors?$invalid++:$valid++;
            $batch->rows()->create(['row_number'=>$total+1,'data'=>$data,'errors'=>$errors?:null,'status'=>$status]);
        }
        if(!$excel)fclose($handle); $batch->update(['total_rows'=>$total,'valid_rows'=>$valid,'invalid_rows'=>$invalid]);
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
            $openingValue='0';foreach ($batch->rows()->where('status','valid')->orderBy('row_number')->cursor() as $row)$openingValue=bcadd($openingValue,$this->importRow($row->data,$batch->company_id,$request->user()->id,$batch->id),2);
            if(bccomp($openingValue,'0',2)>0){$postingDate=$this->openPostingDate($batch->company_id,now()->toDateString());app(\App\Services\JournalPostingService::class)->post($batch->company_id,$postingDate,ImportBatch::class,$batch->id,'IMPORT-'.$batch->uuid,'Opening inventory imported from '.$batch->original_filename,[['account_code'=>'1140','debit'=>$openingValue],['account_code'=>'3300','credit'=>$openingValue]],$request->user()->id);}
            $batch->update(['status'=>'imported','confirmed_at'=>now()]);
            DB::table('import_history')->insert(['import_batch_id'=>$batch->id,'user_id'=>$request->user()->id,'action'=>'imported','summary'=>json_encode(['rows'=>$batch->valid_rows]),'created_at'=>now(),'updated_at'=>now()]);
        });
        return redirect()->route('imports.show',$batch)->with('success',"{$batch->valid_rows} rows imported successfully.");
    }

    private function validateRow(array $d, int $companyId): array
    {
        $e=[]; foreach(['item_code','product_name','unit','category'] as $f) if(trim((string)($d[$f]??''))==='')$e[]="$f is required";
        foreach(['cost_price','retail_price','wholesale_price'] as $f) if(!is_numeric($d[$f]??null)||$d[$f]<0)$e[]="$f must be a non-negative number";
        $quantity=$d['quantity']??$d['opening_quantity']??'';if($quantity!==''&&(!is_numeric($quantity)||$quantity<0))$e[]='quantity must be a non-negative number';
        $productId=DB::table('products')->where('company_id',$companyId)->where('item_code',(string)($d['item_code']??''))->value('id');if($productId&&is_numeric($quantity)&&(float)$quantity>0&&trim((string)($d['supplier_code']??''))===''&&trim((string)($d['supplier_name']??''))===''&&trim((string)($d['supplier_phone']??''))==='')$e[]='supplier name or phone is required when replenishing an existing product';
        foreach(['purchase_date','payment_due_date'] as $field)if(($d[$field]??'')!==''&&strtotime($d[$field])===false)$e[]="$field must be a valid date";
        if (($d['barcode']??'')!=='' && DB::table('products')->where('company_id',$companyId)->where('barcode',(string)$d['barcode'])->where('item_code','!=',(string)($d['item_code']??''))->exists()) $e[]='barcode belongs to another product';
        return $e;
    }

    private function importRow(array $d, int $companyId, int $userId, int $batchId): string
    {
        $now=now();$existingProduct=Product::where('company_id',$companyId)->where('item_code',(string)$d['item_code'])->first();$hadMovements=$existingProduct&&DB::table('stock_movements')->where('product_id',$existingProduct->id)->exists();
        $supplier=$this->resolveSupplier($d,$companyId,$now);
        foreach ([['product_categories','category','CAT'],['brands','brand','BRD'],['units','unit','UNIT']] as [$table,$field,$prefix]) {
            $name=trim((string)($d[$field]??'')); if($name==='')continue; $code=Str::upper(Str::slug($name,'-')); DB::table($table)->updateOrInsert(['company_id'=>$companyId,'code'=>Str::limit($code?:$prefix,50,'')],['name'=>$name,'is_active'=>true,'updated_at'=>$now,'created_at'=>$now]+($table==='units'?['decimal_places'=>0]:[]));
        }
        $categoryId=DB::table('product_categories')->where('company_id',$companyId)->where('code',Str::upper(Str::slug($d['category'],'-')))->value('id');
        $brandId=trim((string)($d['brand']??''))===''?null:DB::table('brands')->where('company_id',$companyId)->where('code',Str::upper(Str::slug($d['brand'],'-')))->value('id');
        $unitId=DB::table('units')->where('company_id',$companyId)->where('code',Str::upper(Str::slug($d['unit'],'-')))->value('id');
        $barcode=trim((string)($d['barcode']??''));if($barcode!==''&&(preg_match('/^[+-]?\d+(?:\.\d+)?E[+-]?\d+$/i',$barcode)||DB::table('products')->where('company_id',$companyId)->where('barcode',$barcode)->when($existingProduct,fn($q)=>$q->where('id','!=',$existingProduct->id))->exists()))$barcode='';$productData=['product_category_id'=>$categoryId,'brand_id'=>$brandId,'unit_id'=>$unitId,'barcode'=>$barcode!==''?$barcode:null,'name'=>$d['product_name'],'minimum_stock'=>$d['minimum_stock']?:0,'reorder_level'=>$d['reorder_level']?:0,'warranty_months'=>$d['warranty_months']?:0,'serial_tracking'=>in_array(strtolower((string)($d['serial_tracking']??'')),['yes','true','1'],true),'is_active'=>true];if(!$hadMovements)$productData['average_cost']=$d['cost_price'];$product=Product::updateOrCreate(['company_id'=>$companyId,'item_code'=>(string)$d['item_code']],$productData);
        foreach(['retail','wholesale'] as $type){$amount=$d[$type.'_price'];$current=$product->prices()->where('price_type',$type)->where('is_active',true)->first();if(!$current||bccomp((string)$current->amount,(string)$amount,2)!==0){$effective=now();while($product->prices()->where('price_type',$type)->where('effective_from',$effective)->exists())$effective->addSecond();if($current)$current->update(['is_active'=>false,'effective_until'=>$effective]);ProductPrice::create(['product_id'=>$product->id,'price_type'=>$type,'amount'=>$amount,'effective_from'=>$effective,'is_active'=>true,'created_by'=>$userId]);}}
        $quantity=(string)($d['quantity']??$d['opening_quantity']??0);if(!is_numeric($quantity)||bccomp($quantity,'0',4)<=0)return'0';if($hadMovements)$d['purchase_date']=$this->openPostingDate($companyId,($d['purchase_date']??'')?:now()->toDateString());
        $location=DB::table('stock_locations')->where('company_id',$companyId)->where('is_default',1)->value('id');$value=bcmul($quantity,(string)$d['cost_price'],2);
        if($hadMovements){if(!$supplier)throw \Illuminate\Validation\ValidationException::withMessages(['supplier'=>'Supplier name or phone is required for stock replenishment.']);$date=($d['purchase_date']??'')?:now()->toDateString();$due=($d['payment_due_date']??'')?:date('Y-m-d',strtotime($date.' +30 days'));$numbers=app(DocumentNumberService::class);$po=PurchaseOrder::create(['company_id'=>$companyId,'supplier_id'=>$supplier->id,'created_by'=>$userId,'document_number'=>$numbers->next($companyId,'purchase_order','PO',date('Y',strtotime($date))),'order_date'=>$date,'expected_date'=>$date,'status'=>'received','subtotal'=>$value,'total_amount'=>$value,'notes'=>'Created by replenishment import']);$poItem=$po->items()->create(['product_id'=>$product->id,'quantity'=>$quantity,'received_quantity'=>$quantity,'unit_cost'=>$d['cost_price'],'line_total'=>$value]);$grn=GoodsReceivedNote::create(['company_id'=>$companyId,'purchase_order_id'=>$po->id,'supplier_id'=>$supplier->id,'stock_location_id'=>$location,'received_by'=>$userId,'document_number'=>$numbers->next($companyId,'grn','GRN',date('Y',strtotime($date))),'supplier_invoice_number'=>($d['supplier_invoice_number']??'')?:'IMPORT-'.$batchId.'-'.$product->id,'received_date'=>$date,'status'=>'posted','total_cost'=>$value,'notes'=>'Created by replenishment import','posted_at'=>$now]);$result=app(InventoryReceivingService::class)->receive($product,$companyId,$location,$quantity,$d['cost_price'],GoodsReceivedNote::class,$grn->id,$grn->document_number,$userId);$grn->items()->create(['purchase_order_item_id'=>$poItem->id,'product_id'=>$product->id,'quantity'=>$quantity,'unit_cost'=>$d['cost_price'],'line_total'=>$value,'average_cost_after'=>$result['average_cost']]);$invoice=SupplierInvoice::create(['company_id'=>$companyId,'supplier_id'=>$supplier->id,'goods_received_note_id'=>$grn->id,'document_number'=>$numbers->next($companyId,'supplier_invoice','PI'),'supplier_invoice_number'=>($d['supplier_invoice_number']??'')?:'IMPORT-'.$batchId.'-'.$product->id,'invoice_date'=>$date,'due_date'=>$due,'total_amount'=>$value,'balance_amount'=>$value,'status'=>'posted','payment_status'=>'unpaid']);app(JournalPostingService::class)->post($companyId,$date,SupplierInvoice::class,$invoice->id,$invoice->document_number,'Imported inventory purchase '.$invoice->document_number,[['account_code'=>'1140','debit'=>$value],['account_code'=>'2100','credit'=>$value,'supplier_id'=>$supplier->id]],$userId);return'0';}
        $product->update(['current_quantity'=>$quantity]);DB::table('stock_movements')->insert(['company_id'=>$companyId,'product_id'=>$product->id,'stock_location_id'=>$location,'created_by'=>$userId,'movement_at'=>$now,'movement_type'=>'opening','reference_type'=>ImportBatch::class,'reference_id'=>$batchId,'reference_number'=>'IMPORT-'.$batchId,'quantity_in'=>$quantity,'quantity_out'=>0,'balance_quantity'=>$quantity,'unit_cost'=>$d['cost_price'],'stock_value'=>$value,'notes'=>'Opening stock from master-data import','created_at'=>$now,'updated_at'=>$now]);return$value;
    }

    private function resolveSupplier(array $data, int $companyId, $now): ?object
    {
        $code=trim((string)($data['supplier_code']??''));
        $name=trim((string)($data['supplier_name']??''));
        $phone=preg_replace('/\s+/', '', trim((string)($data['supplier_phone']??'')));
        if($code===''&&$name===''&&$phone==='')return null;

        $supplier=null;
        if($code!=='')$supplier=DB::table('suppliers')->where('company_id',$companyId)->where('code',$code)->first();
        if(!$supplier&&$phone!=='')$supplier=DB::table('suppliers')->where('company_id',$companyId)->whereRaw("REPLACE(COALESCE(phone,''),' ','') = ?",[$phone])->first();
        if(!$supplier&&$name!=='')$supplier=DB::table('suppliers')->where('company_id',$companyId)->whereRaw('LOWER(TRIM(name)) = ?', [Str::lower($name)])->first();

        if($supplier){DB::table('suppliers')->where('id',$supplier->id)->update(array_filter(['name'=>$name?:null,'phone'=>$phone?:null],fn($v)=>$v!==null)+['is_active'=>true,'updated_at'=>$now]);return DB::table('suppliers')->where('id',$supplier->id)->first();}
        if($code==='')$code=$this->nextSupplierCode($companyId);
        $id=DB::table('suppliers')->insertGetId(['company_id'=>$companyId,'code'=>$code,'name'=>$name?:$code,'phone'=>$phone?:null,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now]);
        return DB::table('suppliers')->where('id',$id)->first();
    }

    private function nextSupplierCode(int $companyId): string
    {
        $codes=DB::table('suppliers')->where('company_id',$companyId)->lockForUpdate()->pluck('code');
        $highest=0;foreach($codes as $existing)if(preg_match('/^SUP-(\d+)$/i',(string)$existing,$match))$highest=max($highest,(int)$match[1]);
        do{$code='SUP-'.str_pad((string)++$highest,3,'0',STR_PAD_LEFT);}while(DB::table('suppliers')->where('company_id',$companyId)->where('code',$code)->exists());
        return $code;
    }

    private function openPostingDate(int $companyId,string $requested):string
    {
        $period=AccountingPeriod::whereHas('financialYear',fn($q)=>$q->where('company_id',$companyId))->where('status','open')->whereDate('starts_on','<=',$requested)->whereDate('ends_on','>=',$requested)->first();
        if($period)return $requested;
        $period=AccountingPeriod::whereHas('financialYear',fn($q)=>$q->where('company_id',$companyId))->where('status','open')->orderByDesc('ends_on')->first();
        if(!$period)throw \Illuminate\Validation\ValidationException::withMessages(['entry_date'=>'No open accounting period is available. Create or reopen a period before importing stock.']);
        $today=now()->toDateString();return $today>=$period->starts_on->toDateString()&&$today<=$period->ends_on->toDateString()?$today:$period->ends_on->toDateString();
    }
}
