<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RfidTag;
use App\Models\ScanLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RfidController extends Controller
{
    /**
     * Encode a new RFID tag
     */
    public function encode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'epc_code' => 'nullable|string|size:24|unique:rfid_tags,epc_code',
            'encoded_by' => 'nullable|string|max:100',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = Product::findOrFail($request->product_id);

        // Generate EPC code if not provided
        $epcCode = $request->epc_code ?? RfidTag::generateEpcCode($product->id);

        // Ensure EPC code is unique
        if (RfidTag::where('epc_code', $epcCode)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'EPC code already exists',
            ], 400);
        }

        $tag = RfidTag::create([
            'epc_code' => $epcCode,
            'product_id' => $product->id,
            'encoded_at' => now(),
            'encoded_by' => $request->encoded_by,
            'metadata' => $request->metadata,
        ]);

        $tag->load('product');

        return response()->json([
            'success' => true,
            'message' => 'RFID tag encoded successfully',
            'data' => $tag,
        ], 201);
    }

    /**
     * Process a single RFID scan
     */
    public function scan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'epc_code' => 'required|string',
            'operation_type' => 'required|in:receiving,picking,shipping,count',
            'quantity' => 'nullable|integer|min:1',
            'device_id' => 'nullable|string|max:100',
            'scanned_by' => 'nullable|string|max:100',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find the RFID tag
        $tag = RfidTag::where('epc_code', $request->epc_code)->first();

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'RFID tag not found in system',
                'epc_code' => $request->epc_code,
            ], 404);
        }

        $product = $tag->product;
        $quantity = $request->quantity ?? 1;

        // Update product quantity based on operation type
        DB::beginTransaction();
        try {
            $product->updateQuantity($request->operation_type, $quantity);

            // Log the scan
            $scanLog = ScanLog::create([
                'epc_code' => $request->epc_code,
                'product_id' => $product->id,
                'operation_type' => $request->operation_type,
                'quantity' => $quantity,
                'device_id' => $request->device_id,
                'scanned_by' => $request->scanned_by,
                'metadata' => $request->metadata,
            ]);

            DB::commit();

            $scanLog->load('product');

            return response()->json([
                'success' => true,
                'message' => 'Scan processed successfully',
                'data' => $scanLog,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error processing scan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process multiple RFID scans in bulk
     */
    public function bulkScan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'epc_codes' => 'required|array',
            'epc_codes.*' => 'required|string',
            'operation_type' => 'required|in:receiving,picking,shipping,count',
            'device_id' => 'nullable|string|max:100',
            'scanned_by' => 'nullable|string|max:100',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $epcCodes = $request->epc_codes;
        $results = [
            'found' => [],
            'not_found' => [],
            'errors' => [],
        ];

        DB::beginTransaction();
        try {
            foreach ($epcCodes as $epcCode) {
                $tag = RfidTag::where('epc_code', $epcCode)->first();

                if (!$tag) {
                    $results['not_found'][] = $epcCode;
                    continue;
                }

                $product = $tag->product;
                $product->updateQuantity($request->operation_type, 1);

                // Log the scan
                $scanLog = ScanLog::create([
                    'epc_code' => $epcCode,
                    'product_id' => $product->id,
                    'operation_type' => $request->operation_type,
                    'quantity' => 1,
                    'device_id' => $request->device_id,
                    'scanned_by' => $request->scanned_by,
                    'metadata' => $request->metadata,
                ]);

                $results['found'][] = [
                    'epc_code' => $epcCode,
                    'product' => $product,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk scan processed',
                'data' => [
                    'total_scanned' => count($epcCodes),
                    'found_count' => count($results['found']),
                    'not_found_count' => count($results['not_found']),
                    'found' => $results['found'],
                    'not_found' => $results['not_found'],
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error processing bulk scan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all RFID tags
     */
    public function tags(Request $request)
    {
        $query = RfidTag::with('product');

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $tags = $query->orderBy('encoded_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $tags,
            'count' => $tags->count(),
        ]);
    }

    /**
     * Get scan logs
     */
    public function scanLogs(Request $request)
    {
        $query = ScanLog::with(['product', 'rfidTag']);

        // Filter by operation type
        if ($request->has('operation_type')) {
            $query->ofOperationType($request->operation_type);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->dateRange($request->from_date, $request->to_date);
        }

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Limit results
        $limit = $request->input('limit', 100);
        $logs = $query->orderBy('created_at', 'desc')->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
            'count' => $logs->count(),
        ]);
    }
}