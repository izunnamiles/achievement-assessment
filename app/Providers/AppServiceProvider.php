<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\Repositories\AchievementRepositoryInterface;
use App\Contracts\Repositories\BadgeRepositoryInterface;
use App\Contracts\Repositories\BankAccountRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\PurchaseRepositoryInterface;
use App\Contracts\Repositories\SystemSettingRepositoryInterface;
use App\Contracts\Repositories\UserAchievementRepositoryInterface;
use App\Contracts\Repositories\UserBadgeRepositoryInterface;
use App\Repositories\AchievementRepository;
use App\Repositories\BadgeRepository;
use App\Repositories\BankAccountRepository;
use App\Repositories\ProductRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\SystemSettingRepository;
use App\Repositories\UserAchievementRepository;
use App\Repositories\UserBadgeRepository;
use App\Services\Payments\PaystackService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PurchaseRepositoryInterface::class, PurchaseRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(AchievementRepositoryInterface::class, AchievementRepository::class);
        $this->app->bind(UserAchievementRepositoryInterface::class, UserAchievementRepository::class);
        $this->app->bind(BadgeRepositoryInterface::class, BadgeRepository::class);
        $this->app->bind(UserBadgeRepositoryInterface::class, UserBadgeRepository::class);
        $this->app->bind(SystemSettingRepositoryInterface::class, SystemSettingRepository::class);
        $this->app->bind(BankAccountRepositoryInterface::class, BankAccountRepository::class);
        $this->app->bind(PaymentGatewayInterface::class, PaystackService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
