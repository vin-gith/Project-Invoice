<?php

namespace App\Providers;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Server bawaan PHP (php artisan serve) menandai akhir halaman dengan menutup koneksi, tanpa Content-Length.
        // Relay port forwarding VS Code (devtunnels) tidak meneruskan tanda itu, jadi browser menunggu terus:
        // halaman tampil tapi terus "loading", dan pratinjau PDF (fetch) kehabisan waktu. Content-Length menyelesaikannya.
        // File (logo) dan respons streaming dilewati: isinya bukan string, dan sudah membawa ukurannya sendiri.
        Event::listen(RequestHandled::class, function (RequestHandled $event) {
            $response = $event->response;
            $content = $response->getContent();

            if (is_string($content) && ! $event->request->isMethod('HEAD') && ! $response->headers->has('Content-Length')) {
                $response->headers->set('Content-Length', (string) strlen($content));
            }
        });
    }
}
