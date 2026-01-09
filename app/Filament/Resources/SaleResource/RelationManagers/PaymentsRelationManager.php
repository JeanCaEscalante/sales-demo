<?php

namespace App\Filament\Resources\SaleResource\RelationManagers;

use App\Enums\TypePaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments'; // Define the relationship name on Sale model

    protected static ?string $title = 'Historial de Pagos';

    protected static ?string $recordTitleAttribute = 'payment_date';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('payment_method')
                    ->label('Método de pago')
                    ->options(TypePaymentMethod::class)
                    ->required(),

                Forms\Components\DatePicker::make('payment_date')
                    ->label('Fecha de pago')
                    ->required()
                    ->default(now()),

                Forms\Components\Select::make('currency_id')
                    ->label('Moneda')
                    ->options(fn () => \App\Models\Currency::query()->pluck('name', 'currency_id'))
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {

                        $currency = \App\Models\Currency::find($state)?->getCurrentRate();
                        $total_amount = $this->getOwnerRecord()->balance;
                        $exchanged_amount = $total_amount * $currency;
                        $set('exchange_rate', number_format($currency, 2));

                        $set('exchanged_amount', number_format($exchanged_amount, 2));
                    })
                    ->required()
                    ->visibleOn('create'),

                Forms\Components\TextInput::make('currency')
                    ->label('Moneda')
                    ->required()
                    ->visibleOn('edit'),

                Forms\Components\TextInput::make('exchange_rate')
                    ->label('Tasa de cambio')
                    ->required()
                    ->disabled(),

                Forms\Components\TextInput::make('amount')
                    ->label('Monto a pagar')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(fn () => $this->getOwnerRecord()->balance)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                        $exchange_rate = $get('exchange_rate');
                        $exchanged_amount = $state * $exchange_rate;
                        $set('exchanged_amount', number_format($exchanged_amount, 2));
                    })
                    ->maxValue(fn () => $this->getOwnerRecord()->balance),

                Forms\Components\TextInput::make('exchanged_amount')
                    ->label('Monto al tipo de cambio')
                    ->required(),

                Forms\Components\TextInput::make('reference')
                    ->label('Referencia')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_date')
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método')
                    ->badge(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Registrado por')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Referencia')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->reference)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Método de pago')
                    ->options(TypePaymentMethod::class),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Registrar pago')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = Auth::id();
                        $currency = \App\Models\Currency::find($data['currency_id']);
                        $data['currency'] = $currency?->symbol;
                        $data['exchange_rate'] = $currency?->getCurrentRate();

                        return $data;
                    })
                    ->after(function () {
                        $this->getOwnerRecord()->updatePaymentStatus();

                        Notification::make()
                            ->success()
                            ->title('Pago registrado')
                            ->body('El estado de la venta ha sido actualizado')
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->after(function () {
                        $this->getOwnerRecord()->updatePaymentStatus();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        $this->getOwnerRecord()->updatePaymentStatus();

                        Notification::make()
                            ->success()
                            ->title('Pago eliminado')
                            ->body('El estado de la venta ha sido actualizado')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            $this->getOwnerRecord()->updatePaymentStatus();
                        }),
                ]),
            ])
            ->emptyStateHeading('Sin pagos registrados')
            ->emptyStateDescription('Esta venta aún no tiene pagos registrados.')
            ->emptyStateIcon('heroicon-o-currency-dollar')
            ->defaultSort('payment_date', 'desc');
    }

    public function isReadOnly(): bool { return false; }
}
