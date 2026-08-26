<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetApprovalController;
use App\Http\Controllers\AssetBundleController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\TeknisiManagementController;
use App\Http\Controllers\BulkAssetController;
use App\Http\Controllers\TicketController;

// ──────────────────────────────────────────────────────
// Authentication Routes (Public)
// ──────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Redirect root to dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// ──────────────────────────────────────────────────────
// Protected Routes (Requires Authentication)
// ──────────────────────────────────────────────────────
Route::middleware(['auth', 'staff.session'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // QR Code Scanner Page & Live IoT Stream Routes
    Route::get('/assets/scan', [AssetController::class, 'scan'])->name('assets.scan');
    Route::get('/assets/scan/latest', [\App\Http\Controllers\RfidController::class, 'getLatestScan'])->name('assets.scan.latest');
    Route::get('/assets/scan/proxy-stream', [AssetController::class, 'streamProxy'])->name('assets.scan.proxy-stream');
    Route::get('/assets/scan/live-frame', [\App\Http\Controllers\RfidController::class, 'getLiveFrame'])->name('assets.scan.live-frame');
    Route::get('/assets/scan/live-stream', [\App\Http\Controllers\RfidController::class, 'getLiveStream'])->name('assets.scan.live-stream');




    // Generate Structured Code Asset
    Route::post('/assets/generate-code', [AssetController::class, 'generateCodeAsset'])->name('assets.generate-code');

    // General Audit History Logs
    Route::get('/assets/history', [AssetController::class, 'history'])->name('assets.history');

    // RFID Web Management
    Route::get('/assets/rfid/history', [AssetController::class, 'rfidHistory'])->name('assets.rfid.history');
    Route::get('/assets/rfid/validate', [AssetController::class, 'showRfidValidator'])->name('assets.rfid.validate');
    Route::post('/assets/{asset}/rfid/register', [AssetController::class, 'registerRfid'])->name('assets.rfid.register-web');
    Route::post('/assets/{asset}/rfid/delete', [AssetController::class, 'deleteRfid'])->name('assets.rfid.delete-web');
    Route::post('/assets/{asset}/rfid/toggle', [AssetController::class, 'toggleRfid'])->name('assets.rfid.toggle-web');

    // Ticketing Maintenance Dashboard
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/claim', [TicketController::class, 'claim'])->name('tickets.claim');
    Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');
    Route::post('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.updateStatus');
    Route::post('/tickets/{ticket}/comment', [TicketController::class, 'storeComment'])->name('tickets.storeComment');

    // Export Assets Excel/CSV
    Route::get('/assets/export', [AssetController::class, 'export'])->name('assets.export');

    // Quick Status Update
    Route::patch('/assets/{asset}/status', [AssetController::class, 'updateStatus'])->name('assets.updateStatus');

    // Asset CRUD
    Route::resource('assets', AssetController::class)->except(['show']);

    // Location CRUD
    Route::resource('locations', LocationController::class);

    // Asset Bundle CRUD
    Route::resource('bundles', AssetBundleController::class);

    // Approval Submission (all authenticated staff)
    Route::post('/approvals', [AssetApprovalController::class, 'store'])->name('approvals.store');
    Route::post('/assets/{asset}/replacement', [AssetApprovalController::class, 'storeReplacement'])->name('assets.replacement.store');
    Route::post('/assets/{asset}/maintenance', [TicketController::class, 'storeMaintenanceLog'])->name('assets.maintenance.store');

    // ──────────────────────────────────────────────────────
    // Admin-Only Routes
    // ──────────────────────────────────────────────────────
    Route::middleware(['admin'])->prefix('admin')->group(function () {
        // Teknisi Management
        Route::resource('teknisi', TeknisiManagementController::class)->except(['show']);
        Route::patch('/teknisi/{teknisi}/reset-password', [TeknisiManagementController::class, 'resetPassword'])->name('teknisi.resetPassword');

        // Asset Approval Management
        Route::get('/approvals', [AssetApprovalController::class, 'index'])->name('admin.approvals.index');
        Route::patch('/approvals/{approval}/approve', [AssetApprovalController::class, 'approve'])->name('admin.approvals.approve');
        Route::patch('/approvals/{approval}/reject', [AssetApprovalController::class, 'reject'])->name('admin.approvals.reject');
        
        // Replacement Requests Management
        Route::patch('/replacements/{replacement}/approve', [AssetApprovalController::class, 'approveReplacement'])->name('admin.replacements.approve');
        Route::patch('/replacements/{replacement}/reject', [AssetApprovalController::class, 'rejectReplacement'])->name('admin.replacements.reject');

        // Bulk Asset Creation
        Route::get('/assets/bulk', [BulkAssetController::class, 'create'])->name('admin.assets.bulk.create');
        Route::post('/assets/bulk/preview', [BulkAssetController::class, 'preview'])->name('admin.assets.bulk.preview');
        Route::post('/assets/bulk/store', [BulkAssetController::class, 'store'])->name('admin.assets.bulk.store');
        Route::get('/assets/bulk/success', [BulkAssetController::class, 'success'])->name('admin.assets.bulk.success');

    });
});

// Public Asset Detail (QR Code scan destination) - Registered last to avoid wildcard conflicts with routes like /assets/create
Route::get('/assets/{asset}', [AssetController::class, 'show'])
    ->name('assets.show')
    ->middleware('staff.session')
    ->whereNumber('asset');

// Public Short URL for QR code scan
Route::get('/a/{inventory_code}', [AssetController::class, 'showByCode'])
    ->name('assets.public.short-show')
    ->where('inventory_code', '[A-Za-z0-9\-]+');

// Public Damage Reporting
Route::get('/assets/{asset}/report', [TicketController::class, 'createPublic'])->name('tickets.public.create')->whereNumber('asset');
Route::post('/assets/{asset}/report', [TicketController::class, 'storePublic'])->name('tickets.public.store')->whereNumber('asset');
Route::get('/tickets/success/{ticket_number}', [TicketController::class, 'successPublic'])->name('tickets.public.success');
