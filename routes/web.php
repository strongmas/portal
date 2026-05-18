<?php

use App\Http\Controllers\Admin\AdminAppointmentController;
use App\Http\Controllers\Admin\AdminBuyerController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminInterestController;
use App\Http\Controllers\Admin\AdminKycController;
use App\Http\Controllers\Admin\AdminMarketInsightController;
use App\Http\Controllers\Admin\AdminOwnershipController;
use App\Http\Controllers\Admin\AdminPaymentController as AdminPaymentCtrl;
use App\Http\Controllers\Admin\AdminProjectMilestoneController;
use App\Http\Controllers\Admin\AdminProjectUpdateController;
use App\Http\Controllers\Admin\AdminPropertyDocumentController;
use App\Http\Controllers\Admin\AdminPropertyImageController;
use App\Http\Controllers\Admin\AdminPropertyValueController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminSalesRepController;
use App\Http\Controllers\Admin\AdminStaffController;
use App\Http\Controllers\Admin\AdminSupportController;
use App\Http\Controllers\Admin\AdminPropertyController;
use App\Http\Controllers\Admin\AdminUnitImageController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\InterestRegistrationController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LoginActivityController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MarketInsightController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectUpdateController;
use App\Http\Controllers\PropertyDocumentController;
use App\Http\Controllers\PropertyValueController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SupportController;
use Illuminate\Support\Facades\Route;

// ── Public routes (no auth) ──────────────────────────────────────────────────
Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept'])->name('invitations.accept');

// ── Buyer portal ─────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('home');

    // Payments
    Route::get('/payments/new', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
    Route::get('/payments', [HistoryController::class, 'index'])->name('payments.history');
    Route::get('/payments/{payment}/receipt', [HistoryController::class, 'receipt'])->name('payments.receipt');
    Route::get('/payments/{payment}/proof', [HistoryController::class, 'proof'])->name('payments.proof');

    // Balance / properties
    Route::get('/properties', [BalanceController::class, 'index'])->name('properties.index');
    Route::get('/properties/{ownership}', [BalanceController::class, 'show'])->name('properties.show');
    Route::get('/properties/{ownership}/values', [PropertyValueController::class, 'show'])->name('property-values.show');
    Route::get('/analytics', [PropertyValueController::class, 'analyticsIndex'])->name('analytics.index');
    Route::get('/properties/{ownership}/analytics', [PropertyValueController::class, 'analytics'])->name('property-analytics.show');

    // Support + file attachments
    Route::get('/support', [SupportController::class, 'index'])->name('support');
    Route::post('/support/messages', [SupportController::class, 'sendMessage'])->name('support.messages.store');
    Route::get('/support/messages', [SupportController::class, 'messages'])->name('support.messages');
    Route::post('/support/tickets', [SupportController::class, 'storeTicket'])->name('support.tickets.store');
    Route::get('/support/attachments/{message}', [SupportController::class, 'downloadAttachment'])->name('support.attachments.download');

    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Appointments
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::patch('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');

    // Secure Documents
    Route::get('/documents', [PropertyDocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [PropertyDocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/download', [PropertyDocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{document}', [PropertyDocumentController::class, 'destroy'])->name('documents.destroy');

    // Project Updates
    Route::get('/project-updates', [ProjectUpdateController::class, 'index'])->name('project-updates.index');
    Route::get('/project-updates/{projectUpdate}', [ProjectUpdateController::class, 'show'])->name('project-updates.show');
    Route::post('/project-updates/{projectUpdate}/comments', [ProjectUpdateController::class, 'comment'])->name('project-updates.comments.store');
    Route::get('/project-updates/documents/{document}/download', [ProjectUpdateController::class, 'downloadDocument'])->name('project-updates.documents.download');

    // Login Activity
    Route::get('/login-activity', [LoginActivityController::class, 'index'])->name('login-activity.index');

    // Session Management
    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::post('/sessions/destroy-others', [SessionController::class, 'destroyOthers'])->name('sessions.destroy-others');
    Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])->name('sessions.destroy');

    // Group / Ownership Members
    Route::get('/ownerships/{ownership}/group', [GroupController::class, 'index'])->name('group.index');
    Route::post('/ownerships/{ownership}/group/invite', [GroupController::class, 'invite'])->name('group.invite');
    Route::delete('/ownerships/{ownership}/group/members/{member}', [GroupController::class, 'removeMember'])->name('group.members.destroy');
    Route::delete('/ownerships/{ownership}/group/invitations/{invitation}', [GroupController::class, 'cancelInvitation'])->name('group.invitations.destroy');
    Route::patch('/ownerships/{ownership}/group/members/{ownershipMember}/role', [GroupController::class, 'updateRole'])->name('group.members.update-role');

    // Market Insights
    Route::get('/market-insights', [MarketInsightController::class, 'index'])->name('market-insights.index');
    Route::get('/market-insights/{marketInsight}', [MarketInsightController::class, 'show'])->name('market-insights.show');

    // Map
    Route::get('/map', [MapController::class, 'index'])->name('map.index');

    // Browse available properties & units
    Route::get('/browse', [BrowseController::class, 'index'])->name('browse.index');
    Route::get('/browse/{property}', [BrowseController::class, 'show'])->name('browse.show');

    // Register / remove interest
    Route::post('/browse/{property}/interest', [InterestRegistrationController::class, 'store'])->name('interests.store');
    Route::delete('/interests/{interest}', [InterestRegistrationController::class, 'destroy'])->name('interests.destroy');
});

// ── Admin portal ─────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {

    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // ── Available to all admin users (super + staff) ──────────────────────

    // Payments (view + flag)
    Route::get('payments', [AdminPaymentCtrl::class, 'index'])->name('payments.index');
    Route::get('payments/{payment}', [AdminPaymentCtrl::class, 'show'])->name('payments.show');
    Route::patch('payments/{payment}/flag', [AdminPaymentCtrl::class, 'flag'])->name('payments.flag');
    Route::patch('payments/{payment}/unflag', [AdminPaymentCtrl::class, 'unflag'])->name('payments.unflag');

    // Support — chat threads
    Route::get('support', [AdminSupportController::class, 'index'])->name('support.index');
    Route::post('support/{user}/reply', [AdminSupportController::class, 'reply'])->name('support.reply');
    Route::get('support/attachments/{message}', [SupportController::class, 'downloadAttachment'])->name('support.attachments.admin-download');

    // Tickets
    Route::get('tickets', [AdminSupportController::class, 'tickets'])->name('tickets.index');
    Route::get('tickets/{ticket}', [AdminSupportController::class, 'ticketShow'])->name('tickets.show');
    Route::patch('tickets/{ticket}', [AdminSupportController::class, 'updateTicket'])->name('tickets.update');
    Route::post('tickets/{ticket}/reply', [AdminSupportController::class, 'ticketReply'])->name('tickets.reply');

    // KYC
    Route::get('kyc', [AdminKycController::class, 'index'])->name('kyc.index');
    Route::post('kyc/{user}/approve', [AdminKycController::class, 'approve'])->name('kyc.approve');
    Route::post('kyc/{user}/reject', [AdminKycController::class, 'reject'])->name('kyc.reject');

    // Appointments
    Route::get('appointments', [AdminAppointmentController::class, 'index'])->name('appointments.index');
    Route::get('appointments/{appointment}', [AdminAppointmentController::class, 'show'])->name('appointments.show');
    Route::patch('appointments/{appointment}', [AdminAppointmentController::class, 'update'])->name('appointments.update');

    // Interest Registrations
    Route::get('interests', [AdminInterestController::class, 'index'])->name('interests.index');
    Route::post('interests/{interest}/forward', [AdminInterestController::class, 'forward'])->name('interests.forward');

    // Project Updates (index + show + comment — all admin users)
    Route::get('project-updates', [AdminProjectUpdateController::class, 'index'])->name('project-updates.index');
    Route::get('properties/{property}/project-updates/{projectUpdate}', [AdminProjectUpdateController::class, 'show'])->name('project-updates.show');
    Route::post('properties/{property}/project-updates/{projectUpdate}/comments', [AdminProjectUpdateController::class, 'storeComment'])->name('project-updates.comments.store');
    Route::delete('project-update-images/{image}', [AdminProjectUpdateController::class, 'destroyImage'])->name('project-update-images.destroy');
    Route::delete('project-update-documents/{document}', [AdminProjectUpdateController::class, 'destroyDocument'])->name('project-update-documents.destroy');

    // ── Super admin only ───────────────────────────────────────────────────
    Route::middleware('superadmin')->group(function () {

        // Staff & role management
        Route::get('staff', [AdminStaffController::class, 'index'])->name('staff.index');
        Route::get('staff/create', [AdminStaffController::class, 'create'])->name('staff.create');
        Route::post('staff', [AdminStaffController::class, 'store'])->name('staff.store');
        Route::get('staff/{staff}/edit', [AdminStaffController::class, 'edit'])->name('staff.edit');
        Route::patch('staff/{staff}', [AdminStaffController::class, 'update'])->name('staff.update');
        Route::post('staff/{staff}/reset-password', [AdminStaffController::class, 'resetPassword'])->name('staff.reset-password');
        Route::delete('staff/{staff}', [AdminStaffController::class, 'destroy'])->name('staff.destroy');

        Route::get('roles', [AdminRoleController::class, 'index'])->name('roles.index');
        Route::get('roles/create', [AdminRoleController::class, 'create'])->name('roles.create');
        Route::post('roles', [AdminRoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{role}/edit', [AdminRoleController::class, 'edit'])->name('roles.edit');
        Route::patch('roles/{role}', [AdminRoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [AdminRoleController::class, 'destroy'])->name('roles.destroy');

        // Buyers + Sales Rep assignment
        Route::resource('buyers', AdminBuyerController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update']);
        Route::post('buyers/{buyer}/sales-rep', [AdminSalesRepController::class, 'assignToBuyer'])->name('buyers.assign-rep');
        Route::post('buyers/{buyer}/reset-password', [AdminBuyerController::class, 'resetPassword'])->name('buyers.reset-password');

        // Sales Reps
        Route::get('sales-reps', [AdminSalesRepController::class, 'index'])->name('sales-reps.index');
        Route::post('sales-reps', [AdminSalesRepController::class, 'store'])->name('sales-reps.store');
        Route::patch('sales-reps/{salesRep}', [AdminSalesRepController::class, 'update'])->name('sales-reps.update');
        Route::delete('sales-reps/{salesRep}', [AdminSalesRepController::class, 'destroy'])->name('sales-reps.destroy');

        // Properties + unit management
        Route::resource('properties', AdminPropertyController::class);
        Route::post('properties/{property}/units', [AdminPropertyController::class, 'storeUnit'])->name('properties.units.store');
        Route::patch('properties/{property}/units/{unit}', [AdminPropertyController::class, 'updateUnit'])->name('properties.units.update');
        Route::delete('properties/{property}/units/{unit}', [AdminPropertyController::class, 'destroyUnit'])->name('properties.units.destroy');

        // Property Images
        Route::post('properties/{property}/images', [AdminPropertyImageController::class, 'store'])->name('property-images.store');
        Route::post('properties/{property}/images/{image}/cover', [AdminPropertyImageController::class, 'setCover'])->name('property-images.cover');
        Route::post('properties/{property}/images/reorder', [AdminPropertyImageController::class, 'reorder'])->name('property-images.reorder');
        Route::delete('properties/{property}/images/{image}', [AdminPropertyImageController::class, 'destroy'])->name('property-images.destroy');

        // Unit Type Images
        Route::post('properties/{property}/units/{unit}/images', [AdminUnitImageController::class, 'store'])->name('unit-images.store');
        Route::delete('properties/{property}/units/{unit}/images/{image}', [AdminUnitImageController::class, 'destroy'])->name('unit-images.destroy');

        // Property Documents — admin uploads
        Route::post('properties/{property}/documents', [AdminPropertyDocumentController::class, 'store'])->name('property-documents.store');

        // Property Documents (buyer-uploaded, admin reviews)
        Route::get('documents', [AdminPropertyDocumentController::class, 'index'])->name('documents.index');
        Route::patch('documents/{propertyDocument}/verify', [AdminPropertyDocumentController::class, 'verify'])->name('documents.verify');
        Route::patch('documents/{propertyDocument}/flag', [AdminPropertyDocumentController::class, 'flag'])->name('documents.flag');
        Route::patch('documents/{propertyDocument}/unflag', [AdminPropertyDocumentController::class, 'unflag'])->name('documents.unflag');
        Route::get('documents/{propertyDocument}/download', [AdminPropertyDocumentController::class, 'adminDownload'])->name('documents.download');
        Route::delete('documents/{propertyDocument}', [AdminPropertyDocumentController::class, 'destroy'])->name('documents.destroy');

        // Project Updates — create & post (super admin only)
        Route::get('properties/{property}/project-updates/create', [AdminProjectUpdateController::class, 'create'])->name('project-updates.create');
        Route::post('properties/{property}/project-updates', [AdminProjectUpdateController::class, 'store'])->name('project-updates.store');

        // Milestones
        Route::get('properties/{property}/milestones', [AdminProjectMilestoneController::class, 'index'])->name('milestones.index');
        Route::post('properties/{property}/milestones', [AdminProjectMilestoneController::class, 'store'])->name('milestones.store');
        Route::patch('properties/{property}/milestones/{milestone}', [AdminProjectMilestoneController::class, 'update'])->name('milestones.update');
        Route::delete('properties/{property}/milestones/{milestone}', [AdminProjectMilestoneController::class, 'destroy'])->name('milestones.destroy');
        Route::post('properties/{property}/milestones/reorder', [AdminProjectMilestoneController::class, 'reorder'])->name('milestones.reorder');

        // Ownerships
        Route::get('ownerships/create', [AdminOwnershipController::class, 'create'])->name('ownerships.create');
        Route::post('ownerships', [AdminOwnershipController::class, 'store'])->name('ownerships.store');
        Route::get('ownerships/{ownership}/edit', [AdminOwnershipController::class, 'edit'])->name('ownerships.edit');
        Route::patch('ownerships/{ownership}', [AdminOwnershipController::class, 'update'])->name('ownerships.update');
        Route::delete('ownerships/{ownership}', [AdminOwnershipController::class, 'destroy'])->name('ownerships.destroy');

        // Property Value Tracker
        Route::get('property-values', [AdminPropertyValueController::class, 'index'])->name('property-values.index');
        Route::post('property-values', [AdminPropertyValueController::class, 'store'])->name('property-values.store');
        Route::delete('property-values/{propertyValueHistory}', [AdminPropertyValueController::class, 'destroy'])->name('property-values.destroy');

        // Market Insights
        Route::resource('market-insights', AdminMarketInsightController::class)->except(['show']);
    });
});

require __DIR__ . '/auth.php';
