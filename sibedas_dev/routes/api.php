<?php

use App\Http\Controllers\Api\BigDataResumeController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\BusinessOrIndustriesController;
use App\Http\Controllers\Api\CustomersController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DataSettingController;
use App\Http\Controllers\Api\GlobalSettingsController;
use App\Http\Controllers\Api\GoogleSheetController;
use App\Http\Controllers\Api\GrowthReportAPIController;
use App\Http\Controllers\Api\ImportDatasourceController;
use App\Http\Controllers\Api\LackOfPotentialController;
use App\Http\Controllers\Api\MenusController;
use App\Http\Controllers\Api\PbgTaskAttachmentsController;
use App\Http\Controllers\Api\PbgTaskController;
use App\Http\Controllers\Api\PbgTaskGoogleSheetsController;
use App\Http\Controllers\Api\ReportPbgPtspController;
use App\Http\Controllers\Api\ReportTourismsController;
use App\Http\Controllers\Api\RequestAssignmentController;
use App\Http\Controllers\Api\RolesController;
use App\Http\Controllers\Api\ScrapingController;
use App\Http\Controllers\Api\SpatialPlanningsController;
use App\Http\Controllers\Api\TaskAssignmentsController;
use App\Http\Controllers\Api\UsersController;
use App\Http\Controllers\Settings\SyncronizeController;
use App\Http\Controllers\Api\AdvertisementController;
use App\Http\Controllers\Api\UmkmController;
use App\Http\Controllers\Api\TourismController;
use App\Http\Controllers\Api\SpatialPlanningController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\TaxationsController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [UsersController::class, 'login'])->name('api.user.login');
Route::post('/generate-text', [ChatbotController::class, 'generateText']);
Route::post('/main-generate-text', [ChatbotController::class, 'mainGenerateText']);
Route::group(['middleware' => 'auth:sanctum'], function (){
    // users
    Route::controller(UsersController::class)->group(function(){
        Route::get('/users', 'index')->name('api.users');
        Route::post('/users', 'store')->name('api.users.store');
        Route::put('/users/{id}', 'update')->name('api.users.update');
        Route::delete('/users/{id}','destroy')->name('api.users.destroy');
        Route::post('/logout','logout')->name('api.users.logout');
    });
    
    // global settings
    Route::apiResource('global-settings', GlobalSettingsController::class);

    // import datasource
    // Route::apiResource('import-datasource',ImportDatasourceController::class);
    Route::controller(ImportDatasourceController::class)->group(function (){
        Route::get('/import-datasource/check-datasource', 'checkImportDatasource')
        ->name('import-datasource.check');
        Route::get('/import-datasource', 'index')->name('import-datasource.index');
    });

    // request assignments
    Route::apiResource('request-assignments',RequestAssignmentController::class);
    Route::get('/report-payment-recaps',[RequestAssignmentController::class, 'report_payment_recaps'])->name('api.report-payment-recaps');
    Route::get('/report-pbg-ptsp',[RequestAssignmentController::class, 'report_pbg_ptsp'])->name('api.report-pbg-ptsp');
    Route::controller(RequestAssignmentController::class)->group( function (){
        Route::get('/pbg-task/export-excel', 'export_excel_pbg_tasks')->name('api.pbg-task.export-excel');
        Route::get('/district-payment-report/excel', 'export_excel_district_payment_recaps')->name('api.district-payment-report.excel');
        Route::get('/district-payment-report/pdf', 'export_pdf_district_payment_recaps')->name('api.district-payment-report.pdf');
    });

    // all dashboards
    Route::controller(DashboardController::class)->group(function(){
        Route::get('/business-documents','businnessDocument');
        Route::get('/non-business-documents','nonBusinnessDocument');
        Route::get('/all-task-documents', 'allTaskDocuments');
        Route::get('/pbg-task-documents', 'pbgTaskDocuments');
        Route::get('/verification-documents','verificationDocuments');
        Route::get('/non-verification-documents','nonVerificationDocuments');
    });

    // scraping
    Route::controller(ScrapingController::class)->group(function (){
        Route::get('/scraping','index')->name('scraping');
        Route::post('/scraping/{id}/pause','pause')->name('scraping.pause');
        Route::post('/scraping/{id}/resume','resume')->name('scraping.resume');
        Route::post('/scraping/{id}/cancel','cancel')->name('scraping.cancel');
        Route::get('/retry-scraping/{id}','retry_syncjob')->name('retry-scraping');
    });

    // reklame
    Route::apiResource('advertisements', AdvertisementController::class);
    Route::get('/combobox/search-options', [AdvertisementController::class, 'searchOptionsInAdvertisements']);
    Route::post('/advertisements/import', [AdvertisementController::class, 'importFromFile']);
    Route::get('/download-template-advertisement', [AdvertisementController::class, 'downloadExcelAdvertisement']);

    // umkm
    Route::apiResource('umkm', UmkmController::class);
    Route::post('/umkm/import', [UmkmController::class, 'importFromFile']);
    Route::get('/download-template-umkm', [UmkmController::class, 'downloadExcelUmkm']);

    //tourism
    Route::apiResource('tourisms', TourismController::class);
    Route::post('/tourisms/import', [TourismController::class, 'importFromFile']);
    Route::get('/download-template-tourism', [TourismController::class, 'downloadExcelTourism']);
    Route::get('/get-all-location', [TourismController::class, 'getAllLocation']);

    Route::apiResource('spatial-plannings', SpatialPlanningController::class);
    Route::post('/spatial-plannings/import', [SpatialPlanningController::class, 'importFromFile']);
    Route::get('/download-template-spatialPlannings', [SpatialPlanningController::class, 'downloadExcelSpatialPlanning']);
    
    // data-settings
    // Route::apiResource('/api-data-settings', DataSettingController::class);
    Route::controller(DataSettingController::class)->group(function (){
        Route::get('/data-settings', 'index')->name('api.data-settings');
        Route::post('/data-settings', 'store')->name('api.data-settings.store');
        Route::put('/data-settings/{data_setting_id}', 'update')->name('api.data-settings.update');
        Route::delete('/data-settings/{data_setting_id}', 'destroy')->name('api.data-settings.destroy');
    });

    Route::apiResource('/api-pbg-task', PbgTaskController::class);
    Route::controller(PbgTaskController::class)->group( function (){
        Route::put('/pbg-task/{task_uuid}/update', 'update')->name('api.pbg-task.update');
    });

    // sync pbg google sheet
    Route::apiResource('/api-google-sheet', GoogleSheetController::class);

    // menus api
    Route::controller(MenusController::class)->group(function (){
        Route::get('/menus', 'index')->name('api.menus');
        Route::post('/menus', 'store')->name('api.menus.store');
        Route::put('/menus/{menu_id}', 'update')->name('api.menus.update');
        Route::delete('/menus/{menu_id}', 'destroy')->name('api.menus.destroy');
    });

    // roles api
    Route::controller(RolesController::class)->group(function (){
        Route::get('/roles', 'index')->name('api.roles');
        Route::post('/roles', 'store')->name('api.roles.store');
        Route::put('/roles/{role_id}', 'update')->name('api.roles.update');
        Route::delete('/roles/{role_id}', 'destroy')->name('api.roles.destroy');
    });

    //business industries api 
    Route::apiResource('api-business-industries', BusinessOrIndustriesController::class);
    Route::post('api-business-industries/upload', [BusinessOrIndustriesController::class, 'upload'])->name('business-industries.upload');

    Route::controller(CustomersController::class)->group( function (){
        Route::get('/customers', 'index')->name('api.customers');
        Route::post('/customers', 'store')->name('api.customers.store');
        Route::put('/customers/{id}', 'update')->name('api.customers.update');
        Route::delete('/customers/{id}', 'destroy')->name('api.customers.destroy');
        Route::post('/customers/upload', 'upload')->name('api.customers.upload');
    });

    //dashboard potensi
    Route::get('/dashboard-potential-count', [LackOfPotentialController::class, 'count_lack_of_potential'])->name('api.count-dashboard-potential');

    // big data resume
    Route::controller(BigDataResumeController::class)->group(function (){
        Route::get('/bigdata-resume', 'index')->name('api.bigdata-resume');
        Route::get('/bigdata-report', 'bigdata_report')->name('api.bigdata-report');
        Route::get('/payment-recaps', 'payment_recaps')->name('api.payment-recaps');
        Route::get('/payment-recaps/excel', 'export_excel_payment_recaps')->name('api.payment-recaps.excel');
        Route::get('/payment-recaps/pdf', 'export_pdf_payment_recaps')->name('api.payment-recaps.pdf');
        Route::get('/report-director/excel', 'export_excel_report_director')->name('api.report-director.excel');
        Route::get('/report-director/pdf', 'export_pdf_report_director')->name('api.report-director.pdf');
    });

    // task-assignments
    Route::controller(TaskAssignmentsController::class)->group(function (){
        Route::get('/task-assignments/{uuid}', 'index')->name('api.task-assignments');
    });

    // pbg-task-google-sheet
    Route::apiResource('pbg-task-google-sheet', PbgTaskGoogleSheetsController::class);

    // export
    Route::controller(ReportTourismsController::class)->group(function (){
        Route::get('/report-tourisms/excel', 'export_excel')->name('api.report-tourisms.excel');
        Route::get('/report-tourisms/pdf', 'export_pdf')->name('api.report-tourisms.pdf');
    });

    Route::controller(ReportPbgPtspController::class)->group( function (){
        Route::get('/report-ptsp/excel', 'export_excel')->name('api.report-ptsp.excel');
        Route::get('/report-ptsp/pdf', 'export_pdf')->name('api.report-ptsp.pdf');
    });

    Route::controller(PbgTaskAttachmentsController::class)->group(function (){
        Route::post('/pbg-task-attachment/{pbg_task_id}', 'store')->name('api.pbg-task.upload');
        Route::get('/pbg-task-attachment/{attachment_id}/download', 'download')->name('api.pbg-task.download');
    });

    Route::controller(GrowthReportAPIController::class)->group(function(){
        Route::get('/growth','index')->name('api.growth');
    });

    Route::controller(TaxationsController::class)->group(function (){
        Route::get('/taxs', 'index')->name('api.taxs');
        Route::post('/taxs/upload', 'upload')->name('api.taxs.upload');
        Route::get('/taxs/export', 'export')->name('api.taxs.export');
        Route::delete('/taxs/{id}', 'delete')->name('api.taxs.delete');
        Route::put('/taxs/{id}', 'update')->name('api.taxs.update');
    });

    // TODO: Implement new retribution calculation API endpoints using the new schema
});

// GitHub webhook — no auth middleware
Route::post('/webhook/github', [WebhookController::class, 'github'])->name('webhook.github');