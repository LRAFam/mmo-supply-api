<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WheelPrizeResource\Pages;
use App\Filament\Resources\WheelPrizeResource\RelationManagers;
use App\Models\WheelPrize;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WheelPrizeResource extends Resource
{
    protected static ?string $model = WheelPrize::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Gamification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('spin_wheel_id')
                    ->relationship('spinWheel', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'wallet_balance' => 'Wallet Balance',
                        'discount' => 'Discount',
                        'free_spin' => 'Free Spin',
                        'points' => 'Points',
                        'item' => 'Item',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('value')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('probability_weight')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\ColorPicker::make('color'),
                Forms\Components\TextInput::make('icon')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('spinWheel.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('probability_weight')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\ColorColumn::make('color')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('spin_wheel_id')
                    ->relationship('spinWheel', 'name'),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'wallet_balance' => 'Wallet Balance',
                        'discount' => 'Discount',
                        'free_spin' => 'Free Spin',
                        'points' => 'Points',
                        'item' => 'Item',
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
            'index' => Pages\ListWheelPrizes::route('/'),
            'create' => Pages\CreateWheelPrize::route('/create'),
            'edit' => Pages\EditWheelPrize::route('/{record}/edit'),
        ];
    }
}
