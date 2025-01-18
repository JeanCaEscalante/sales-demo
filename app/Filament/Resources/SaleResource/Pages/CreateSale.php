<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Article;
use App\Services\InventoryService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['receipt_series'] = null;
        $data['num_receipt'] = null;
        $data['user_id'] = Auth::id();
        return $data;
    }

    protected function afterCreate(): void
    {
        $sale = $this->getRecord();

        $sale->details->each(function ($item) {
            $article = Article::find($item->article_id);
            $service = new InventoryService($article);
            $service->removeFromStock($item->quantity);
        });


    }
}
