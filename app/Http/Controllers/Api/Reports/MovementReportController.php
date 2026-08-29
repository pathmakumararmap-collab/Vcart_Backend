<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\MovementReportRequest;
use App\Services\MovementReportExportService;
use App\Services\MovementReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use OpenApi\Attributes as OA;

class MovementReportController extends Controller
{
    public function __construct(
        private readonly MovementReportService $reports,
        private readonly MovementReportExportService $exporter,
    ) {}

    #[OA\Get(
        path: '/reports/movement',
        tags: ['Reports'],
        summary: 'Fast / slow / non-moving item report',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'warehouse_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'warehouse_type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['main', 'branch', 'outlet'])),
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'brand_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'group_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['category', 'subcategory', 'brand'])),
            new OA\Parameter(name: 'bucket', in: 'query', schema: new OA\Schema(type: 'string', enum: ['fast', 'slow', 'non_moving'])),
        ],
        responses: [new OA\Response(response: 200, description: 'Movement report')],
    )]
    public function index(MovementReportRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->reports->report($request->validated())]);
    }

    #[OA\Get(
        path: '/reports/movement/thresholds',
        tags: ['Reports'],
        summary: 'Get the fast/slow/non-moving classification thresholds',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Current thresholds')],
    )]
    public function thresholds(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);

        return response()->json(['data' => $this->reports->thresholds()]);
    }

    #[OA\Put(
        path: '/reports/movement/thresholds',
        tags: ['Reports'],
        summary: 'Update the fast/slow/non-moving classification thresholds',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Updated thresholds')],
    )]
    public function updateThresholds(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('reports.manage'), 403);

        $validated = $request->validate([
            'mode' => ['required', 'in:percentile,fixed'],
            'fast_percentile' => ['required_if:mode,percentile', 'integer', 'min:1', 'max:100'],
            'slow_percentile' => ['required_if:mode,percentile', 'integer', 'min:1', 'max:100'],
            'fast_qty_threshold' => ['required_if:mode,fixed', 'integer', 'min:0'],
            'slow_qty_threshold' => ['required_if:mode,fixed', 'integer', 'min:0'],
        ]);

        return response()->json(['data' => $this->reports->updateThresholds($validated)]);
    }

    #[OA\Get(
        path: '/reports/movement/export',
        tags: ['Reports'],
        summary: 'Export the movement report as Excel, PDF, or Word',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'format', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['excel', 'pdf', 'word'])),
        ],
        responses: [new OA\Response(response: 200, description: 'File download')],
    )]
       public function export(MovementReportRequest $request): BinaryFileResponse
    {
        $format = $request->validate(['format' => ['required', 'in:excel,pdf,word']])['format'];
        $report = $this->reports->report($request->validated());

        $bucketLabel = match ($request->input('bucket')) {
            'fast' => 'Fast Moving Items Report',
            'slow' => 'Slow Moving Items Report',
            'non_moving' => 'Non-Moving Items Report',
            default => 'Item Movement Report',
        };

        [$path, $filename] = $this->exporter->export($report, $format, $bucketLabel);

        return response()->download($path, $filename)->deleteFileAfterSend();
    }
}
