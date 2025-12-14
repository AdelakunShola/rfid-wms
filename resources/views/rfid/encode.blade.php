<!-- resources/views/rfid/encode.blade.php -->
@extends('layouts.app')

@section('title', 'Encode RFID Tags - RFID WMS')

@section('content')
<div class="mb-4">
    <h1 class="h2 mb-0">
        <i class="fas fa-tag text-success"></i> Encode RFID Tag
    </h1>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Tag Encoding Form</h5>
                
                <form id="encodeForm">
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Select Product <span class="text-danger">*</span></label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <option value="">-- Select a Product --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->name }} ({{ $product->sku }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="epc_code" class="form-label">EPC Code</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="epc_code" name="epc_code" 
                                   placeholder="Leave blank to auto-generate">
                            <button type="button" class="btn btn-outline-secondary" onclick="generateEpc()">
                                <i class="fas fa-sync"></i> Generate
                            </button>
                        </div>
                        <small class="text-muted">24-character hexadecimal code (e.g., E2801190200051A47F0B9A9B)</small>
                    </div>

                    <div class="mb-3">
                        <label for="encoded_by" class="form-label">Encoded By</label>
                        <input type="text" class="form-control" id="encoded_by" name="encoded_by" 
                               placeholder="Your name or user ID">
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> Ensure the RFID tag is in range of the encoder device before clicking "Encode Tag".
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-tag"></i> Encode Tag
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="simulateEncode()">
                            <i class="fas fa-flask"></i> Simulate Encoding (Test)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Product Preview</h5>
                <div id="productPreview" class="text-center text-muted">
                    Select a product to see details
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title mb-3">Recently Encoded Tags</h5>
                <div id="recentTags">
                    <div class="text-center text-muted">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        Loading...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let products = @json($products);

    // Show product preview
    document.getElementById('product_id').addEventListener('change', function() {
        const productId = parseInt(this.value);
        const product = products.find(p => p.id === productId);
        
        const preview = document.getElementById('productPreview');
        if (product) {
            preview.innerHTML = `
                <div class="text-start">
                    <h6>${product.name}</h6>
                    <p class="mb-1"><strong>SKU:</strong> ${product.sku}</p>
                    <p class="mb-1"><strong>Barcode:</strong> ${product.barcode || 'N/A'}</p>
                    <p class="mb-1"><strong>Current Stock:</strong> ${product.quantity}</p>
                    <p class="mb-1"><strong>Price:</strong> $${parseFloat(product.price).toFixed(2)}</p>
                    <p class="mb-0"><small class="text-muted">${product.description || 'No description'}</small></p>
                </div>
            `;
        } else {
            preview.innerHTML = '<p class="text-center text-muted">Select a product to see details</p>';
        }
    });

    // Generate EPC code
    function generateEpc() {
        const productId = document.getElementById('product_id').value;
        if (!productId) {
            alert('Please select a product first');
            return;
        }

        // Generate SGTIN-96 format EPC
        const header = 'E2';
        const filter = '8';
        const randomHex = () => Math.floor(Math.random() * 16).toString(16).toUpperCase();
        
        // Company prefix (5 chars)
        let companyPrefix = '';
        for (let i = 0; i < 5; i++) companyPrefix += randomHex();
        
        // Item reference from product ID (7 chars, padded)
        const itemRef = productId.toString(16).toUpperCase().padStart(7, '0');
        
        // Serial number (9 chars)
        let serial = '';
        for (let i = 0; i < 9; i++) serial += randomHex();
        
        const epc = header + filter + companyPrefix + itemRef + serial;
        document.getElementById('epc_code').value = epc;
    }

    // Encode tag form submission
    document.getElementById('encodeForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const productId = document.getElementById('product_id').value;
        const epcCode = document.getElementById('epc_code').value;
        const encodedBy = document.getElementById('encoded_by').value;

        if (!productId) {
            alert('Please select a product');
            return;
        }

        const formData = {
            product_id: parseInt(productId),
            epc_code: epcCode || null,
            encoded_by: encodedBy || null
        };

        try {
            const res = await fetch('/api/rfid/encode', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(formData)
            });

            const data = await res.json();

            if (data.success) {
                alert(`Tag encoded successfully!\nEPC Code: ${data.data.epc_code}`);
                document.getElementById('encodeForm').reset();
                document.getElementById('productPreview').innerHTML = '<p class="text-center text-muted">Select a product to see details</p>';
                loadRecentTags();
            } else {
                let errorMsg = 'Error encoding tag:\n';
                if (data.errors) {
                    for (const [field, messages] of Object.entries(data.errors)) {
                        errorMsg += `${field}: ${messages.join(', ')}\n`;
                    }
                }
                alert(errorMsg);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error encoding tag');
        }
    });

    // Simulate encoding (for testing without device)
    async function simulateEncode() {
        const productId = document.getElementById('product_id').value;
        if (!productId) {
            alert('Please select a product first');
            return;
        }

        // Auto-generate EPC if not provided
        if (!document.getElementById('epc_code').value) {
            generateEpc();
        }

        // Submit the form
        document.getElementById('encodeForm').dispatchEvent(new Event('submit'));
    }

    // Load recent tags
    async function loadRecentTags() {
        try {
            const res = await fetch('/api/rfid/tags');
            const data = await res.json();
            const tags = data.data.slice(0, 5);

            const container = document.getElementById('recentTags');
            if (tags.length === 0) {
                container.innerHTML = '<p class="text-muted text-center">No tags encoded yet</p>';
                return;
            }

            container.innerHTML = tags.map(tag => {
                const date = new Date(tag.encoded_at).toLocaleString();
                return `
                    <div class="border-bottom pb-2 mb-2">
                        <small class="text-muted">${date}</small><br>
                        <strong>${tag.product.name}</strong><br>
                        <code style="font-size: 0.75rem;">${tag.epc_code}</code>
                    </div>
                `;
            }).join('');

        } catch (error) {
            console.error('Error loading recent tags:', error);
        }
    }

    document.addEventListener('DOMContentLoaded', loadRecentTags);
</script>
@endpush

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

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Search by EPC code, product name, or SKU...">
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tagsTable">
                    @if($tags->isEmpty())
                        <tr>
                            <td colspan="6" class="text-center text-muted">No RFID tags encoded yet</td>
                        </tr>
                    @else
                        @foreach($tags as $tag)
                        <tr>
                            <td><code>{{ $tag->epc_code }}</code></td>
                            <td>{{ $tag->product->name }}</td>
                            <td>{{ $tag->product->sku }}</td>
                            <td>{{ $tag->encoded_at->format('M d, Y H:i') }}</td>
                            <td>{{ $tag->encoded_by ?? '-' }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="viewTagDetails('{{ $tag->epc_code }}')">
                                    <i class="fas fa-eye"></i> Details
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tag Details Modal -->
<div class="modal fade" id="tagModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">RFID Tag Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="tagDetails">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let allTags = @json($tags);

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', (e) => {
        const search = e.target.value.toLowerCase();
        const tbody = document.getElementById('tagsTable');
        const rows = tbody.querySelectorAll('tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(search) ? '' : 'none';
        });
    });

    // View tag details
    async function viewTagDetails(epcCode) {
        const modal = new bootstrap.Modal(document.getElementById('tagModal'));
        modal.show();

        const detailsDiv = document.getElementById('tagDetails');
        detailsDiv.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';

        try {
            // Get tag info
            const tagsRes = await fetch('/api/rfid/tags');
            const tagsData = await tagsRes.json();
            const tag = tagsData.data.find(t => t.epc_code === epcCode);

            if (!tag) {
                detailsDiv.innerHTML = '<p class="text-danger">Tag not found</p>';
                return;
            }

            // Get scan history for this tag
            const logsRes = await fetch('/api/scan-logs?limit=100');
            const logsData = await logsRes.json();
            const tagScans = logsData.data.filter(log => log.epc_code === epcCode);

            detailsDiv.innerHTML = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Tag Information</h6>
                        <p><strong>EPC Code:</strong><br><code>${tag.epc_code}</code></p>
                        <p><strong>Encoded Date:</strong><br>${new Date(tag.encoded_at).toLocaleString()}</p>
                        <p><strong>Encoded By:</strong><br>${tag.encoded_by || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Product Information</h6>
                        <p><strong>Name:</strong> ${tag.product.name}</p>
                        <p><strong>SKU:</strong> ${tag.product.sku}</p>
                        <p><strong>Current Stock:</strong> ${tag.product.quantity}</p>
                        <p><strong>Price:</strong> $${parseFloat(tag.product.price).toFixed(2)}</p>
                    </div>
                </div>
                
                <h6>Scan History (${tagScans.length} scans)</h6>
                ${tagScans.length > 0 ? `
                    <div class="table-responsive">
                        <table class="table table-sm">
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
                                    const operationColor = {
                                        receiving: 'success',
                                        picking: 'primary',
                                        shipping: 'info',
                                        count: 'secondary'
                                    }[scan.operation_type] || 'secondary';
                                    
                                    return `
                                        <tr>
                                            <td>${new Date(scan.created_at).toLocaleString()}</td>
                                            <td><span class="badge bg-${operationColor}">${scan.operation_type}</span></td>
                                            <td>${scan.quantity}</td>
                                            <td>${scan.device_id || 'N/A'}</td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                ` : '<p class="text-muted">No scan history for this tag</p>'}
            `;

        } catch (error) {
            console.error('Error loading tag details:', error);
            detailsDiv.innerHTML = '<p class="text-danger">Error loading tag details</p>';
        }
    }
</script>
@endpush