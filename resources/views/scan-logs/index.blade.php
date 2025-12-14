<!-- resources/views/scan-logs/index.blade.php -->
@extends('layouts.app')

@section('title', 'Scan Logs - RFID WMS')

@section('content')
<div class="mb-4">
    <h1 class="h2 mb-0">
        <i class="fas fa-history text-primary"></i> Scan History
    </h1>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Operation Type</label>
                <select class="form-select" id="operationFilter">
                    <option value="">All Operations</option>
                    <option value="receiving">Receiving</option>
                    <option value="picking">Picking</option>
                    <option value="shipping">Shipping</option>
                    <option value="count">Count</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" class="form-control" id="dateFrom">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" class="form-control" id="dateTo">
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-primary w-100" onclick="applyFilters()">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scan Logs Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Product</th>
                        <th>EPC Code</th>
                        <th>Operation</th>
                        <th>Quantity</th>
                        <th>Device</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                        <td>{{ $log->product->name ?? 'Unknown' }}</td>
                        <td><code style="font-size: 0.75rem;">{{ substr($log->epc_code, 0, 20) }}...</code></td>
                        <td>
                            <span class="badge 
                                @if($log->operation_type == 'receiving') bg-success
                                @elseif($log->operation_type == 'picking') bg-primary
                                @elseif($log->operation_type == 'shipping') bg-info
                                @else bg-secondary
                                @endif">
                                {{ $log->operation_type }}
                            </span>
                        </td>
                        <td>{{ $log->quantity }}</td>
                        <td>{{ $log->device_id ?? '-' }}</td>
                        <td>{{ $log->scanned_by ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No scan logs found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function applyFilters() {
        const operation = document.getElementById('operationFilter').value;
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;

        let url = '/scan-logs?';
        if (operation) url += `operation_type=${operation}&`;
        if (dateFrom) url += `from_date=${dateFrom}&`;
        if (dateTo) url += `to_date=${dateTo}&`;

        window.location.href = url;
    }
</script>
@endpush