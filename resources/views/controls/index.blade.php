@extends('layouts.app')
@section('title','Release & Control Center')
@section('content')
<div class="d-flex justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 page-title">Release & Control Center</h1>
        <p class="text-muted">Operational safeguards, notifications, backup status and production readiness.</p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-start">
        @if($backup && $backup->status === 'completed')
            <a class="btn btn-outline-success" href="{{ route('controls.backups.download', $backup) }}">
                <i class="bi bi-download"></i> Download database (.sql)
            </a>
        @endif
        <form method="POST" action="{{ route('controls.backup') }}">
            @csrf
            <button class="btn btn-primary" onclick="return confirm('Create a verified database backup now?')">
                <i class="bi bi-database-down"></i> Create backup
            </button>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <div class="card">
            <div class="card-body">
                <h2 class="h5">Release health</h2>
                <div class="list-group list-group-flush">
                    @foreach($checks as [$name,$passed,$detail])
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <div><strong>{{ $name }}</strong><div class="small text-muted">{{ $detail }}</div></div>
                            <span class="badge {{ $passed ? 'text-bg-success' : 'text-bg-warning' }}">{{ $passed ? 'Passed' : 'Action needed' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5">Control summary</h2>
                <div class="row text-center mt-4">
                    <div class="col"><div class="h2">{{ $pending }}</div><div class="small text-muted">Pending approvals</div></div>
                    <div class="col"><div class="h2">{{ $notifications->whereIn('severity',['warning','danger'])->count() }}</div><div class="small text-muted">Active warnings</div></div>
                </div>
                <hr>
                <div class="small text-muted">Latest backup</div>
                @if($backup)
                    <strong>{{ $backup->filename }}</strong>
                    <div>{{ str($backup->status)->headline() }} · {{ number_format($backup->size_bytes / 1024, 1) }} KB</div>
                    @if($backup->status === 'completed')
                        <a class="btn btn-sm btn-outline-success mt-3" href="{{ route('controls.backups.download', $backup) }}">
                            <i class="bi bi-download"></i> Save SQL to this PC
                        </a>
                    @endif
                @else
                    <div>No backup recorded.</div>
                @endif
                <div class="mt-3">
                    <a href="{{ route('controls.periods') }}">Accounting periods</a> ·
                    <a href="{{ route('controls.approvals') }}">Approvals</a> ·
                    <a href="{{ route('controls.audit') }}">Audit log</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h2 class="h5">Operational notifications</h2>
        <div class="row g-3 mt-1">
            @foreach($notifications as $n)
                <div class="col-md-6">
                    <a href="{{ $n->action_url }}" class="alert alert-{{ $n->severity === 'danger' ? 'danger' : ($n->severity === 'warning' ? 'warning' : 'info') }} d-block text-decoration-none mb-0">
                        <strong>{{ $n->title }}</strong><div>{{ $n->message }}</div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
