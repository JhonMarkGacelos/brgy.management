<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\BlotterController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentVerifyController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResidentPortalController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// ── PUBLIC DOCUMENT VERIFICATION (no login required) ──────────
Route::get('/verify',  [DocumentVerifyController::class, 'index'])->name('document.verify');
Route::post('/verify', [DocumentVerifyController::class, 'check'])->name('document.verify.check');

// Post-login redirect: send each role to their own dashboard
Route::get('/dashboard', function () {
    if (Auth::user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    if (Auth::user()->role === 'resident') {
        return redirect()->route('resident.dashboard');
    }
    return redirect()->route('staff.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// ── ADMIN ROUTES ──────────────────────────────────────────────
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {

    Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');

    Route::resource('residents',    ResidentController::class);
    Route::put('/residents/{id}/member/{memberId}',    [ResidentController::class, 'updateMember'])->name('residents.member.update');
    Route::delete('/residents/{id}/member/{memberId}', [ResidentController::class, 'destroyMember'])->name('residents.member.destroy');
    Route::resource('blotter',      BlotterController::class);
    Route::resource('documents',    DocumentController::class);
    Route::get('/documents/{id}/print',    [DocumentController::class, 'print'])->name('documents.print');
    Route::post('/documents/{id}/verify-id', [DocumentController::class, 'verifyId'])->name('documents.verify-id');
    Route::resource('announcements', AnnouncementController::class);

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    Route::get('/users',            [UserController::class, 'index'])->name('users.index');
    Route::post('/users',           [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}',     [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}',  [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/settings',  [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

// ── STAFF / CLERK ROUTES ──────────────────────────────────────
Route::middleware(['auth', 'verified', 'role:staff'])->prefix('staff')->name('staff.')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'staffDashboard'])->name('dashboard');

    // Residents (Profiling)
    Route::get('/residents',                          [ResidentController::class, 'index'])->name('residents.index');
    Route::get('/residents/create',                   [ResidentController::class, 'create'])->name('residents.create');
    Route::post('/residents',                         [ResidentController::class, 'store'])->name('residents.store');
    Route::get('/residents/{id}',                     [ResidentController::class, 'show'])->name('residents.show');
    Route::get('/residents/{id}/edit',                [ResidentController::class, 'edit'])->name('residents.edit');
    Route::put('/residents/{id}',                     [ResidentController::class, 'update'])->name('residents.update');
    Route::put('/residents/{id}/member/{memberId}',   [ResidentController::class, 'updateMember'])->name('residents.member.update');
    Route::delete('/residents/{id}/member/{memberId}',[ResidentController::class, 'destroyMember'])->name('residents.member.destroy');

    // Blotter
    Route::get('/blotter',              [BlotterController::class, 'index'])->name('blotter.index');
    Route::get('/blotter/create',       [BlotterController::class, 'create'])->name('blotter.create');
    Route::post('/blotter',             [BlotterController::class, 'store'])->name('blotter.store');
    Route::get('/blotter/{id}',         [BlotterController::class, 'show'])->name('blotter.show');
    Route::get('/blotter/{id}/edit',    [BlotterController::class, 'edit'])->name('blotter.edit');
    Route::put('/blotter/{id}',         [BlotterController::class, 'update'])->name('blotter.update');
    Route::delete('/blotter/{id}',      [BlotterController::class, 'destroy'])->name('blotter.destroy');

    // Documents
    Route::get('/documents',        [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/documents',       [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{id}',              [DocumentController::class, 'show'])->name('documents.show');
    Route::put('/documents/{id}',              [DocumentController::class, 'update'])->name('documents.update');
    Route::get('/documents/{id}/print',        [DocumentController::class, 'print'])->name('documents.print');
    Route::post('/documents/{id}/verify-id',   [DocumentController::class, 'verifyId'])->name('documents.verify-id');

    // Announcements
    Route::get('/announcements',           [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/create',    [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/announcements',          [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/announcements/{id}',      [AnnouncementController::class, 'show'])->name('announcements.show');
    Route::get('/announcements/{id}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::put('/announcements/{id}',      [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{id}',   [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
});

// ── RESIDENT PORTAL ──────────────────────────────────────────
Route::middleware(['auth', 'verified', 'role:resident'])->prefix('portal')->name('resident.')->group(function () {

    Route::get('/dashboard', [ResidentPortalController::class, 'dashboard'])->name('dashboard');

    // Announcements (view-only community board)
    Route::get('/announcements',       [ResidentPortalController::class, 'announcementsIndex'])->name('announcements.index');
    Route::get('/announcements/{id}',  [ResidentPortalController::class, 'announcementsShow'])->name('announcements.show');

    // Document Requests
    Route::get('/documents',           [ResidentPortalController::class, 'documentsIndex'])->name('documents.index');
    Route::get('/documents/create',    [ResidentPortalController::class, 'documentsCreate'])->name('documents.create');
    Route::post('/documents',          [ResidentPortalController::class, 'documentsStore'])->name('documents.store');
    Route::get('/documents/track',     [ResidentPortalController::class, 'documentsTrack'])->name('documents.track');
    Route::get('/documents/{id}/print', [ResidentPortalController::class, 'documentsPrint'])->name('documents.print');
});

// ── PROFILE (both roles) ──────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
