<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

// ===================================
// IMPORTANT: Import all model classes
// ===================================
use App\Models\Product;
use App\Models\RfidTag;
use App\Models\ScanLog;

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RfidController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Product routes
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/{product}', [ProductController::class, 'show']);
    Route::put('/{product}', [ProductController::class, 'update']);
    Route::delete('/{product}', [ProductController::class, 'destroy']);
});

// RFID routes - Using controllers
Route::prefix('rfid')->group(function () {
    Route::post('/encode', [RfidController::class, 'encode']);
    Route::get('/tags', [RfidController::class, 'tags']);
});

// Scan logs route
Route::get('/scan-logs', [RfidController::class, 'scanLogs']);

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'RFID WMS API is running',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// ═══════════════════════════════════════════════════════
// MAIN SCAN ENDPOINT - Handles all operations
// ═══════════════════════════════════════════════════════

Route::post('/rfid/scan', function (Request $request) {
    Log::info('═══════════════════════════════════════');
    Log::info('RFID SCAN RECEIVED');
    Log::info('═══════════════════════════════════════');
    Log::info('Request data:', $request->all());

    $validator = Validator::make($request->all(), [
        'epc_code' => 'required|string',
        'operation_type' => 'required|in:receiving,picking,shipping,count',
        'quantity' => 'nullable|integer|min:1',
        'device_id' => 'nullable|string|max:100',
        'scanned_by' => 'nullable|string|max:100',
        'metadata' => 'nullable|array',
    ]);

    if ($validator->fails()) {
        Log::error('Validation failed', $validator->errors()->toArray());
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }

    $epcCode = $request->epc_code;
    $operationType = $request->operation_type;
    $quantity = $request->quantity ?? 1;

    Log::info('EPC Code: ' . $epcCode);
    Log::info('Operation: ' . $operationType);
    Log::info('Quantity: ' . $quantity);

    DB::beginTransaction();
    try {
        // Find the RFID tag
        $tag = RfidTag::where('epc_code', $epcCode)->first();

        // ═══════════════════════════════════════════════════════
        // RECEIVING OPERATION - Auto-create tag if not exists
        // ═══════════════════════════════════════════════════════
        if ($operationType === 'receiving') {
            if (!$tag) {
                Log::info('⚡ Tag NOT found - Auto-creating for RECEIVING operation');
                
                // Create a default "Unidentified" product if it doesn't exist
                $defaultProduct = Product::firstOrCreate(
                    ['sku' => 'UNIDENTIFIED'],
                    [
                        'name' => 'Unidentified Items',
                        'description' => 'Items scanned during receiving that need to be identified',
                        'quantity' => 0,
                        'price' => 0.00,
                    ]
                );

                Log::info('✓ Using product: ' . $defaultProduct->name . ' (ID: ' . $defaultProduct->id . ')');

                // Create the RFID tag
                $tag = RfidTag::create([
                    'epc_code' => $epcCode,
                    'product_id' => $defaultProduct->id,
                    'encoded_at' => now(),
                    'encoded_by' => $request->scanned_by ?? 'auto_created_on_receiving',
                    'metadata' => json_encode([
                        'auto_created' => true,
                        'device_id' => $request->device_id,
                        'created_at' => now()->toIso8601String(),
                    ]),
                ]);

                Log::info('✓ New RFID tag created: ' . $tag->id);

                // Get the product (now it exists)
                $product = $defaultProduct;
            } else {
                Log::info('✓ Tag found - ID: ' . $tag->id);
                $product = $tag->product;
            }

            // Update product quantity (add to stock)
            $product->increment('quantity', $quantity);
            Log::info('✓ Product quantity increased by ' . $quantity . ' → New total: ' . $product->quantity);

        } 
        // ═══════════════════════════════════════════════════════
        // OTHER OPERATIONS - Require tag to exist
        // ═══════════════════════════════════════════════════════
        else {
            if (!$tag) {
                Log::warning('❌ Tag NOT found in database for ' . $operationType);
                DB::rollBack();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Tag not found in system',
                    'help' => 'This tag needs to be received first or encoded in the system',
                    'epc_code' => $epcCode,
                    'operation_type' => $operationType,
                ], 404);
            }

            Log::info('✓ Tag found - ID: ' . $tag->id);
            $product = $tag->product;

            // Update quantity based on operation
            if ($operationType === 'picking' || $operationType === 'shipping') {
                if ($product->quantity < $quantity) {
                    Log::warning('⚠ Insufficient stock');
                    DB::rollBack();
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock',
                        'product' => $product->name,
                        'available' => $product->quantity,
                        'requested' => $quantity,
                    ], 400);
                }

                $product->decrement('quantity', $quantity);
                Log::info('✓ Product quantity decreased by ' . $quantity . ' → New total: ' . $product->quantity);
            }
            // Count operations don't change quantity
        }

        // Log the scan
        $scanLog = ScanLog::create([
            'epc_code' => $epcCode,
            'product_id' => $product->id,
            'operation_type' => $operationType,
            'quantity' => $quantity,
            'device_id' => $request->device_id,
            'scanned_by' => $request->scanned_by,
            'metadata' => $request->metadata,
        ]);

        Log::info('✓ Scan logged - ID: ' . $scanLog->id);

        DB::commit();

        Log::info('═══════════════════════════════════════');
        Log::info('✓✓✓ SCAN PROCESSED SUCCESSFULLY ✓✓✓');
        Log::info('═══════════════════════════════════════');

        // Check if metadata indicates auto-creation
        $metadataArray = is_string($tag->metadata) ? json_decode($tag->metadata, true) : $tag->metadata;
        $autoCreated = isset($metadataArray['auto_created']) && $metadataArray['auto_created'];

        return response()->json([
            'success' => true,
            'message' => 'Scan processed successfully',
            'data' => [
                'scan_log_id' => $scanLog->id,
                'epc_code' => $epcCode,
                'operation_type' => $operationType,
                'quantity' => $quantity,
                'tag_info' => [
                    'id' => $tag->id,
                    'auto_created' => $autoCreated,
                    'encoded_at' => $tag->encoded_at,
                ],
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $product->quantity,
                    'price' => $product->price,
                ],
                'timestamp' => now()->toIso8601String(),
            ],
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('❌ Exception occurred: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());

        return response()->json([
            'success' => false,
            'message' => 'Error processing scan: ' . $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
        ], 500);
    }
});

// ═══════════════════════════════════════════════════════
// UNIVERSAL SCAN - Read-only without database changes
// ═══════════════════════════════════════════════════════

Route::post('/rfid/universal-scan', function (Request $request) {
    Log::info('UNIVERSAL SCAN (Read-only): ' . $request->epc_code);
    
    $validator = Validator::make($request->all(), [
        'epc_code' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors(),
        ], 422);
    }

    $epcCode = $request->epc_code;
    $tag = RfidTag::where('epc_code', $epcCode)->first();

    return response()->json([
        'success' => true,
        'message' => '✓ Tag read successfully',
        'data' => [
            'epc_code' => $epcCode,
            'length' => strlen($epcCode),
            'registered' => $tag !== null,
            'product' => $tag ? [
                'name' => $tag->product->name,
                'sku' => $tag->product->sku,
                'quantity' => $tag->product->quantity,
            ] : null,
            'scanned_at' => now()->toIso8601String(),
        ],
    ]);
});

// ═══════════════════════════════════════════════════════
// TEST ENDPOINT
// ═══════════════════════════════════════════════════════

Route::get('/rfid/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'RFID Universal Scanner API is working!',
        'timestamp' => now()->toDateTimeString(),
        'endpoints' => [
            'POST /api/rfid/scan' => 'Main scan endpoint (auto-creates on receiving)',
            'POST /api/rfid/universal-scan' => 'Read-only scan',
            'GET /api/rfid/test' => 'Test API connection',
        ],
    ]);
});