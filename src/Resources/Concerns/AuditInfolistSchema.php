<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Resources\Concerns;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\RecordkeeperFilament\Support\AuditFormatter;

final class AuditInfolistSchema
{
    /**
     * @return list<Section>
     */
    public static function make(): array
    {
        return [
            Section::make('Overview')
                ->columns(3)
                ->schema([
                    TextEntry::make('event')->badge(),
                    TextEntry::make('auditable_type')
                        ->label('Subject')
                        ->formatStateUsing(fn ($state, $record) => AuditFormatter::subjectLabel($state, $record->auditable_id)),
                    TextEntry::make('user_id')
                        ->label('Actor')
                        ->formatStateUsing(fn ($state, $record) => AuditFormatter::actorLabel($state, $record->user_type)),
                    TextEntry::make('guard')->badge(),
                    TextEntry::make('ip_address')->label('IP'),
                    TextEntry::make('created_at')->label('Time')->dateTime(),
                    TextEntry::make('batch_id')->label('Batch'),
                ]),

            Section::make('Changes')
                ->schema([
                    KeyValueEntry::make('old_values')->label('Before'),
                    KeyValueEntry::make('new_values')->label('After'),
                ]),

            Section::make('Context')
                ->schema([KeyValueEntry::make('context')])
                ->visible(fn (Audit $record) => ! empty($record->context)),

            Section::make('Outbound HTTP Requests')
                ->schema([
                    RepeatableEntry::make('httpRequests')
                        ->schema([
                            TextEntry::make('method')->badge(),
                            TextEntry::make('url'),
                            TextEntry::make('status_code')
                                ->badge()
                                ->color(fn (?int $state): string => match (true) {
                                    $state === null => 'gray',
                                    $state < 300 => 'success',
                                    $state < 400 => 'warning',
                                    default => 'danger',
                                }),
                            TextEntry::make('duration_ms')->suffix(' ms'),
                            TextEntry::make('failed')
                                ->badge()
                                ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Failed' : 'OK'),
                        ])
                        ->columns(5),
                ])
                ->visible(fn (Audit $record) => config('recordkeeper.http.enabled', false)
                    && $record->httpRequests()->exists()),
        ];
    }
}
