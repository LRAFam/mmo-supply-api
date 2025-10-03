<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserSeasonParticipationResource\Pages;
use App\Filament\Resources\UserSeasonParticipationResource\RelationManagers;
use App\Models\UserSeasonParticipation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserSeasonParticipationResource extends Resource
{
    protected static ?string $model = UserSeasonParticipation::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Gamification';

    protected static ?string $label = 'Season Participants';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('season_id')
                    ->relationship('season', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('rank')
                    ->numeric(),
                Forms\Components\TextInput::make('total_sales')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0.00),
                Forms\Components\TextInput::make('total_earned')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0.00),
                Forms\Components\TextInput::make('achievements_unlocked')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('participated')
                    ->required()
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('season.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('rank')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state === 1 => 'success',
                        $state <= 3 => 'warning',
                        $state <= 10 => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_sales')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_earned')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('achievements_unlocked')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('participated')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('season_id')
                    ->relationship('season', 'name'),
                Tables\Filters\TernaryFilter::make('participated')
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
            ])
            ->defaultSort('rank', 'asc');
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
            'index' => Pages\ListUserSeasonParticipations::route('/'),
            'create' => Pages\CreateUserSeasonParticipation::route('/create'),
            'edit' => Pages\EditUserSeasonParticipation::route('/{record}/edit'),
        ];
    }
}
