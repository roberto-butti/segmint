<?php

namespace App\Http\Controllers;

use App\Models\EventLog;
use App\Models\Project;
use App\Services\SegmentEngine;
use Illuminate\Http\Request;

class EventLogController extends Controller
{
    public function track(Request $request): array
    {
        $token = $request->input('token', '');
        if ($token === '') {
            abort(404, 'token_mandatory');
        }
        $project = Project::resolveFromAccessToken($token);

        if (is_null($project)) {
            abort(404, 'token_not_valid');
        }

        $sessionId = $request->hasSession() ? session()->getId() : null;

        $log = new EventLog([
            'project_id' => $project->id,
            'session_id' => $sessionId,
            'uuid' => uniqid('', true),
            'visitor_id' => $request->input('visitor_id'),
            'event_type' => $request->input('type', 'view'),
            'page_url' => $request->input('url'),
            'page_path' => $request->input('path'),
            'referrer_url' => $request->input('referrer'),
            'utm_source' => $request->input('utms.utm_source'),
            'utm_medium' => $request->input('utms.utm_medium'),
            'utm_campaign' => $request->input('utms.utm_campaign'),
            'utm_term' => $request->input('utms.utm_term'),
            'utm_content' => $request->input('utms.utm_content'),
            'event_properties' => $request->input('event_properties', []),
            'metadata' => $request->input('metadata'),
        ]);
        $log->save();

        $segments = app(SegmentEngine::class)->assignSegments($log);

        return [
            'status' => 'OK',
            'session' => $sessionId,
            'segments' => $segments,
        ];
    }
}
