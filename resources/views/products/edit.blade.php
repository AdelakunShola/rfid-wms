@extends('layouts.app')

@section('title', 'Edit Product - RFID WMS')

@section('content')
<div class="mb-4">
    <h1 class="h2 mb-0">
        <i class="fas fa-edit text-primary"></i> Edit Product
    </h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form id="productForm">
                    <input type="hidden" id="product_id" value="{{ $product->id }}">
                    
                    <div class="mb-3">
                        <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sku" name="sku" 
                               value="{{ $product->sku }}" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="{{ $product->name }}" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="barcode" class="form-label">Barcode</label>
                        <input type="text" class="form-control" id="barcode" name="barcode" 
                               value="{{ $product->barcode }}">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3">{{ $product->description }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" 
                                       value="{{ $product->quantity }}" min="0">
                                <small class="text-muted">Note: Quantity is usually managed through operations</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="price" class="form-label">Unit Price ($)</label>
                                <input type="number" class="form-control" id="price" name="price" 
                                       value="{{ $product->price }}" min="0" step="0.01">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                        <a href="{{ route('products.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Product Information</h5>
                <p><strong>Created:</strong> {{ $product->created_at->format('M d, Y H:i') }}</p>
                <p><strong>Last Updated:</strong> {{ $product->updated_at->format('M d, Y H:i') }}</p>
                <p><strong>RFID Tags:</strong> {{ $product->rfidTags()->count() }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('productForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        // Clear previous errors
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        const productId = document.getElementById('product_id').value;
        const formData = {
            sku: document.getElementById('sku').value,
            name: document.getElementById('name').value,
            barcode: document.getElementById('barcode').value || null,
            description: document.getElementById('description').value || null,
            quantity: parseInt(document.getElementById('quantity').value) || 0,
            price: parseFloat(document.getElementById('price').value) || 0.00,
        };

        try {
            const res = await fetch(`/api/products/${productId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(formData)
            });

            const data = await res.json();

            if (data.success) {
                alert('Product updated successfully!');
                window.location.href = '{{ route("products.index") }}';
            } else {
                // Display validation errors
                if (data.errors) {
                    for (const [field, messages] of Object.entries(data.errors)) {
                        const input = document.getElementById(field);
                        if (input) {
                            input.classList.add('is-invalid');
                            const feedback = input.nextElementSibling;
                            if (feedback && feedback.classList.contains('invalid-feedback')) {
                                feedback.textContent = messages[0];
                            }
                        }
                    }
                }
                alert(data.message || 'Error updating product');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error updating product');
        }
    });
</script>
@endpush