<?php

namespace App\Providers;

use App\Services\Payments\MockGateway;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\RazorpayGateway;
use App\Support\Platform;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, function () {
            $keyId = (string) config('edubook.razorpay.key_id');
            $keySecret = (string) config('edubook.razorpay.key_secret');

            if ($keyId !== '' && $keySecret !== '') {
                return new RazorpayGateway(
                    $keyId,
                    $keySecret,
                    (string) config('edubook.razorpay.webhook_secret'),
                    (string) config('edubook.razorpay.mode'),
                );
            }

            return new MockGateway((string) config('app.key'));
        });
    }

    public function boot(): void
    {
        Model::unguard(false);

        date_default_timezone_set('Asia/Kolkata');

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Expose a few platform values to every Blade view.
        View::composer('*', function ($view) {
            $view->with([
                'platformName' => Platform::get('platform_name', 'EduBook'),
                'supportPhone' => Platform::get('support_phone'),
                'supportEmail' => Platform::get('support_email'),
            ]);
        });
    }
}
