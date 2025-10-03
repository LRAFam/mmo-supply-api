<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventParticipantResource\Pages;
use App\Filament\Resources\EventParticipantResource\RelationManagers;
use App\Models\EventParticipant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EventParticipantResource extends Resource
{
    protected static ?string $model = EventParticipant::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Gamification';

    protected static ?string $label = 'Event Participants';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('event_id')
                    ->relationship('event', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\DateTimePicker::make('joined_at')
                    ->required(),
                Forms\Components\TextInput::make('score')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('rank')
                    ->numeric(),
                Forms\Components\Select::make('status')
                    ->options([
                        'registered' => 'Registered',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'winner' => 'Winner',
                        'disqualified' => 'Disqualified',
                    ])
                    ->required(),
                Forms\Components\KeyValue::make('prize_data')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('prize_claimed')
                    ->required()
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('joined_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('score')
                    ->numeric()
                    ->sortable(),
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
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'registered' => 'info',
                        'active' => 'warning',
                        'completed' => 'gray',
                        'winner' => 'success',
                        'disqualified' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('prize_claimed')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_id')
                    ->relationship('event', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'registered' => 'Registered',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'winner' => 'Winner',
                        'disqualified' => 'Disqualified',
                    ]),
                Tables\Filters\TernaryFilter::make('prize_claimed')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('award_winner')
                    ->icon('heroicon-o-trophy')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status !== 'winner')
                    ->action(fn ($record) => $record->update(['status' => 'winner'])),
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
            'index' => Pages\ListEventParticipants::route('/'),
            'create' => Pages\CreateEventParticipant::route('/create'),
            'edit' => Pages\EditEventParticipant::route('/{record}/edit'),
        ];
    }
}
