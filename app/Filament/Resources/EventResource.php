<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Gamification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Select::make('type')
                    ->options([
                        'tournament' => 'Tournament',
                        'challenge' => 'Challenge',
                        'community' => 'Community',
                        'seasonal' => 'Seasonal',
                    ])
                    ->required(),
                Forms\Components\Select::make('game_id')
                    ->relationship('game', 'name')
                    ->searchable(),
                Forms\Components\FileUpload::make('banner_image')
                    ->image()
                    ->disk('s3')
                    ->directory('events/banners')
                    ->maxSize(4096),
                Forms\Components\DateTimePicker::make('starts_at')
                    ->required(),
                Forms\Components\DateTimePicker::make('ends_at')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'upcoming' => 'Upcoming',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('max_participants')
                    ->numeric(),
                Forms\Components\TextInput::make('winner_count')
                    ->required()
                    ->numeric()
                    ->default(3),
                Forms\Components\KeyValue::make('prizes')
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('rules')
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('requirements')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_featured')
                    ->required()
                    ->default(false),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('game.name')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'upcoming' => 'info',
                        'active' => 'success',
                        'completed' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('max_participants')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'tournament' => 'Tournament',
                        'challenge' => 'Challenge',
                        'community' => 'Community',
                        'seasonal' => 'Seasonal',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'upcoming' => 'Upcoming',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'active')
                    ->action(fn ($record) => $record->complete()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('starts_at', 'desc');
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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
