<?php

use App\Http\Controllers\Approval\ApprovalController;
use App\Http\Controllers\BigdataResumesController;
use App\Http\Controllers\BusinessOrIndustriesController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\Dashboards\LackOfPotentialController;
use App\Http\Controllers\Dashboards\PotentialsController;
use App\Http\Controllers\DataSettingController;
use App\Http\Controllers\Dashboards\BigDataController;
use App\Http\Controllers\GoogleApisController;
use App\Http\Controllers\Home\HomeController;
use App\Http\Controllers\InvitationsController;
use App\Http\Controllers\Master\UsersController;
use App\Http\Controllers\MenusController;
use App\Http\Controllers\PaymentRecapsController;
use App\Http\Controllers\PbgTaskAttachmentsController;
use App\Http\Controllers\QuickSearchController;
use App\Http\Controllers\Report\GrowthReportsController;
use App\Http\Controllers\ReportPaymentRecapsController;
use App\Http\Controllers\ReportPbgPTSPController;
use App\Http\Controllers\RequestAssignment\PbgTaskController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\SyncronizeController;
use App\Http\Controllers\Data\AdvertisementController;
use App\Http\Controllers\Data\UmkmController;
use App\Http\Controllers\Data\TourismController;
use App\Http\Controllers\Data\SpatialPlanningController;
use App\Http\Controllers\Data\GoogleSheetsController;
use App\Http\Controllers\Report\ReportTourismController;
use App\Http\Controllers\Chatbot\ChatbotController;
use App\Http\Controllers\ChatbotPimpinan\ChatbotPimpinanController;
use App\Http\Controllers\TaxationController;
use App\Http\Controllers\TpatptsController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/auth.php';

Route::get('/search', [QuickSearchController::class, 'index'])->name('search');
Route::get('/public-search', [QuickSearchController::class, 'public_search'])->name('public-search');
Route::get('/search-result', [QuickSearchController::class, 'search_result'])->name('search-result');
Route::get('/quick-search-datatable', [QuickSearchController::class, 'quick_search_datatable'])->name('quick-search-datatable');
Route::get('/public-search-datatable', [QuickSearchController::class, 'public_search_datatable'])->name('public-search-datatable');
Route::get('/quick-search/{id}', [QuickSearchController::class, 'show'])->name('quick-search.detail');
Route::get('/quick-search/{uuid}/task-assignments', [QuickSearchController::class, 'task_assignments'])->name('api.quick-search-task-assignments');

// auth 
Route::group(['middleware' => ['auth', 'validate.api.token.web']], function(){

    Route::get('', [BigDataController::class, 'index'])->name('any');
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // API Token Management
    Route::prefix('api-tokens')->group(function() {
        Route::post('/generate', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'generateApiToken'])->name('api-tokens.generate');
        Route::delete('/revoke', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'revokeApiToken'])->name('api-tokens.revoke');
    });

    //dashboards
    Route::group(['prefix' => '/dashboards'], function(){
        Route::get('/bigdata', [BigDataController::class, 'index'])->name('dashboard.home');
        Route::get('/leader', [BigDataController::class, 'leader'])->name('dashboard.leader');
        Route::get('/dashboard-pbg', [BigDataController::class, 'pbg'])->name('dashboard.pbg');
        Route::get('/lack-of-potential', [LackOfPotentialController::class, 'lack_of_potential'])->name('dashboard.lack_of_potential');
        Route::get('/maps', [GoogleApisController::class, 'index'])->name('dashboard.maps');
        Route::get('/inside-system', [PotentialsController::class, 'inside_system'])->name('dashboard.potentials.inside_system');
        Route::get('/outside-system', [PotentialsController::class, 'outside_system'])->name('dashboard.potentials.outside_system');
    });
    
    // settings
    Route::group(['prefix' => '/settings'], function(){
        Route::resource('/general', SettingsController::class);
        Route::get('/syncronize', [SyncronizeController::class, 'index'])->name('settings.syncronize');
        Route::post('/syncronize', [SyncronizeController::class, 'syncronizeTask'])->name('settings.sync');
    });

    // masters
    Route::group(['prefix' => '/master'], function (){
        Route::resource('/users', UsersController::class);
        Route::get('/all-users', [UsersController::class, 'allUsers'])->name('users.all');
    });

    // data - PBG
    Route::resource('/pbg-task', PbgTaskController::class);
    Route::get('/pbg-task-attachment/{attachment_id}', [PbgTaskAttachmentsController::class, 'show'])->name('pbg-task-attachment.show');

    // data settings
    Route::resource('/data-settings', DataSettingController::class);

    // menus
    Route::resource('/menus', MenusController::class);

    // chatbot
    Route::resource('/chatbot', ChatbotController::class);

    // chatbot - pimpinan
    Route::resource('/main-chatbot', ChatbotPimpinanController::class);

    // roles
    Route::resource('/roles', RolesController::class);
    Route::group(['prefix' => '/roles'], function (){
        Route::get('/role-menu/{role_id}', [RolesController::class, 'menu_permission'])->name('role-menu.permission');
        Route::put('/role-menu/{role_id}', [RolesController::class, 'update_menu_permission'])->name('role-menu.permission.update');
    });

    // data
    Route::group(['prefix' => '/data'], function(){
        // Resource route, kecuali create karena dibuat terpisah
        Route::resource('/web-advertisements', AdvertisementController::class)->except(['create', 'show']);

        // Rute khusus untuk create dan bulk-create
        Route::get('/advertisements/create', [AdvertisementController::class, 'create'])->name('advertisements.create');
        Route::get('/advertisements/bulk-create', [AdvertisementController::class, 'bulkCreate'])->name('advertisements.bulk-create');

        // Resource route, kecuali create karena dibuat terpisah
        Route::resource('/web-umkm', UmkmController::class)->except(['create', 'show']);

        // Rute khusus untuk create dan bulk-create
        Route::get('/umkm/create', [UmkmController::class, 'create'])->name('umkm.create');
        Route::get('/umkm/bulk-create', [UmkmController::class, 'bulkCreate'])->name('umkm.bulk-create');

        // Resource route, kecuali create karena dibuat terpisah
        Route::resource('/web-tourisms', TourismController::class)->except(['create', 'show']);
        // Rute khusus untuk create dan bulk-create
        Route::get('/tourisms/create', [TourismController::class, 'create'])->name('tourisms.create');
        Route::get('/tourisms/bulk-create', [TourismController::class, 'bulkCreate'])->name('tourisms.bulk-create');

        // Resource route, kecuali create karena dibuat terpisah
        Route::resource('/web-spatial-plannings', SpatialPlanningController::class)->except(['create', 'show']);
        // Rute khusus untuk create dan bulk-create
        Route::get('/spatial-plannings/create', [SpatialPlanningController::class, 'create'])->name('spatial-plannings.create');
        Route::get('/spatial-plannings/bulk-create', [SpatialPlanningController::class, 'bulkCreate'])->name('spatial-plannings.bulk-create');
        

        Route::resource('/business-industries',BusinessOrIndustriesController::class);

        Route::controller(CustomersController::class)->group( function (){
            Route::get('/customers', 'index')->name('customers');
            Route::get('/customers/create', 'create')->name('customers.create');
            Route::get('/customers/{customer_id}/edit', 'edit')->name('customers.edit');
            Route::get('/customers/upload', 'upload')->name('customers.upload');
        });

        Route::controller(GoogleSheetsController::class)->group(function (){
            Route::get('/google-sheets', 'index')->name('google-sheets');
            Route::get('/google-sheets/create', 'create')->name('google-sheets.create');
            Route::get('/google-sheets/{google_sheet_id}', 'show')->name('google-sheets.show');
            Route::get('/google-sheets/{google_sheet_id}/edit', 'edit')->name('google-sheets.edit');
        });

        // tpa-tpt
        Route::resource('/tpa-tpt', TpatptsController::class);
    });

    // Report
    Route::group(['prefix' => '/report'], function(){
        // Resource route, kecuali create karena dibuat terpisah
        Route::controller(ReportTourismController::class)->group(function (){
            Route::get('/tourisms-report','index')->name('tourisms-report.index');
        });

        Route::controller(BigdataResumesController::class)->group(function (){
            Route::get('/bigdata-resumes', 'index')->name('bigdata-resumes');
        });
        Route::controller(PaymentRecapsController::class)->group(function (){
            Route::get('/payment-recaps', 'index')->name('payment-recaps');
        });

        Route::controller(ReportPaymentRecapsController::class)->group(function (){
            Route::get('/report-payment-recaps', 'index')->name('report-payment-recaps');
        });

        Route::controller(ReportPbgPTSPController::class)->group(function (){
            Route::get('/report-pbg-ptsp', 'index')->name('report-pbg-ptsp');
        });

        Route::controller(GrowthReportsController::class)->group(function (){
            Route::get('/growths','index')->name('growths');
        });
    });

    // approval
    Route::group(['prefix' => '/approval'], function (){
        Route::get('/list',[ApprovalController::class, 'index'])->name('approval-list');
    });

    // tools
    Route::group(['prefix' => '/tools'], function (){
        Route::get('/invitations', [InvitationsController::class, 'index'])->name('invitations');
    });

    // taxation
    Route::group(['prefix' => '/tax'], function (){
        Route::get('/', [TaxationController::class, 'index'])->name('taxation');
        Route::get('/upload', [TaxationController::class, 'upload'])->name('taxation.upload');
        Route::get('/{id}/edit', [TaxationController::class, 'edit'])->name('taxation.edit');
    });
});