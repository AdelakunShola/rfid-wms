<!-- resources/views/rfid/tags.blade.php -->
@extends('layouts.app')

@section('title', 'RFID Tags - RFID WMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">
        <i class="fas fa-tags text-success"></i> Encoded RFID Tags
    </h1>
    <a href="{{ route('rfid.encode') }}" class="btn btn-success">
        <i class="fas fa-plus-circle"></i> Encode New Tag
    </a>
</div>

<!-- Summary Statistics -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                    <i class="fas fa-tags"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Tags</div>
                    <h3 class="mb-0">{{ $tags->count() }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                    <i class="fas fa-box"></i>
                </div>
                <div>
                    <div class="text-muted small">Unique Products</div>
                    <h3 class="mb-0">{{ $tags->unique('product_id')->count() }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                    <i class="fas fa-calendar"></i>
                </div>
                <div>
                    <div class="text-muted small">Encoded Today</div>
                    <h3 class="mb-0">{{ $tags->where('encoded_at', '>=', today())->count() }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RFID Tags Table -->
<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="searchInput" class="form-control" 
                   placeholder="Search by EPC code, product name, or SKU...">
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>EPC Code</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Encoded Date</th>
                        <th>Encoded By</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tagsTable">
                    @forelse($tags as $tag)
                    <tr>
                        <td>
                            <code style="font-size: 0.85rem;">{{ substr($tag->epc_code, 0, 20) }}...</code>
                            <br>
                            <small class="text-muted">{{ strlen($tag->epc_code) }} chars</small>
                        </td>
                        <td>
                            <strong>{{ $tag->product->name }}</strong>
                            <br>
                            <small class="text-muted">{{ $tag->product->description ?? 'No description' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $tag->product->sku }}</span>
                        </td>
                        <td>
                            {{ $tag->encoded_at->format('M d, Y') }}
                            <br>
                            <small class="text-muted">{{ $tag->encoded_at->format('h:i A') }}</small>
                        </td>
                        <td>{{ $tag->encoded_by ?? '-' }}</td>
                        <td>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle"></i> Active
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="viewTagDetails('{{ $tag->epc_code }}', {{ $tag->id }})">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-sm btn-outline-info" 
                                    onclick="showQRCode('{{ $tag->epc_code }}')">
                                <i class="fas fa-qrcode"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-tags fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                            <p class="mb-2">No RFID tags encoded yet</p>
                            <a href="{{ route('rfid.encode') }}" class="btn btn-success btn-sm">
                                <i class="fas fa-plus-circle"></i> Encode Your First Tag
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tags->count() > 0)
        <div class="mt-3 text-muted">
            <small>
                <i class="fas fa-info-circle"></i> 
                Showing {{ $tags->count() }} tag(s). 
                Last encoded: {{ $tags->first()->encoded_at->diffForHumans() }}
            </small>
        </div>
        @endif
    </div>
</div>

<!-- Tag Details Modal -->
<div class="modal fade" id="tagModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-tag text-success"></i> RFID Tag Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="tagDetails">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading tag details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printTagInfo()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Modal -->
<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-qrcode"></i> Tag QR Code
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qrCodeContainer"></div>
                <p class="mt-3 mb-0"><code id="qrEpcCode"></code></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="downloadQRCode()">
                    <i class="fas fa-download"></i> Download
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- QR Code Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
    let currentQRCode = null;
    let currentEpcForQR = '';

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', (e) => {
        const search = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#tagsTable tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(search) ? '' : 'none';
        });
    });

    // View tag details
    async function viewTagDetails(epcCode, tagId) {
        const modal = new bootstrap.Modal(document.getElementById('tagModal'));
        modal.show();

        const detailsDiv = document.getElementById('tagDetails');
        detailsDiv.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading tag details...</p>
            </div>
        `;

        try {
            // Get tag info
            const tagsRes = await fetch('/api/rfid/tags');
            const tagsData = await tagsRes.json();
            const tag = tagsData.data.find(t => t.epc_code === epcCode);

            if (!tag) {
                detailsDiv.innerHTML = '<div class="alert alert-danger">Tag not found</div>';
                return;
            }

            // Get scan history for this tag
            const logsRes = await fetch('/api/scan-logs?limit=100');
            const logsData = await logsRes.json();
            const tagScans = logsData.data.filter(log => log.epc_code === epcCode);

            // Calculate statistics
            const totalScans = tagScans.length;
            const firstScan = tagScans.length > 0 ? new Date(tagScans[tagScans.length - 1].created_at) : null;
            const lastScan = tagScans.length > 0 ? new Date(tagScans[0].created_at) : null;

            // Count operations
            const operationCounts = {
                receiving: tagScans.filter(s => s.operation_type === 'receiving').length,
                picking: tagScans.filter(s => s.operation_type === 'picking').length,
                shipping: tagScans.filter(s => s.operation_type === 'shipping').length,
                count: tagScans.filter(s => s.operation_type === 'count').length
            };

            detailsDiv.innerHTML = `
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-primary">
                            <i class="fas fa-tag"></i> Tag Information
                        </h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="text-muted">EPC Code:</td>
                                <td><code>${tag.epc_code}</code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Length:</td>
                                <td>${tag.epc_code.length} characters</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Encoded Date:</td>
                                <td>${new Date(tag.encoded_at).toLocaleString()}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Encoded By:</td>
                                <td>${tag.encoded_by || 'N/A'}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary">
                            <i class="fas fa-box"></i> Product Information
                        </h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="text-muted">Name:</td>
                                <td><strong>${tag.product.name}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">SKU:</td>
                                <td><span class="badge bg-secondary">${tag.product.sku}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Current Stock:</td>
                                <td>
                                    <span class="badge ${tag.product.quantity < 20 ? 'bg-danger' : tag.product.quantity < 50 ? 'bg-warning' : 'bg-success'}">
                                        ${tag.product.quantity} units
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Unit Price:</td>
                                <td>$${parseFloat(tag.product.price).toFixed(2)}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr>

                <h6 class="text-primary mb-3">
                    <i class="fas fa-chart-line"></i> Scan Statistics
                </h6>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <h3 class="mb-0">${totalScans}</h3>
                            <small class="text-muted">Total Scans</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                            <h3 class="mb-0 text-success">${operationCounts.receiving}</h3>
                            <small class="text-muted">Receiving</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-primary bg-opacity-10 rounded">
                            <h3 class="mb-0 text-primary">${operationCounts.picking}</h3>
                            <small class="text-muted">Picking</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-info bg-opacity-10 rounded">
                            <h3 class="mb-0 text-info">${operationCounts.shipping}</h3>
                            <small class="text-muted">Shipping</small>
                        </div>
                    </div>
                </div>

                ${firstScan ? `
                <div class="row mb-4">
                    <div class="col-md-6">
                        <small class="text-muted">First Scan:</small>
                        <p class="mb-0">${firstScan.toLocaleString()}</p>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Last Scan:</small>
                        <p class="mb-0">${lastScan.toLocaleString()}</p>
                    </div>
                </div>
                ` : ''}

                <hr>

                <h6 class="text-primary mb-3">
                    <i class="fas fa-history"></i> Recent Scan History (Last 10)
                </h6>
                ${tagScans.length > 0 ? `
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Operation</th>
                                    <th>Quantity</th>
                                    <th>Device</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${tagScans.slice(0, 10).map(scan => {
                                    const operationColors = {
                                        receiving: 'success',
                                        picking: 'primary',
                                        shipping: 'info',
                                        count: 'secondary'
                                    };
                                    const color = operationColors[scan.operation_type] || 'secondary';
                                    
                                    return `
                                        <tr>
                                            <td>${new Date(scan.created_at).toLocaleString()}</td>
                                            <td>
                                                <span class="badge bg-${color}">
                                                    ${scan.operation_type}
                                                </span>
                                            </td>
                                            <td>${scan.quantity}</td>
                                            <td>${scan.device_id || 'N/A'}</td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                ` : '<p class="text-muted text-center py-3">No scan history for this tag</p>'}
            `;

        } catch (error) {
            console.error('Error loading tag details:', error);
            detailsDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Error loading tag details: ${error.message}
                </div>
            `;
        }
    }

    // Show QR Code
    function showQRCode(epcCode) {
        currentEpcForQR = epcCode;
        const modal = new bootstrap.Modal(document.getElementById('qrModal'));
        modal.show();

        // Clear previous QR code
        const container = document.getElementById('qrCodeContainer');
        container.innerHTML = '';

        // Generate new QR code
        currentQRCode = new QRCode(container, {
            text: epcCode,
            width: 256,
            height: 256,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        document.getElementById('qrEpcCode').textContent = epcCode;
    }

    // Download QR Code
    function downloadQRCode() {
        const canvas = document.querySelector('#qrCodeContainer canvas');
        if (canvas) {
            const url = canvas.toDataURL('image/png');
            const a = document.createElement('a');
            a.href = url;
            a.download = `rfid_tag_${currentEpcForQR.substring(0, 12)}.png`;
            a.click();
        }
    }

    // Print tag info
    function printTagInfo() {
        const printContent = document.getElementById('tagDetails').innerHTML;
        const printWindow = window.open('', '', 'width=800,height=600');
        printWindow.document.write(`
            <html>
                <head>
                    <title>RFID Tag Details</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { padding: 20px; }
                        @media print {
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <h2>RFID Tag Details</h2>
                    ${printContent}
                    <script>
                        window.onload = () => {
                            window.print();
                            window.close();
                        };
                    </script>
                </body>
            </html>
        `);
        printWindow.document.close();
    }

    // Auto-refresh data every 30 seconds if no modal is open
    setInterval(() => {
        const modalOpen = document.querySelector('.modal.show');
        if (!modalOpen) {
            location.reload();
        }
    }, 30000);
</script>
@endpush