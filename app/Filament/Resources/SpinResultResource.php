<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SpinResultResource\Pages;
use App\Filament\Resources\SpinResultResource\RelationManagers;
use App\Models\SpinResult;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SpinResultResource extends Resource
{
    protected static ?string $model = SpinResult::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Gamification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required()
                    ->disabled(),
                Forms\Components\Select::make('spin_wheel_id')
                    ->relationship('spinWheel', 'name')
                    ->required()
                    ->disabled(),
                Forms\Components\Select::make('wheel_prize_id')
                    ->relationship('wheelPrize', 'name')
                    ->disabled(),
                Forms\Components\TextInput::make('prize_name')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),
                Forms\Components\TextInput::make('prize_type')
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('prize_value')
                    ->required()
                    ->numeric()
                    ->disabled(),
                Forms\Components\DateTimePicker::make('spun_at')
                    ->required()
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('spinWheel.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('prize_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('prize_type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('prize_value')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('spun_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('spin_wheel_id')
                    ->relationship('spinWheel', 'name'),
                Tables\Filters\SelectFilter::make('prize_type')
                    ->options([
                        'wallet_balance' => 'Wallet Balance',
                        'discount' => 'Discount',
                        'free_spin' => 'Free Spin',
                        'points' => 'Points',
                        'item' => 'Item',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('spun_at', 'desc');
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
            'index' => Pages\ListSpinResults::route('/'),
            'create' => Pages\CreateSpinResult::route('/create'),
            'edit' => Pages\EditSpinResult::route('/{record}/edit'),
        ];
    }
}
