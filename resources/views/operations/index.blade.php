<!-- resources/views/operations/index.blade.php -->
@extends('layouts.app')

@section('title', 'Warehouse Operations - RFID WMS')

@section('content')
<div class="mb-4">
    <h1 class="h2 mb-0">
        <i class="fas fa-tasks text-primary"></i> Warehouse Operations
    </h1>
</div>

<div class="row g-4">
    <!-- Receiving -->
    <div class="col-md-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success mx-auto mb-3">
                    <i class="fas fa-truck-loading"></i>
                </div>
                <h5 class="card-title">Receiving</h5>
                <p class="card-text text-muted">Scan incoming items to add to inventory</p>
                <button class="btn btn-success" onclick="startOperation('receiving')">
                    <i class="fas fa-barcode"></i> Start Receiving
                </button>
            </div>
        </div>
    </div>

    <!-- Picking -->
    <div class="col-md-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary mx-auto mb-3">
                    <i class="fas fa-box-open"></i>
                </div>
                <h5 class="card-title">Picking</h5>
                <p class="card-text text-muted">Scan items for order fulfillment</p>
                <button class="btn btn-primary" onclick="startOperation('picking')">
                    <i class="fas fa-barcode"></i> Start Picking
                </button>
            </div>
        </div>
    </div>

    <!-- Shipping -->
    <div class="col-md-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-icon bg-info bg-opacity-10 text-info mx-auto mb-3">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <h5 class="card-title">Shipping</h5>
                <p class="card-text text-muted">Scan items being shipped out</p>
                <button class="btn btn-info" onclick="startOperation('shipping')">
                    <i class="fas fa-barcode"></i> Start Shipping
                </button>
            </div>
        </div>
    </div>

    <!-- Inventory Count -->
    <div class="col-md-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-icon bg-secondary bg-opacity-10 text-secondary mx-auto mb-3">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <h5 class="card-title">Inventory Count</h5>
                <p class="card-text text-muted">Perform physical inventory count</p>
                <button class="btn btn-secondary" onclick="startOperation('count')">
                    <i class="fas fa-barcode"></i> Start Count
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Active Operation Panel -->
<div class="card mt-4" id="operationPanel" style="display: none;">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">
                <span class="badge bg-primary" id="operationType">Operation</span>
                Active Operation
            </h5>
            <button class="btn btn-sm btn-danger" onclick="stopOperation()">
                <i class="fas fa-stop"></i> Stop Operation
            </button>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="text-muted small">Items Scanned</div>
                    <h3 class="mb-0" id="itemsScanned">0</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="text-muted small">Unique Products</div>
                    <h3 class="mb-0" id="uniqueProducts">0</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="text-muted small">Duration</div>
                    <h3 class="mb-0" id="operationDuration">00:00</h3>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Product</th>
                        <th>EPC Code</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody id="operationItems">
                    <tr>
                        <td colspan="4" class="text-center text-muted">
                            Waiting for scans from handheld device...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Operation Summary Modal -->
<div class="modal fade" id="summaryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Operation Summary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="summaryContent">
                <!-- Summary will be inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="exportSummary()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let activeOperation = null;
    let operationStartTime = null;
    let scannedItems = [];
    let durationInterval = null;

    function startOperation(type) {
        activeOperation = type;
        operationStartTime = new Date();
        scannedItems = [];

        document.getElementById('operationPanel').style.display = 'block';
        document.getElementById('operationType').textContent = type.charAt(0).toUpperCase() + type.slice(1);
        
        updateOperationStats();
        
        // Start duration timer
        durationInterval = setInterval(updateDuration, 1000);

        alert(`${type.charAt(0).toUpperCase() + type.slice(1)} operation started. Use your handheld device to scan items.`);
    }

    function stopOperation() {
        if (!activeOperation) return;

        clearInterval(durationInterval);

        // Show summary
        showOperationSummary();

        // Reset
        activeOperation = null;
        operationStartTime = null;
        document.getElementById('operationPanel').style.display = 'none';
    }

    function updateDuration() {
        if (!operationStartTime) return;

        const now = new Date();
        const diff = Math.floor((now - operationStartTime) / 1000);
        const minutes = Math.floor(diff / 60);
        const seconds = diff % 60;

        document.getElementById('operationDuration').textContent = 
            `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }

    function addScannedItem(item) {
        scannedItems.push({
            ...item,
            scanned_at: new Date().toISOString()
        });

        updateOperationStats();
        updateOperationTable();
    }

    function updateOperationStats() {
        document.getElementById('itemsScanned').textContent = scannedItems.length;
        
        const uniqueProducts = new Set(scannedItems.map(item => item.product_id));
        document.getElementById('uniqueProducts').textContent = uniqueProducts.size;
    }

    function updateOperationTable() {
        const tbody = document.getElementById('operationItems');
        
        if (scannedItems.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No items scanned yet</td></tr>';
            return;
        }

        tbody.innerHTML = scannedItems.slice(-10).reverse().map(item => {
            const time = new Date(item.scanned_at).toLocaleTimeString();
            return `
                <tr>
                    <td>${time}</td>
                    <td>${item.product?.name || 'Unknown'}</td>
                    <td><code style="font-size: 0.75rem;">${item.epc_code.substring(0, 16)}...</code></td>
                    <td>${item.quantity}</td>
                </tr>
            `;
        }).join('');
    }

    function showOperationSummary() {
        const modal = new bootstrap.Modal(document.getElementById('summaryModal'));
        const content = document.getElementById('summaryContent');

        const uniqueProducts = new Set(scannedItems.map(item => item.product_id));
        const duration = Math.floor((new Date() - operationStartTime) / 1000 / 60);

        content.innerHTML = `
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <div class="text-muted">Total Items</div>
                        <h2 class="text-primary">${scannedItems.length}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <div class="text-muted">Unique Products</div>
                        <h2 class="text-success">${uniqueProducts.size}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <div class="text-muted">Duration</div>
                        <h2 class="text-info">${duration} min</h2>
                    </div>
                </div>
            </div>
            <h6>Operation Details:</h6>
            <p><strong>Type:</strong> ${activeOperation}</p>
            <p><strong>Started:</strong> ${operationStartTime.toLocaleString()}</p>
            <p><strong>Completed:</strong> ${new Date().toLocaleString()}</p>
        `;

        modal.show();
    }

    function exportSummary() {
        // Create CSV export
        const csv = [
            ['Time', 'Product', 'EPC Code', 'Quantity'],
            ...scannedItems.map(item => [
                new Date(item.scanned_at).toLocaleString(),
                item.product?.name || 'Unknown',
                item.epc_code,
                item.quantity
            ])
        ].map(row => row.join(',')).join('\n');

        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `operation_${activeOperation}_${Date.now()}.csv`;
        a.click();
    }

    // Simulate receiving scan data (in production, this comes from WebSocket)
    // For testing purposes
    function simulateScan() {
        if (!activeOperation) {
            alert('Start an operation first');
            return;
        }

        addScannedItem({
            epc_code: 'E2801190200051A47F0B9A9B',
            product_id: 1,
            product: { name: 'Test Product' },
            quantity: 1
        });
    }
</script>
@endpush

