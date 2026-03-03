<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Predis\Client as PredisClient;

class DashboardController extends Controller
{
    protected function redis()
    {
        return new PredisClient(); // dùng predis đúng ý bạn
    }

    /**
     * View dashboard (load lần đầu)
     */
    public function index()
    {
        $redisKey = "agent:facade:901:sensor:temperatures";

        $redis = $this->redis();

        // LẤY SNAPSHOT MỚI NHẤT
        $raw = $redis->lindex($redisKey, -1);

        $latestData = $raw ? json_decode($raw, true) : null;

        return view('dashboard.index', compact('latestData'));
    }

    /**
     * API cho fetch (realtime)
     */
    public function fetch(Request $request)
    {
        $redisKey = $request->input('redis_key');

        if (!$redisKey) {
            return response()->json([
                'error' => 'redis_key missing'
            ], 422);
        }

        $redis = $this->redis();

        $raw = $redis->lindex($redisKey, -1);

        if (!$raw) {
            return response()->json([
                'error' => 'no data'
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'redis_key' => $redisKey,
            'data' => json_decode($raw, true),
        ]);
    }

    /**
     * API for market page
     */
    public function market()
    {

        return view('dashboard.market');
    }
}
