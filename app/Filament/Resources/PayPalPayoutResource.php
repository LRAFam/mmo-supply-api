<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayPalPayoutResource\Pages;
use App\Models\PayPalPayout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayPalPayoutResource extends Resource
{
    protected static ?string $model = PayPalPayout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'PayPal Payouts';

    protected static ?string $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payout Information')
                    ->schema([
                        Forms\Components\TextInput::make('user.username')
                            ->label('User')
                            ->disabled(),
                        Forms\Components\TextInput::make('paypal_email')
                            ->label('PayPal Email')
                            ->email()
                            ->disabled(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->prefix('$')
                            ->disabled(),
                        Forms\Components\TextInput::make('fee')
                            ->label('Fee')
                            ->prefix('$')
                            ->disabled(),
                        Forms\Components\TextInput::make('net_amount')
                            ->label('Net Amount')
                            ->prefix('$')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'pending_review' => 'Pending Review',
                                'success' => 'Success',
                                'failed' => 'Failed',
                            ])
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('PayPal Details')
                    ->schema([
                        Forms\Components\TextInput::make('payout_batch_id')
                            ->label('Payout Batch ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('sender_batch_id')
                            ->label('Sender Batch ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('payout_item_id')
                            ->label('Payout Item ID')
                            ->disabled(),
                    ])->columns(1),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('error_message')
                            ->label('Error Message')
                            ->disabled()
                            ->rows(3),
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.username')
                    ->label('User')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('paypal_email')
                    ->label('PayPal Email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Net Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'pending_review',
                        'success' => 'success',
                        'danger' => 'failed',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'pending_review' => 'Pending Review',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ]),
                Filter::make('pending_review')
                    ->label('Requires Review')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'pending_review'))
                    ->toggle(),
                Filter::make('large_amounts')
                    ->label('Large Amounts (>$500)')
                    ->query(fn (Builder $query): Builder => $query->where('amount', '>', 500))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (PayPalPayout $record): bool => $record->status === 'pending_review')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Payout Request')
                    ->modalDescription(fn (PayPalPayout $record) => "Are you sure you want to approve this \${$record->amount} payout to {$record->paypal_email}?")
                    ->action(function (PayPalPayout $record) {
                        try {
                            $response = Http::post(config('app.url') . "/api/admin/payouts/{$record->id}/approve", [], [
                                'headers' => [
                                    'Accept' => 'application/json',
                                ],
                            ]);

                            if ($response->successful()) {
                                Notification::make()
                                    ->title('Payout Approved')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Approval Failed')
                                    ->body($response->json()['message'] ?? 'Unknown error')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PayPalPayout $record): bool => $record->status === 'pending_review')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3)
                            ->placeholder('Explain why this payout is being rejected...'),
                    ])
                    ->action(function (PayPalPayout $record, array $data) {
                        try {
                            $response = Http::post(config('app.url') . "/api/admin/payouts/{$record->id}/reject", [
                                'reason' => $data['reason'],
                            ], [
                                'headers' => [
                                    'Accept' => 'application/json',
                                ],
                            ]);

                            if ($response->successful()) {
                                Notification::make()
                                    ->title('Payout Rejected')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Rejection Failed')
                                    ->body($response->json()['message'] ?? 'Unknown error')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                // Removed bulk delete for safety
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayPalPayouts::route('/'),
            'view' => Pages\ViewPayPalPayout::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending_review')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
