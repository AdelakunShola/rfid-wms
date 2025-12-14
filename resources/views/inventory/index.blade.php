<!-- resources/views/inventory/index.blade.php -->
@extends('layouts.app')

@section('title', 'Inventory - RFID WMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">
        <i class="fas fa-warehouse text-primary"></i> Inventory Overview
    </h1>
    <button class="btn btn-primary" onclick="refreshInventory()">
        <i class="fas fa-sync"></i> Refresh
    </button>
</div>

<!-- Summary Statistics -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                    <i class="fas fa-box"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Products</div>
                    <h3 class="mb-0">{{ $totalProducts }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                    <i class="fas fa-cubes"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Quantity</div>
                    <h3 class="mb-0">{{ number_format($totalQuantity) }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Value</div>
                    <h3 class="mb-0">${{ number_format($totalValue, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Table -->
<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="searchInput" class="form-control" 
                   placeholder="Search inventory by product name or SKU...">
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="inventoryTable">
                    @foreach($products as $product)
                    <tr>
                        <td><strong>{{ $product->sku }}</strong></td>
                        <td>{{ $product->name }}</td>
                        <td>
                            <span class="badge 
                                @if($product->quantity < 20) bg-danger
                                @elseif($product->quantity < 50) bg-warning
                                @else bg-success
                                @endif">
                                {{ $product->quantity }}
                            </span>
                        </td>
                        <td>${{ number_format($product->price, 2) }}</td>
                        <td>${{ number_format($product->quantity * $product->price, 2) }}</td>
                        <td>
                            @if($product->quantity < 20)
                                <span class="badge bg-danger">Low Stock</span>
                            @elseif($product->quantity < 50)
                                <span class="badge bg-warning">Medium</span>
                            @else
                                <span class="badge bg-success">In Stock</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('input', (e) => {
        const search = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#inventoryTable tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(search) ? '' : 'none';
        });
    });

    function refreshInventory() {
        location.reload();
    }
</script>
@endpush

