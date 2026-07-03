<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => \App\Models\User::count(),
            'admins_count' => \App\Models\User::where('role', 'admin')->count(),
            'regular_users' => \App\Models\User::where('role', 'user')->count(),
        ];
        
        $users = \App\Models\User::orderBy('id', 'desc')->get();

        return view('admin.dashboard', compact('stats', 'users'));
    }

    public function getLatestLogs(Request $request)
    {
        $lastCheckedAt = $request->query('last_checked_at');
        
        if (!$lastCheckedAt) {
            return response()->json(['logs' => []]);
        }

        $logs = \App\Models\GenerationLog::with('article')
                    ->where('created_at', '>', $lastCheckedAt)
                    ->orderBy('created_at', 'asc')
                    ->get();
                    
        return response()->json(['logs' => $logs]);
    }
}
