@extends('layouts.app')
@section('title', 'Bulk Inventory Import')

@push('styles')
<style>
    .import-hero { display:flex; justify-content:space-between; align-items:center; gap:20px; margin-bottom:20px; }
    .import-guide { display:grid; grid-template-columns:repeat(3,1fr); gap:0; margin-bottom:20px; overflow:hidden; border:1px solid #dbe4ef; border-radius:12px; background:#fff; }
    .import-step { display:flex; align-items:center; gap:12px; padding:15px 18px; border-right:1px solid #dbe4ef; }
    .import-step:last-child { border-right:0; }
    .import-step-number { display:grid; place-items:center; flex:0 0 34px; height:34px; border-radius:50%; background:#eaf1ff; color:#185adb; font-weight:800; }
    .import-step strong { display:block; color:#102548; }
    .import-step small { color:#64748b; }
    .upload-panel { height:100%; border-top:4px solid #2563eb; }
    .upload-zone { display:block; padding:24px 18px; border:2px dashed #b8c7dc; border-radius:12px; background:#f8fbff; text-align:center; cursor:pointer; transition:.18s ease; }
    .upload-zone:hover { border-color:#2563eb; background:#f1f6ff; }
    .upload-zone-icon { display:grid; place-items:center; width:52px; height:52px; margin:0 auto 10px; border-radius:50%; background:#e5efff; color:#2563eb; font-size:1.45rem; }
    .upload-zone input { width:100%; max-width:410px; margin:14px auto 0; }
    .import-note { display:flex; gap:10px; padding:13px; border-radius:9px; background:#f8fafc; color:#475569; }
    .history-card { overflow:hidden; }
    .history-heading { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:20px 22px; border-bottom:1px solid #e2e8f0; }
    .history-table th { white-space:nowrap; font-size:.78rem; }
    .history-file { max-width:270px; overflow-wrap:anywhere; }
    .history-date { white-space:nowrap; }
    .status-badge { min-width:78px; padding:.42rem .65rem; text-align:center; }
    @media (max-width:900px) {
        .import-hero { align-items:flex-start; flex-direction:column; }
        .import-guide { grid-template-columns:1fr; }
        .import-step { border-right:0; border-bottom:1px solid #dbe4ef; }
        .import-step:last-child { border-bottom:0; }
    }
</style>
@endpush

@section('content')
<div class="import-hero">
    <div>
        <h1 class="h3 page-title mb-1">Bulk Product & Inventory Import</h1>
        <p class="text-muted mb-0">Add new products or replenish existing stock using one Excel or CSV file.</p>
    </div>
    <a class="btn btn-outline-primary btn-lg" href="{{ route('imports.template') }}">
        <i class="bi bi-file-earmark-arrow-down me-1"></i> Download Template
    </a>
</div>

<div class="import-guide" aria-label="Import process">
    <div class="import-step">
        <span class="import-step-number">1</span>
        <div><strong>Download template</strong><small>Use the correct column structure</small></div>
    </div>
    <div class="import-step">
        <span class="import-step-number">2</span>
        <div><strong>Complete and upload</strong><small>Excel .xlsx or CSV, up to 5,000 rows</small></div>
    </div>
    <div class="import-step">
        <span class="import-step-number">3</span>
        <div><strong>Review and confirm</strong><small>Nothing is imported before confirmation</small></div>
    </div>
</div>

<div class="alert alert-info d-flex align-items-start gap-2 mb-4">
    <i class="bi bi-info-circle-fill mt-1"></i>
    <div><strong>Quantity rule:</strong> New product quantities become opening stock. For an existing item code, the quantity is added as new stock and the related purchase, GRN, supplier invoice and payable are created automatically.</div>
</div>

<div class="row g-4 align-items-stretch">
    <div class="col-xl-5">
        <div class="card upload-panel">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-1">Upload completed file</h2>
                <p class="text-muted mb-3">Select your completed inventory template. Your data will be validated before importing.</p>

                <form method="POST" action="{{ route('imports.store') }}" enctype="multipart/form-data">
                    @csrf
                    <label class="upload-zone" for="inventory-import-file">
                        <span class="upload-zone-icon"><i class="bi bi-cloud-arrow-up"></i></span>
                        <strong class="d-block">Choose an Excel or CSV file</strong>
                        <span class="small text-muted">Accepted: .xlsx and .csv · Maximum size: 10 MB</span>
                        <input id="inventory-import-file" class="form-control" type="file" name="file" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required>
                    </label>
                    <button class="btn btn-primary btn-lg w-100 mt-3">
                        <i class="bi bi-check2-circle me-1"></i> Validate and Preview File
                    </button>
                </form>

                <div class="import-note mt-3 small">
                    <i class="bi bi-person-check fs-5 text-primary"></i>
                    <div><strong>Supplier matching</strong><br>Supplier code is optional. The system matches by phone or name, or creates the next SUP-### code.</div>
                </div>
                <div class="import-note mt-2 small">
                    <i class="bi bi-upc-scan fs-5 text-primary"></i>
                    <div><strong>Keep codes accurate</strong><br>Format item codes, phone numbers and barcodes as Text in Excel.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card history-card">
            <div class="history-heading">
                <div>
                    <h2 class="h5 fw-bold mb-1">Import history</h2>
                    <p class="small text-muted mb-0">Open a previous batch to review its products and result.</p>
                </div>
                <span class="badge text-bg-light border">{{ $batches->total() }} batches</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle history-table mb-0">
                    <thead>
                        <tr><th class="ps-4">File</th><th>Valid rows</th><th>Status</th><th>Date</th><th class="text-end pe-4">Action</th></tr>
                    </thead>
                    <tbody>
                    @forelse($batches as $batch)
                        @php($statusColour=match($batch->status){'imported'=>'text-bg-success','validated'=>'text-bg-primary','cancelled'=>'text-bg-danger','invalid','failed'=>'text-bg-danger',default=>'text-bg-secondary'})
                        <tr>
                            <td class="ps-4 history-file">
                                <a href="{{ route('imports.show',$batch) }}" class="fw-semibold text-decoration-none">{{ $batch->original_filename }}</a>
                            </td>
                            <td><strong>{{ $batch->valid_rows }}</strong> / {{ $batch->total_rows }}</td>
                            <td><span class="badge status-badge {{ $statusColour }}">{{ ucfirst($batch->status) }}</span></td>
                            <td class="history-date"><span class="d-block">{{ $batch->created_at->format('d M Y') }}</span><small class="text-muted">{{ $batch->created_at->format('h:i A') }}</small></td>
                            <td class="text-end pe-4">
                                <a href="{{ route('imports.show',$batch) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i> Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No imports have been uploaded yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($batches->hasPages())<div class="p-3 border-top">{{ $batches->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
