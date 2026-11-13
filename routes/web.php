<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

Route::get('/', function () {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (isset($_SESSION['user'])) {
        return $_SESSION['user']['role'] === 'admin' 
            ? redirect('admin.php') 
            : redirect('user.php');
    }
    return redirect('login.php');
});

Route::any('/login', function (Request $request) {
    $query = $request->getQueryString() ? '?' . $request->getQueryString() : '';
    return redirect('login.php' . $query);
})->name('login');

Route::get('/admin', function () {
    return redirect('admin.php');
})->name('admin.dashboard');

Route::get('/user', function () {
    return redirect('user.php');
})->name('user.dashboard');

Route::get('/logout', function () {
    return redirect('logout.php');
})->name('logout');

Route::get('/test-db', function () {
    try {
        $usersCount = DB::table('users')->count();
        $laporanCount = DB::table('laporan')->count();
        return response()->json([
            'status' => 'OK',
            'framework' => 'Laravel ' . app()->version(),
            'database' => config('database.connections.mysql.database'),
            'total_users' => $usersCount,
            'total_laporan' => $laporanCount
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
