
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">
        <i class="fas fa-box text-primary"></i> Products
    </h1>
    <a href="{{ route('products.create') }}" class="btn btn-primary">
        <i class="fas fa-plus-circle"></i> Add New Product
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="searchInput" class="form-control" 
                   placeholder="Search products by name, SKU, or barcode...">
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Name</th>
                        <th>Barcode</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="productsTable">
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            Loading products...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this product?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>




<script>
    const API_URL = '/api';
    let products = [];
    let deleteProductId = null;

    // Load products
    async function loadProducts() {
        try {
            const res = await fetch(`${API_URL}/products`);
            const data = await res.json();
            products = data.data;
            displayProducts(products);
        } catch (error) {
            console.error('Error loading products:', error);
            document.getElementById('productsTable').innerHTML = 
                '<tr><td colspan="6" class="text-center text-danger">Error loading products</td></tr>';
        }
    }

    function displayProducts(productsToShow) {
        const tbody = document.getElementById('productsTable');
        
        if (productsToShow.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No products found</td></tr>';
            return;
        }

        tbody.innerHTML = productsToShow.map(product => `
            <tr>
                <td><strong>${product.sku}</strong></td>
                <td>${product.name}</td>
                <td>${product.barcode || '-'}</td>
                <td>
                    <span class="badge ${product.quantity < 20 ? 'bg-danger' : product.quantity < 50 ? 'bg-warning' : 'bg-success'}">
                        ${product.quantity}
                    </span>
                </td>
                <td>$${parseFloat(product.price).toFixed(2)}</td>
                <td>
                    <a href="/products/${product.id}/edit" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(${product.id})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </td>
            </tr>
        `).join('');
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', (e) => {
        const search = e.target.value.toLowerCase();
        const filtered = products.filter(p => 
            p.name.toLowerCase().includes(search) ||
            p.sku.toLowerCase().includes(search) ||
            (p.barcode && p.barcode.toLowerCase().includes(search))
        );
        displayProducts(filtered);
    });

    // Delete confirmation
    function confirmDelete(productId) {
        deleteProductId = productId;
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    document.getElementById('confirmDelete').addEventListener('click', async () => {
        if (!deleteProductId) return;

        try {
            const res = await fetch(`${API_URL}/products/${deleteProductId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await res.json();

            if (data.success) {
                alert('Product deleted successfully');
                loadProducts();
            } else {
                alert(data.message || 'Failed to delete product');
            }

            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
            deleteProductId = null;

        } catch (error) {
            console.error('Error deleting product:', error);
            alert('Error deleting product');
        }
    });

    // Load products on page load
    document.addEventListener('DOMContentLoaded', loadProducts);
</script>
