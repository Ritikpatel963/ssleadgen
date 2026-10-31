<?php

namespace App\Providers;

use App\Models\Insurer;
use App\Models\LookupValue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event): void {
            $event->extendSocialite('azure', \SocialiteProviders\Azure\Provider::class);
        });

        View::composer('*', function ($view): void {
            $insuranceCompanies = collect();
            $categoryMasterOptions = collect();

            if (Schema::hasTable('lookup_values')) {
                $categoryMasterOptions = LookupValue::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get()
                    ->groupBy('group')
                    ->map(fn ($values) => $values->pluck('name')->values());

                $insuranceCompanies = LookupValue::query()
                    ->where('group', 'insurance_company')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();
            } elseif (Schema::hasTable('insurers')) {
                $insuranceCompanies = Insurer::where('is_active', true)->orderBy('name')->get();
            }

            $view->with('insuranceCompanies', $insuranceCompanies);
            $view->with('categoryMasterOptions', $categoryMasterOptions);
        });
    }
}
