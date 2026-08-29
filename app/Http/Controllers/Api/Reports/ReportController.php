<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    #[OA\Get(
        path: '/reports/sales',
        tags: ['Reports'],
        summary: 'Sales report — revenue, discounts, tax, and breakdown by channel/day',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'source', in: 'query', schema: new OA\Schema(type: 'string', enum: ['website', 'facebook', 'pos'])),
            new OA\Parameter(name: 'warehouse_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Sales report')],
    )]
    public function sales(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);

        return response()->json([
            'data' => $this->reports->salesReport(
                $request->input('from'),
                $request->input('to'),
                $request->input('source'),
                $request->input('warehouse_id'),
            ),
        ]);
    }

    #[OA\Get(
        path: '/reports/orders',
        tags: ['Reports'],
        summary: 'Order report — breakdown by status and payment status',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Order report')],
    )]
    public function orders(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);

        return response()->json(['data' => $this->reports->orderReport($request->input('from'), $request->input('to'))]);
    }

    #[OA\Get(
        path: '/reports/products',
        tags: ['Reports'],
        summary: 'Product report — top-selling products and stock cost valuation',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Product report')],
    )]
    public function products(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);

        return response()->json([
            'data' => $this->reports->productReport($request->input('from'), $request->input('to'), (int) $request->integer('limit', 20)),
        ]);
    }

    #[OA\Get(
        path: '/reports/stock',
        tags: ['Reports'],
        summary: 'Stock report — total units, low-stock items, recent movements',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Stock report')],
    )]
    public function stock(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);

        return response()->json(['data' => $this->reports->stockReport($request->input('warehouse_id'))]);
    }
}
