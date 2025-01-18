<?php

namespace App\Filament\Resources\ArticleResource\RelationManagers;

use App\Filament\Resources\DiscountResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DiscountsRelationManager extends RelationManager
{
    protected static string $relationship = 'discounts';

    protected static ?string $label = 'Descuento';

    protected static ?string $title = 'Descuentos';

    public function form(Form $form): Form
    {
        return DiscountResource::form($form);
    }

    public function table(Table $table): Table
    {
        return DiscountResource::table($table)->headerActions([
            Tables\Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {

                    $data['used'] = 0;
                    $data['is_active'] = true;
                    $data['user_id'] = Auth::id();

                    return $data;
                }),
        ]);
    }
}
