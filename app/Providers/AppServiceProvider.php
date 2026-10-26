<?php

namespace App\Providers;

use App\Models\User;
use App\Repositories\Accounting\PaymentMilestoneRepository;
use App\Repositories\Accounting\PaymentMilestoneRepositoryInterface;
use App\Repositories\Accounting\PaymentRepository;
use App\Repositories\Accounting\PaymentRepositoryInterface;
use App\Repositories\Budget\BudgetItemRepository;
use App\Repositories\Budget\BudgetItemRepositoryInterface;
use App\Repositories\Contracts\ContractRepository;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Delivery\DeliveryBatchRepository;
use App\Repositories\Delivery\DeliveryBatchRepositoryInterface;
use App\Repositories\Penalty\PenaltyRepository;
use App\Repositories\Penalty\PenaltyRepositoryInterface;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(ContractRepositoryInterface::class, ContractRepository::class);
        $this->app->bind(DeliveryBatchRepositoryInterface::class, DeliveryBatchRepository::class);
        $this->app->bind(PenaltyRepositoryInterface::class, PenaltyRepository::class);
        $this->app->bind(PaymentMilestoneRepositoryInterface::class, PaymentMilestoneRepository::class);
        $this->app->bind(BudgetItemRepositoryInterface::class, BudgetItemRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
    }

    public function boot(): void {
        JsonResource::withoutWrapping();

        Gate::define('viewApiDocs', function (?User $user = null): bool {
            return true;
        });
    }
}
