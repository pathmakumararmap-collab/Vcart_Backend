<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    #[OA\Get(
        path: '/admin/activity-logs',
        tags: ['Admin Users'],
        summary: 'List system activity logs (who changed what, and when)',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Activity log list')],
    )]
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('activity-logs.view'), 403);

        $query = Activity::query()->with('causer')->latest();

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->string('subject_type'));
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->integer('causer_id'));
        }

        return response()->json($query->paginate((int) $request->integer('per_page', 25))->toArray());
    }
}
