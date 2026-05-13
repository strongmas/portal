<?php

namespace App\Providers;

use App\Events\AppointmentBooked;
use App\Events\DocumentUploaded;
use App\Events\GroupInvitationSent;
use App\Events\ProjectUpdatePosted;
use App\Listeners\SendAppointmentNotifications;
use App\Listeners\SendDocumentUploadedNotifications;
use App\Listeners\SendGroupInvitation;
use App\Listeners\SendProjectUpdateNotifications;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // In production, force all generated URLs (routes, assets, storage) to
        // use the exact APP_URL so links in emails and pages are always correct.
        if (app()->environment('production')) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        // Event → Listener bindings
        Event::listen(AppointmentBooked::class, SendAppointmentNotifications::class);
        Event::listen(ProjectUpdatePosted::class, SendProjectUpdateNotifications::class);
        Event::listen(DocumentUploaded::class, SendDocumentUploadedNotifications::class);
        Event::listen(GroupInvitationSent::class, SendGroupInvitation::class);

        // Inject unread notifications into buyer portal layout
        View::composer('components.portal-layout', function ($view) {
            if (auth()->check() && !auth()->user()->is_admin) {
                $unread = auth()->user()->unreadNotifications;
                $view->with('_unreadCount', $unread->count());
                $view->with('_recentNotifications', $unread->take(5));
            } else {
                $view->with('_unreadCount', 0);
                $view->with('_recentNotifications', collect());
            }
        });
    }
}
