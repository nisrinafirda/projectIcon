<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskImportExportController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function (): void {
    Route::get('/', function () {
        return redirect()->route('login');
    });

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated Routes
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Export Routes
    Route::get('/tasks/export/excel', [TaskImportExportController::class, 'exportExcel'])->name('tasks.export.excel');
    Route::get('/tasks/export/pdf', [TaskImportExportController::class, 'exportPdf'])->name('tasks.export.pdf');

    // Category Quick Links (SSO Open, BAA, BAI, Exception, Kontrak Exp)
    Route::get('/tasks/category/{category}', [TaskController::class, 'index'])->name('tasks.category');

    // Task workflow for employees
    Route::post('/tasks/{task}/start', [TaskController::class, 'startWork'])->name('tasks.start');
    Route::post('/tasks/{task}/submit', [TaskController::class, 'submit'])->name('tasks.submit');
    Route::post('/tasks/{task}/toggle-complete', [TaskController::class, 'toggleComplete'])->name('tasks.toggle-complete');
    Route::post('/tasks/{task}/comment', [TaskController::class, 'saveComment'])->name('tasks.save-comment');

    // Admin-only Task Management & Imports
    Route::middleware('admin')->group(function (): void {
        Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
        Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

        // Admin Verifikasi Menu & Antrean Tugas
        Route::get('/verifikasi', [TaskController::class, 'verifikasi'])->name('admin.verifikasi');

        // Admin Review (Approve/Reject)
        Route::post('/tasks/{task}/review', [TaskController::class, 'review'])->name('tasks.review');

        // Live duplicate checking API
        Route::get('/api/tasks/check-duplicate', [TaskController::class, 'checkDuplicate'])->name('tasks.check-duplicate');

        // Import & Template
        Route::get('/tasks-import', [TaskImportExportController::class, 'showImport'])->name('tasks.import.view');
        Route::get('/tasks-import/template', [TaskImportExportController::class, 'downloadTemplate'])->name('tasks.import.template');
        Route::post('/tasks-import', [TaskImportExportController::class, 'import'])->name('tasks.import.process');

        // Admin Employee Management
        Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
        Route::put('/admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
        Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
    });

    // General Task List & Show (placed after specific routes to avoid conflict)
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
});
