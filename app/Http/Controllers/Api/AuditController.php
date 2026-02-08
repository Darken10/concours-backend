<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'integer|min:1|max:100',
            'action' => 'string|in:created,updated,deleted,restored,force_deleted',
            'model_type' => 'string',
        ]);

        $query = Audit::with('user');

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->input('model_type'));
        }

        $perPage = $request->input('per_page', 20);
        $audits = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $audits->items(),
            'meta' => [
                'current_page' => $audits->currentPage(),
                'per_page' => $audits->perPage(),
                'total' => $audits->total(),
                'last_page' => $audits->lastPage(),
            ],
        ]);
    }

    public function show(Audit $audit): JsonResponse
    {
        return response()->json($audit->load('user'));
    }

    public function userAudits(Request $request, $userId): JsonResponse
    {
        $this->authorize('viewAny', Audit::class);

        $request->validate([
            'per_page' => 'integer|min:1|max:100',
        ]);

        $perPage = $request->input('per_page', 20);
        $audits = Audit::where('user_id', $userId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $audits->items(),
            'meta' => [
                'current_page' => $audits->currentPage(),
                'per_page' => $audits->perPage(),
                'total' => $audits->total(),
                'last_page' => $audits->lastPage(),
            ],
        ]);
    }

    public function modelAudits(Request $request, string $modelType, $modelId): JsonResponse
    {
        $request->validate([
            'per_page' => 'integer|min:1|max:100',
        ]);

        $perPage = $request->input('per_page', 20);
        $audits = Audit::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $audits->items(),
            'meta' => [
                'current_page' => $audits->currentPage(),
                'per_page' => $audits->perPage(),
                'total' => $audits->total(),
                'last_page' => $audits->lastPage(),
            ],
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Audit::class);

        $stats = [
            'total_audits' => Audit::count(),
            'audits_this_month' => Audit::whereMonth('created_at', now()->month)
                ->count(),
            'audits_today' => Audit::whereDate('created_at', now())
                ->count(),
            'by_action' => Audit::selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->pluck('count', 'action'),
            'by_model' => Audit::selectRaw('model_type, COUNT(*) as count')
                ->groupBy('model_type')
                ->pluck('count', 'model_type'),
        ];

        return response()->json($stats);
    }
}
