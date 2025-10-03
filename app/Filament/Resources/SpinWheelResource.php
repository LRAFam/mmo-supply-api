<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SpinWheelResource\Pages;
use App\Filament\Resources\SpinWheelResource\RelationManagers;
use App\Models\SpinWheel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SpinWheelResource extends Resource
{
    protected static ?string $model = SpinWheel::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Gamification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'free' => 'Free',
                        'premium' => 'Premium',
                        'paid' => 'Paid',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('cost')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0.00),
                Forms\Components\TextInput::make('cooldown_hours')
                    ->numeric()
                    ->suffix('hours'),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'free' => 'success',
                        'premium' => 'warning',
                        'paid' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('cost')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cooldown_hours')
                    ->numeric()
                    ->suffix(' hrs')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'free' => 'Free',
                        'premium' => 'Premium',
                        'paid' => 'Paid',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListSpinWheels::route('/'),
            'create' => Pages\CreateSpinWheel::route('/create'),
            'edit' => Pages\EditSpinWheel::route('/{record}/edit'),
        ];
    }
}
