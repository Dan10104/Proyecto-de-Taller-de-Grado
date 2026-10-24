<?php

namespace App\Http\Controllers;

use App\Services\IotIngestionService;
use Illuminate\Http\Request;

class IotEventController extends Controller
{
    public function __invoke(Request $request, IotIngestionService $service)
    {
        $result = $service->receive($request, $request->attributes->get('iot.raw'));

        return response()->json(['result' => $result['result'], 'event_id' => $result['event_id']], $result['status']);
    }
}
