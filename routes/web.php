<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\CatalogueController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WardController as AdminWardController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InsuranceController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LabController;
use App\Http\Controllers\MaternityController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\Platform\FacilityAdminController;
use App\Http\Controllers\Platform\FacilityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\RadiologyController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TheatreController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\WardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->homeRoute());
    }

    return view('welcome');
});

Route::middleware(['auth', 'verified'])->prefix('onboarding')->group(function () {
    Route::get('/{step?}', [OnboardingController::class, 'show'])
        ->whereIn('step', OnboardingController::STEPS)
        ->name('onboarding.show');
    Route::post('/facility', [OnboardingController::class, 'saveFacility'])->name('onboarding.facility');
    Route::post('/wards', [OnboardingController::class, 'saveWards'])->name('onboarding.wards');
    Route::post('/finish', [OnboardingController::class, 'finish'])->name('onboarding.finish');
});

Route::middleware(['auth', 'verified', 'onboarded'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::middleware('permission:patients.manage')->group(function () {
        Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
        Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
        Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit');
        Route::patch('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
        Route::post('/patients/{patient}/visits', [VisitController::class, 'store'])->name('visits.store');
    });

    Route::middleware('permission:patients.view')->group(function () {
        Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
        Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
        Route::get('/patients/{patient}/photo', [PatientController::class, 'photo'])->name('patients.photo');
        Route::get('/visits/{visit}', [VisitController::class, 'show'])->name('visits.show');
    });

    Route::post('/visits/{visit}/triage', [VisitController::class, 'triage'])
        ->middleware('permission:triage.manage')
        ->name('visits.triage');
    Route::post('/visits/{visit}/consult', [VisitController::class, 'consult'])
        ->middleware('permission:consultations.manage')
        ->name('visits.consult');
    Route::post('/visits/{visit}/labs', [VisitController::class, 'orderLab'])
        ->middleware('permission:consultations.manage')
        ->name('visits.labs');
    Route::post('/visits/{visit}/imaging', [VisitController::class, 'orderImaging'])
        ->middleware('permission:consultations.manage')
        ->name('visits.imaging');
    Route::post('/visits/{visit}/prescriptions', [VisitController::class, 'prescribe'])
        ->middleware('permission:consultations.manage')
        ->name('visits.prescribe');
    Route::post('/visits/{visit}/admit', [VisitController::class, 'admit'])
        ->middleware('permission:wards.manage')
        ->name('visits.admit');
    Route::post('/visits/{visit}/complete', [VisitController::class, 'complete'])
        ->middleware('permission:consultations.manage,cashier.manage,wards.manage')
        ->name('visits.complete');

    Route::middleware('permission:appointments.manage')->group(function () {
        Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
        Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
        Route::post('/appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn'])->name('appointments.check-in');
        Route::post('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
    });

    Route::middleware('permission:queue.view')->group(function () {
        Route::get('/queues', [QueueController::class, 'index'])->name('queues.index');
        Route::post('/queues/call', [QueueController::class, 'call'])->name('queues.call');
        Route::post('/queues/{entry}/complete', [QueueController::class, 'complete'])->name('queues.complete');
    });

    Route::middleware('permission:lab.view')->group(function () {
        Route::get('/laboratory', [LabController::class, 'index'])->name('lab.index');
    });
    Route::post('/laboratory/items/{item}/verify', [LabController::class, 'verify'])
        ->middleware('permission:lab.manage')
        ->name('lab.verify');
    Route::post('/laboratory/items/{item}/cancel', [LabController::class, 'cancel'])
        ->middleware('permission:consultations.manage')
        ->name('lab.cancel');

    Route::middleware('permission:radiology.view')->group(function () {
        Route::get('/radiology', [RadiologyController::class, 'index'])->name('radiology.index');
        Route::get('/radiology/{order}/image', [RadiologyController::class, 'image'])->name('radiology.image');
    });
    Route::post('/radiology/{order}/report', [RadiologyController::class, 'report'])
        ->middleware('permission:radiology.manage')
        ->name('radiology.report');

    Route::middleware('permission:pharmacy.view')->group(function () {
        Route::get('/pharmacy', [PharmacyController::class, 'index'])->name('pharmacy.index');
    });
    Route::post('/pharmacy/{prescriptionItem}/dispense', [PharmacyController::class, 'dispense'])
        ->middleware('permission:pharmacy.manage')
        ->name('pharmacy.dispense');
    Route::post('/pharmacy/{prescriptionItem}/cancel', [PharmacyController::class, 'cancel'])
        ->middleware('permission:consultations.manage,pharmacy.manage')
        ->name('pharmacy.cancel');

    Route::middleware('permission:cashier.manage')->group(function () {
        Route::get('/cashier', [CashierController::class, 'index'])->name('cashier.index');
        Route::post('/cashier/shift', [CashierController::class, 'openShift'])->name('cashier.shift');
        Route::post('/cashier/shift/{shift}/close', [CashierController::class, 'closeShift'])->name('cashier.close');
        Route::post('/cashier/bills/{bill}/pay', [CashierController::class, 'pay'])->name('cashier.pay');
        Route::post('/cashier/bills/{bill}/waivers', [CashierController::class, 'requestWaiver'])->name('cashier.waivers');
        Route::get('/receipts/{payment}', [ReceiptController::class, 'show'])->name('receipts.show');
    });

    Route::post('/waivers/{waiver}/approve', [CashierController::class, 'approveWaiver'])
        ->middleware('permission:waivers.approve')
        ->name('waivers.approve');
    Route::post('/waivers/{waiver}/reject', [CashierController::class, 'rejectWaiver'])
        ->middleware('permission:waivers.approve')
        ->name('waivers.reject');

    Route::middleware('permission:insurance.manage')->group(function () {
        Route::get('/insurance', [InsuranceController::class, 'index'])->name('insurance.index');
        Route::post('/insurance/schemes', [InsuranceController::class, 'storeScheme'])->name('insurance.schemes.store');
        Route::post('/insurance/preauthorisations', [InsuranceController::class, 'storePreauthorisation'])->name('insurance.preauthorisations.store');
        Route::post('/insurance/preauthorisations/{preauthorisation}', [InsuranceController::class, 'decidePreauthorisation'])->name('insurance.preauthorisations.decide');
        Route::post('/insurance/claims', [InsuranceController::class, 'storeClaim'])->name('insurance.claims.store');
        Route::post('/insurance/claims/{claim}/submit', [InsuranceController::class, 'submitClaim'])->name('insurance.claims.submit');
        Route::post('/insurance/claims/{claim}/remit', [InsuranceController::class, 'remit'])->name('insurance.claims.remit');
    });

    Route::middleware('permission:wards.view')->group(function () {
        Route::get('/wards', [WardController::class, 'index'])->name('wards.index');
        Route::get('/wards/admissions/{admission}', [WardController::class, 'show'])->name('wards.show');
    });
    Route::middleware('permission:wards.manage')->group(function () {
        Route::post('/wards/admissions/{admission}/notes', [WardController::class, 'note'])->name('wards.notes');
        Route::post('/wards/admissions/{admission}/medications', [WardController::class, 'medication'])->name('wards.medications');
        Route::post('/wards/admissions/{admission}/issue', [WardController::class, 'issue'])->name('wards.issue');
        Route::post('/wards/admissions/{admission}/discharge', [WardController::class, 'discharge'])->name('wards.discharge');
        Route::post('/wards/beds/{bed}/clean', [WardController::class, 'cleanBed'])->name('wards.beds.clean');
    });

    Route::middleware('permission:maternity.manage')->group(function () {
        Route::get('/maternity', [MaternityController::class, 'index'])->name('maternity.index');
        Route::post('/maternity/antenatal', [MaternityController::class, 'antenatal'])->name('maternity.antenatal');
        Route::post('/maternity/deliveries', [MaternityController::class, 'delivery'])->name('maternity.deliveries');
    });

    Route::middleware('permission:theatre.manage')->group(function () {
        Route::get('/theatre', [TheatreController::class, 'index'])->name('theatre.index');
        Route::post('/theatre', [TheatreController::class, 'store'])->name('theatre.store');
        Route::post('/theatre/{booking}/notes', [TheatreController::class, 'note'])->name('theatre.notes');
    });

    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    });
    Route::post('/inventory/orders', [InventoryController::class, 'order'])->middleware('permission:inventory.purchase')->name('inventory.order');
    Route::post('/inventory/orders/{order}/receive', [InventoryController::class, 'receive'])->middleware('permission:inventory.purchase')->name('inventory.receive');
    Route::post('/inventory/requisitions', [InventoryController::class, 'requisition'])->middleware('permission:inventory.requisition')->name('inventory.requisition');
    Route::post('/inventory/requisitions/{requisition}/issue', [InventoryController::class, 'issue'])->middleware('permission:inventory.purchase')->name('inventory.issue');
    Route::post('/inventory/adjustments', [InventoryController::class, 'adjust'])->middleware('permission:inventory.adjust')->name('inventory.adjust');
    Route::post('/inventory/adjustments/{adjustment}/approve', [InventoryController::class, 'approve'])->middleware('permission:inventory.approve')->name('inventory.approve');

    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    });

    Route::middleware('permission:admin.manage')->prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('admin.users.store');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
        Route::get('/departments', [DepartmentController::class, 'index'])->name('admin.departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('admin.departments.store');
        Route::get('/wards', [AdminWardController::class, 'index'])->name('admin.wards.index');
        Route::post('/wards', [AdminWardController::class, 'store'])->name('admin.wards.store');
        Route::patch('/wards/{ward}', [AdminWardController::class, 'update'])->name('admin.wards.update');
        Route::delete('/wards/{ward}', [AdminWardController::class, 'destroy'])->name('admin.wards.destroy');
        Route::post('/wards/{ward}/beds', [AdminWardController::class, 'storeBeds'])->name('admin.wards.beds.store');
        Route::patch('/beds/{bed}', [AdminWardController::class, 'updateBed'])->name('admin.beds.update');
        Route::patch('/beds/{bed}/name', [AdminWardController::class, 'renameBed'])->name('admin.beds.rename');
        Route::delete('/beds/{bed}', [AdminWardController::class, 'destroyBed'])->name('admin.beds.destroy');
        Route::get('/catalogue', [CatalogueController::class, 'index'])->name('admin.catalogue.index');
        Route::post('/catalogue/services', [CatalogueController::class, 'storeService'])->name('admin.services.store');
        Route::post('/catalogue/items', [CatalogueController::class, 'storeItem'])->name('admin.items.store');
        Route::post('/catalogue/lab-tests', [CatalogueController::class, 'storeLabTest'])->name('admin.lab-tests.store');
        Route::post('/catalogue/studies', [CatalogueController::class, 'storeStudy'])->name('admin.studies.store');
        Route::get('/audit', [AuditController::class, 'index'])->name('admin.audit.index');
    });

    Route::middleware('permission:platform.manage')->prefix('platform')->group(function () {
        Route::get('/facilities', [FacilityController::class, 'index'])->name('platform.facilities.index');
        Route::post('/facilities', [FacilityController::class, 'store'])->name('platform.facilities.store');
        Route::patch('/facilities/{facility}', [FacilityController::class, 'update'])->name('platform.facilities.update');
        Route::get('/facilities/{facility}/admins', [FacilityAdminController::class, 'index'])->name('platform.facilities.admins.index');
        Route::post('/facilities/{facility}/admins', [FacilityAdminController::class, 'store'])->name('platform.facilities.admins.store');
        Route::patch('/facilities/{facility}/admins/{user}', [FacilityAdminController::class, 'update'])->name('platform.facilities.admins.update');
    });
});

require __DIR__.'/auth.php';
