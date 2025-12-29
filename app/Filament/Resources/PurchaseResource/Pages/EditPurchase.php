<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as &$item) {
                if ($item['tax_exempt'] ?? false) {
                    $item['tax_rate_id'] = null;
                    $item['tax_rate'] = null;
                    $item['tax_name'] = null;
                } elseif (!empty($item['tax_rate_id'])) {
                    $tax = \App\Models\TaxRate::find($item['tax_rate_id']);
                    if ($tax) {
                        $item['tax_rate'] = $tax->rate;
                        $item['tax_name'] = $tax->name;
                    }
                }
            }
        }

        return $data;
    }
}