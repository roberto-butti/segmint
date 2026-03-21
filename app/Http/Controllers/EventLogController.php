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
        $eventType = $request->input('type', 'view');
        $logValues = [
            'project_id' => $project->id,
            'session_id' => $sessionId,
            'uuid' => uniqid('', true),
            'visitor_id' => $request->input('visitor_id', null),
            'event_type' => $eventType,
            'event_properties' => $request->input('event_properties', []),
            'navigation_info' => [
                'page_url' => $request->fullUrl(),
                'referrer_url' => $request->headers->get('referer', null),
                'path' => $request->path(),
                'query_string' => $request->getQueryString(),
            ],
            'utms' => [
                'utm_source' => $request->input('utms.utm_source', null),
                'utm_medium' => $request->input('utms.utm_medium', null),
                'utm_campaign' => $request->input('utms.utm_campaign', null),
                'utm_term' => $request->input('utms.utm_term', null),
                'utm_content' => $request->input('utms.utm_content', null),
            ],
            'metadata' => $request->input('metadata', null),
        ];
        $log = new EventLog($logValues);
        $log->save();

        $segments = app(SegmentEngine::class)->assignSegments($log);

        return [
            'status' => 'OK',
            'session' => $sessionId,
            'segments' => $segments,
        ];
    }
}
