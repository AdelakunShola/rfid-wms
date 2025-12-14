<!-- resources/views/dashboard.blade.php -->
@extends('layouts.app')

@section('title', 'Dashboard - RFID WMS')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">
            <i class="fas fa-chart-line text-primary"></i> Dashboard
        </h1>
        <div class="text-muted">
            <i class="fas fa-clock"></i> {{ now()->format('M d, Y H:i') }}
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Products</div>
                        <h3 class="mb-0" id="totalProducts">0</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Encoded Tags</div>
                        <h3 class="mb-0" id="totalTags">0</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                        <i class="fas fa-barcode"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Today's Scans</div>
                        <h3 class="mb-0" id="todayScans">0</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Stock</div>
                        <h3 class="mb-0" id="totalStock">0</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">
                <i class="fas fa-bolt text-warning"></i> Quick Actions
            </h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="{{ route('products.create') }}" class="btn btn-primary w-100">
                        <i class="fas fa-plus-circle"></i> Add Product
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('rfid.encode') }}" class="btn btn-success w-100">
                        <i class="fas fa-tag"></i> Encode RFID Tag
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('operations.index') }}" class="btn btn-info w-100">
                        <i class="fas fa-tasks"></i> Operations
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('scan-logs.index') }}" class="btn btn-secondary w-100">
                        <i class="fas fa-history"></i> View Logs
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-history text-primary"></i> Recent Scans
                    </h5>
                    <div id="recentScans">
                        <div class="text-center text-muted py-3">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            Loading recent scans...
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-exclamation-triangle text-warning"></i> Low Stock Items
                    </h5>
                    <div id="lowStockItems">
                        <div class="text-center text-muted py-3">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            Loading low stock items...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // API Base URL
        const API_URL = '/api';

        // Load dashboard statistics
        async function loadDashboardStats() {
            try {
                // Load products
                const productsRes = await fetch(`${API_URL}/products`);
                const productsData = await productsRes.json();
                const products = productsData.data;

                document.getElementById('totalProducts').textContent = products.length;
                document.getElementById('totalStock').textContent = products.reduce((sum, p) => sum + p.quantity, 0);

                // Load RFID tags
                const tagsRes = await fetch(`${API_URL}/rfid/tags`);
                const tagsData = await tagsRes.json();
                document.getElementById('totalTags').textContent = tagsData.data.length;

                // Load scan logs
                const logsRes = await fetch(`${API_URL}/scan-logs?limit=100`);
                const logsData = await logsRes.json();
                const logs = logsData.data;

                // Count today's scans
                const today = new Date().toISOString().split('T')[0];
                const todayScans = logs.filter(log => log.created_at.startsWith(today)).length;
                document.getElementById('todayScans').textContent = todayScans;

                // Display recent scans
                displayRecentScans(logs.slice(0, 5));

                // Display low stock items
                displayLowStockItems(products.filter(p => p.quantity < 50).slice(0, 5));

            } catch (error) {
                console.error('Error loading dashboard:', error);
            }
        }

        function displayRecentScans(scans) {
            const container = document.getElementById('recentScans');

            if (scans.length === 0) {
                container.innerHTML = '<p class="text-muted text-center py-3">No recent scans</p>';
                return;
            }

            let html = '<div class="list-group list-group-flush">';
            scans.forEach(scan => {
                const operationColor = {
                    receiving: 'success',
                    picking: 'primary',
                    shipping: 'info',
                    count: 'secondary'
                } [scan.operation_type] || 'secondary';

                const productName = scan.product ? scan.product.name : 'Unknown Product';
                const time = new Date(scan.created_at).toLocaleTimeString();

                html += `
                <div class="list-group-item border-0 px-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${productName}</strong><br>
                            <small class="text-muted">
                                <span class="badge bg-${operationColor}">${scan.operation_type}</span>
                                ${scan.device_id || 'Unknown Device'}
                            </small>
                        </div>
                        <small class="text-muted">${time}</small>
                    </div>
                </div>
            `;
            });
            html += '</div>';

            container.innerHTML = html;
        }

        function displayLowStockItems(products) {
            const container = document.getElementById('lowStockItems');

            if (products.length === 0) {
                container.innerHTML = '<p class="text-muted text-center py-3">All items well stocked</p>';
                return;
            }

            let html = '<div class="list-group list-group-flush">';
            products.forEach(product => {
                const stockClass = product.quantity < 20 ? 'danger' : 'warning';
                html += `
                <div class="list-group-item border-0 px-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${product.name}</strong><br>
                            <small class="text-muted">SKU: ${product.sku}</small>
                        </div>
                        <span class="badge bg-${stockClass}">${product.quantity} units</span>
                    </div>
                </div>
            `;
            });
            html += '</div>';

            container.innerHTML = html;
        }

        // Load dashboard on page load
        document.addEventListener('DOMContentLoaded', loadDashboardStats);

        // Auto-refresh every 30 seconds
        setInterval(loadDashboardStats, 30000);
    </script>
@endpush
