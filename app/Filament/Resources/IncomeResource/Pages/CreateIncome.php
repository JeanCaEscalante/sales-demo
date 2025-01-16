<?php

namespace App\Filament\Resources\IncomeResource\Pages;

use App\Filament\Resources\IncomeResource;
use App\Models\Article;
use App\Services\InventoryService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateIncome extends CreateRecord
{
    protected static string $resource = IncomeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $income = $this->getRecord();

        $income->details->each(function ($item) {
            $article = Article::find($item->article_id);
            $inventoryService = new InventoryService($article);
            $inventoryService->addToStock($item->quantity);
            $inventoryService->updateIncomePrice($item->purchase_price);
            $inventoryService->updateSalePrice($item->sale_price);
        });
    }
}
