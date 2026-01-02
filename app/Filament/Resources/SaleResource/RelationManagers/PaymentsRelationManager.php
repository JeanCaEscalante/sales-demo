<?php

namespace App\Filament\Resources\SaleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments'; // Define the relationship name on Sale model

    protected static ?string $title = 'Historial de Pagos';

    protected static ?string $recordTitleAttribute = 'payment_date';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->label('Monto')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->maxValue(fn () => $this->getOwnerRecord()->balance),
                
                Forms\Components\Select::make('payment_method')
                    ->label('Método de pago')
                    ->options([
                        'cash' => 'Efectivo',
                        'card' => 'Tarjeta',
                        'transfer' => 'Transferencia',
                        'check' => 'Cheque',
                    ])
                    ->required(),
                
                Forms\Components\DatePicker::make('payment_date')
                    ->label('Fecha de pago')
                    ->required()
                    ->default(now()),
                
                Forms\Components\Textarea::make('reference')
                    ->label('Referencia/Notas')
                    ->rows(3)
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
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Efectivo',
                        'card' => 'Tarjeta',
                        'transfer' => 'Transferencia',
                        'check' => 'Cheque',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'card' => 'info',
                        'transfer' => 'warning',
                        'check' => 'gray',
                        default => 'gray',
                    }),
                
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
                    ->options([
                        'cash' => 'Efectivo',
                        'card' => 'Tarjeta',
                        'transfer' => 'Transferencia',
                        'check' => 'Cheque',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Registrar pago')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = Auth::id();
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
}
