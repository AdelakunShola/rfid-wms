@extends('layouts.app')

@section('title', 'Edit Product - RFID WMS')

@section('content')
<div class="mb-4">
    <h1 class="h2 mb-0">
        <i class="fas fa-plus-circle text-primary"></i> Add New Product
    </h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form id="productForm">
                    <div class="mb-3">
                        <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sku" name="sku" required>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="barcode" class="form-label">Barcode</label>
                        <input type="text" class="form-control" id="barcode" name="barcode">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="quantity" class="form-label">Initial Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" value="0" min="0">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Unit Price ($)</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" value="0.00" min="0">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Product
                        </button>
                        <a href="{{ route('products.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('productForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = {
            sku: document.getElementById('sku').value,
            name: document.getElementById('name').value,
            barcode: document.getElementById('barcode').value || null,
            description: document.getElementById('description').value || null,
            quantity: parseInt(document.getElementById('quantity').value) || 0,
            price: parseFloat(document.getElementById('price').value) || 0.00
        };

        try {
            const response = await fetch('/api/products', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                alert('Product created successfully!');
                window.location.href = '{{ route("products.index") }}';
            } else {
                let errorMsg = 'Error creating product:\n';
                if (data.errors) {
                    for (const [field, messages] of Object.entries(data.errors)) {
                        errorMsg += `${field}: ${messages.join(', ')}\n`;
                    }
                }
                alert(errorMsg);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error creating product');
        }
    });
</script>
@endpush