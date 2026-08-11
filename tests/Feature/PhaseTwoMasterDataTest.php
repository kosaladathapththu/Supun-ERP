<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use App\Models\ImportBatch;
use Tests\TestCase;
use ZipArchive;
use App\Services\XlsxReader;

class PhaseTwoMasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_log_in_and_see_dashboard(): void
    {
        $this->post('/login', ['email' => 'admin@supun-erp.local', 'password' => 'ChangeMe!2026'])
            ->assertRedirect(route('password.edit'));
        $this->put(route('password.update'), ['current_password'=>'ChangeMe!2026','password'=>'Changed!2026Secure','password_confirmation'=>'Changed!2026Secure'])
            ->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertOk()->assertSee('Business overview');
    }

    public function test_admin_can_create_master_data_and_product_with_price_history(): void
    {
        $admin = User::where('email', 'admin@supun-erp.local')->firstOrFail();
        $this->actingAs($admin);
        $this->post(route('categories.store'), ['code' => 'MOB', 'name' => 'Mobile Phones', 'is_active' => 1])->assertRedirect(route('categories.index'));
        $categoryId = DB::table('product_categories')->where('code', 'MOB')->value('id');
        $unitId = DB::table('units')->where('code', 'PCS')->value('id');
        $this->post(route('products.store'), [
            'item_code'=>'PHONE-001','barcode'=>'890000000001','name'=>'Demo Phone','product_category_id'=>$categoryId,
            'unit_id'=>$unitId,'average_cost'=>50000,'retail_price'=>60000,'wholesale_price'=>57000,
            'minimum_stock'=>2,'reorder_level'=>5,'warranty_months'=>12,'serial_tracking'=>1,'is_active'=>1,
        ])->assertRedirect(route('products.index'));
        $productId = DB::table('products')->where('item_code', 'PHONE-001')->value('id');
        $this->assertDatabaseHas('products', ['id'=>$productId,'serial_tracking'=>1]);
        $this->assertDatabaseCount('product_prices', 2);
    }

    public function test_admin_can_create_customer_and_supplier(): void
    {
        $this->actingAs(User::where('email', 'admin@supun-erp.local')->firstOrFail());
        $this->post(route('customers.store'), ['code'=>'CUS-001','name'=>'Test Customer','phone'=>'0770000000','customer_type'=>'RET','default_due_term'=>'30_days','credit_enabled'=>1,'is_active'=>1])->assertSessionHasNoErrors();
        $this->post(route('suppliers.store'), ['code'=>'SUP-001','name'=>'Test Supplier','phone'=>'0110000000','is_active'=>1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customers', ['code'=>'CUS-001','credit_enabled'=>1]);
        $this->assertDatabaseHas('suppliers', ['code'=>'SUP-001']);
    }

    public function test_admin_can_validate_preview_and_confirm_master_data_csv(): void
    {
        $this->actingAs(User::where('email', 'admin@supun-erp.local')->firstOrFail());
        $csv = implode("\n", [
            'supplier_code,supplier_name,supplier_phone,item_code,product_name,barcode,brand,unit,category,cost_price,retail_price,wholesale_price,minimum_stock,reorder_level,warranty_months,serial_tracking,opening_quantity',
            'SUP-CSV,CSV Supplier,0111111111,CSV-001,Imported Phone,001234567890,Acme,PCS,Phones,1000,1250,1150,2,5,12,yes,10',
        ]);
        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);
        $response = $this->post(route('imports.store'), ['file'=>$file]);
        $batch = ImportBatch::firstOrFail();
        $response->assertRedirect(route('imports.show', $batch));
        $this->assertSame(1, $batch->valid_rows);
        $this->post(route('imports.confirm', $batch))->assertRedirect(route('imports.show', $batch));
        $this->assertDatabaseHas('products', ['item_code'=>'CSV-001','barcode'=>'001234567890']);
        $this->assertDatabaseHas('products',['item_code'=>'CSV-001','current_quantity'=>10]);
        $this->assertDatabaseHas('stock_movements',['movement_type'=>'opening','quantity_in'=>10]);
        $this->assertDatabaseHas('journal_entries',['source_type'=>ImportBatch::class,'source_id'=>$batch->id]);
        $this->assertDatabaseHas('suppliers', ['code'=>'SUP-CSV']);
        $this->assertSame('imported', $batch->fresh()->status);

        $replenishment=implode("\n",[
            'supplier_code,supplier_name,supplier_phone,supplier_invoice_number,purchase_date,payment_due_date,item_code,product_name,barcode,brand,unit,category,cost_price,retail_price,wholesale_price,minimum_stock,reorder_level,warranty_months,serial_tracking,quantity',
            'SUP-CSV,CSV Supplier,0111111111,SINV-200,2035-08-11,,CSV-001,Imported Phone,001234567890,Acme,PCS,Phones,1200,1400,1300,2,5,12,yes,5',
        ]);
        $this->post(route('imports.store'),['file'=>UploadedFile::fake()->createWithContent('replenishment.csv',$replenishment)])->assertSessionHasNoErrors();
        $restockBatch=ImportBatch::latest('id')->firstOrFail();$this->post(route('imports.confirm',$restockBatch))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products',['item_code'=>'CSV-001','current_quantity'=>15]);
        $this->assertDatabaseHas('purchase_orders',['supplier_id'=>DB::table('suppliers')->where('code','SUP-CSV')->value('id'),'status'=>'received']);
        $this->assertDatabaseHas('goods_received_notes',['supplier_invoice_number'=>'SINV-200','status'=>'posted']);
        $this->assertDatabaseHas('supplier_invoices',['supplier_invoice_number'=>'SINV-200','total_amount'=>6000,'balance_amount'=>6000,'payment_status'=>'unpaid']);
        $invoice=\App\Models\SupplierInvoice::where('supplier_invoice_number','SINV-200')->firstOrFail();$this->assertDatabaseHas('journal_entries',['source_type'=>\App\Models\SupplierInvoice::class,'source_id'=>$invoice->id]);
    }

    public function test_excel_csv_with_carriage_return_rows_validates_every_data_row(): void
    {
        $admin = User::where('email', 'admin@supun-erp.local')->firstOrFail();
        $header = 'supplier_code,supplier_name,supplier_phone,item_code,product_name,barcode,brand,unit,category,cost_price,retail_price,wholesale_price,minimum_stock,reorder_level,warranty_months,serial_tracking';
        $rows = [
            'SUP-001,Demo Supplier,0110000000,ITEM-001,Demo Product,890000000001,Demo Brand,PCS,Electronics,1000,1250,1150,2,5,12,yes',
            'SUP-002,Fuji,123654987,ITEM-002,Rice cooker,890000000002,Fuji,PCS,Electronics,9000,15000,13000,2,5,12,yes',
            'SUP-003,National,987456321,ITEM-003,Water Filter,890000000003,National,PCS,Filters,18000,25000,22000,2,5,12,yes',
        ];
        $file = UploadedFile::fake()->createWithContent('excel-export.csv', $header."\r".implode("\r", $rows));
        $this->actingAs($admin)->post(route('imports.store'), ['file'=>$file])->assertSessionHasNoErrors();
        $batch = ImportBatch::latest('id')->firstOrFail();
        $this->assertSame(3, $batch->total_rows);
        $this->assertSame(3, $batch->valid_rows);
        $this->assertSame(0, $batch->invalid_rows);
        $this->assertCount(3, $batch->rows);
    }

    public function test_native_excel_workbook_validates_every_row(): void
    {
        $admin = User::where('email', 'admin@supun-erp.local')->firstOrFail();
        $rows = [
            ['supplier_code','supplier_name','supplier_phone','item_code','product_name','barcode','brand','unit','category','cost_price','retail_price','wholesale_price','minimum_stock','reorder_level','warranty_months','serial_tracking'],
            ['SUP-X1','Excel Supplier','0110000000','XLSX-001','Excel Phone','001234567890','Acme','PCS','Phones','1000','1250','1150','2','5','12','yes'],
            ['SUP-X2','Excel Supplier 2','0770000000','XLSX-002','Excel TV','009876543210','Acme','PCS','TV','2000','2500','2300','2','5','12','no'],
        ];
        $path = tempnam(sys_get_temp_dir(), 'erp-xlsx-');
        $zip = new ZipArchive(); $zip->open($path, ZipArchive::CREATE|ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml','<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml','<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Import" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $sheet='<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach($rows as $r=>$values){$sheet.='<row r="'.($r+1).'">';foreach($values as $c=>$value){$column=chr(65+$c);$sheet.='<c r="'.$column.($r+1).'" t="inlineStr"><is><t>'.htmlspecialchars($value,ENT_XML1).'</t></is></c>';}$sheet.='</row>';}$sheet.='</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml',$sheet);$zip->close();
        $this->assertCount(3,app(XlsxReader::class)->rows($path));
        $file = new UploadedFile($path,'products.xlsx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',null,true);
        $this->actingAs($admin)->post(route('imports.store'),['file'=>$file])->assertSessionHasNoErrors()->assertRedirect();
        $batch=ImportBatch::latest('id')->firstOrFail();
        $this->assertSame(2,$batch->total_rows);$this->assertSame(2,$batch->valid_rows);$this->assertSame('001234567890',$batch->rows()->first()->data['barcode']);
    }
}
