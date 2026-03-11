<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SegmentController extends Controller
{
    /**
     * Display a listing of segments for the project resolved by token.
     */
    public function index(Request $request): JsonResponse
    {
        $token = $request->input('token', '');

        if ($token === '') {
            abort(404, 'token_mandatory');
        }

        $project = Project::resolveFromAccessToken($token);

        if (is_null($project)) {
            abort(404, 'token_not_valid');
        }

        $segments = $project->segments()
            ->where('active', true)
            ->get();

        return response()->json( $segments,
        );
    }
}
