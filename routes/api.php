<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthenticationController::class, 'register']);
Route::post('/login', [AuthenticationController::class, 'login']);

Route::middleware(['auth:sanctum', 'role:student'])->prefix('student')->group(function () {
    
    Route::get('/scholarships', [StudentController::class, 'listScholarships']);

    Route::post('/applications', [StudentController::class, 'applyToScholarship']);

    Route::post('/applications/{id}/documents', [StudentController::class, 'uploadApplicationDocuments']);
    Route::get('/my-applications', [StudentController::class, 'myApplications']);
    Route::get('/applications/{id}', [StudentController::class, 'viewApplication']);


    Route::get('/applications/{id}/logs', [StudentController::class, 'viewReviewLogs']);
    
    Route::get('/my-awards', [StudentController::class, 'myAwards']);
    Route::get('/awards/{awardId}/disbursements', [StudentController::class, 'viewDisbursements']);

    Route::post('/disbursements/{id}/receipts', [StudentController::class, 'uploadReceipt']);
    Route::get('/disbursements/{id}', [StudentController::class, 'viewDisbursement']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('applications', [AdminController::class, 'applicationsIndex']);
    Route::get('applications/{id}', [AdminController::class, 'applicationsShow']);
    Route::post('applications/{id}/review', [AdminController::class, 'applicationsReview']);

    Route::post('/scholarships', [AdminController::class, 'createScholarship']);
    Route::put('/scholarships/{id}', [AdminController::class, 'updateScholarship']);
    Route::delete('/scholarships/{id}', [AdminController::class, 'deleteScholarship']);

    Route::post('/cost-categories', [AdminController::class, 'createCostCategory']);
    Route::get('/cost-categories', [AdminController::class, 'listCostCategories']);

    Route::post('/scholarships/{id}/budgets', [AdminController::class, 'setScholarshipBudgets']);
    Route::get('/scholarships/{id}/budgets', [AdminController::class, 'viewScholarshipBudgets']);

    Route::post('/applications/{id}/aw                              ard', [AdminController::class, 'createAward']);
    Route::post('/awards/{awardId}/schedules', [AdminController::class, 'createDisbursementSchedules']);

    Route::get('/disbursements', [AdminController::class, 'filterDisbursements']);
    Route::post('/receipts/{id}/verify', [AdminController::class, 'verifyReceipt']);
    Route::get('/reports/scholarships/{id}', [AdminController::class, 'scholarshipReport']);
    Route::get('/reports/awards/{awardId}', [AdminController::class, 'awardReport']);

     // 5 REQUEST WILL BE ALLOWED TO THIS SPECIFIC ROUTE
    Route::middleware('throttle:5,1')->post('/disbursements/{id}/pay', [AdminController::class, 'payDisbursement']);
});

