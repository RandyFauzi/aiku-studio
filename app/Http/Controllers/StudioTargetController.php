<?php

namespace App\Http\Controllers;

use App\Services\StudioTargetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudioTargetController extends Controller
{
    public function show(Request $request, StudioTargetService $service): JsonResponse
    {
        $periodId = $request->query('periodId');

        return response()->json(
            $service->summary(is_string($periodId) ? $periodId : null)
        );
    }
}
