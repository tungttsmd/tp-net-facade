<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Redis;

class SensorController extends Controller
{
    public function refresh(Request $request)
    {
        $redisKey = $request->input('redis_key');

        if (!$redisKey) {
            return response()->json(['error' => 'missing redis_key'], 422);
        }

        $raw = Redis::lindex($redisKey, -1); // snapshot mới nhất

        if (!$raw) {
            return response()->json([
                'error' => 'no data',
                'redis_key' => $redisKey
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'redis_key' => $redisKey,
            'data' => json_decode($raw, true),
        ]);
    }

    public function index()
    {
        $redisKey = cache('last_sensor_redis_key');

        if (!$redisKey) {
            return view('sensor.index', ['stats' => []]);
        }

        $raw = Redis::lindex($redisKey, -1);

        if (!$raw) {
            return view('sensor.index', ['stats' => []]);
        }

        $stats = json_decode($raw, true);

        return view('sensor.index', compact('stats'));
    }
}
