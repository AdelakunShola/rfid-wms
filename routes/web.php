<?php

use App\Models\Product;
use App\Models\RfidTag;
use App\Models\ScanLog;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/products', function () {
    return view('products.index');
})->name('products.index');

Route::get('/products/create', function () {
    return view('products.create');
})->name('products.create');

Route::get('/products/{id}/edit', function ($id) {
    $product = Product::findOrFail($id);
    return view('products.edit', compact('product'));
})->name('products.edit');

// ✅ FIXED: Pass both $products and $tags to the view
Route::get('/rfid/encode', function () {
    $products = Product::orderBy('name')->get();
    $tags = RfidTag::with('product')
        ->orderBy('encoded_at', 'desc')
        ->limit(10)
        ->get(); // Get last 10 encoded tags
    return view('rfid.encode', compact('products', 'tags'));
})->name('rfid.encode');

Route::get('/rfid/tags', function () {
    $tags = RfidTag::with('product')->orderBy('encoded_at', 'desc')->get();
    return view('rfid.tags', compact('tags'));
})->name('rfid.tags');

Route::get('/operations', function () {
    return view('operations.index');
})->name('operations.index');

Route::get('/scan-logs', function () {
    $logs = ScanLog::with('product')
        ->orderBy('created_at', 'desc')
        ->limit(100)
        ->get();
    return view('scan-logs.index', compact('logs'));
})->name('scan-logs.index');

Route::get('/inventory', function () {
    $products = Product::orderBy('quantity', 'desc')->get();
    $totalProducts = $products->count();
    $totalQuantity = $products->sum('quantity');
    $totalValue = $products->sum(function ($product) {
        return $product->quantity * $product->price;
    });
    
    return view('inventory.index', compact('products', 'totalProducts', 'totalQuantity', 'totalValue'));
})->name('inventory.index');


Route::get('/test-scanner', function () {
    return view('test-scanner');
});