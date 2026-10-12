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
            Section::make(__('filament-recordkeeper::audit.sections.overview'))
                ->columns(3)
                ->schema([
                    TextEntry::make('event')
                        ->label(__('filament-recordkeeper::audit.columns.event'))
                        ->badge(),
                    TextEntry::make('auditable_type')
                        ->label(__('filament-recordkeeper::audit.columns.subject'))
                        ->formatStateUsing(fn ($state, $record) => AuditFormatter::subjectLabel($state, $record->auditable_id)),
                    TextEntry::make('user_id')
                        ->label(__('filament-recordkeeper::audit.columns.actor'))
                        ->formatStateUsing(fn ($state, $record) => AuditFormatter::actorLabel($state, $record->user_type)),
                    TextEntry::make('guard')
                        ->label(__('filament-recordkeeper::audit.columns.guard'))
                        ->badge(),
                    TextEntry::make('ip_address')
                        ->label(__('filament-recordkeeper::audit.columns.ip')),
                    TextEntry::make('created_at')
                        ->label(__('filament-recordkeeper::audit.columns.time'))
                        ->dateTime(),
                    TextEntry::make('batch_id')
                        ->label(__('filament-recordkeeper::audit.columns.batch')),
                ]),

            Section::make(__('filament-recordkeeper::audit.sections.changes'))
                ->schema([
                    KeyValueEntry::make('old_values')
                        ->label(__('filament-recordkeeper::audit.fields.before')),
                    KeyValueEntry::make('new_values')
                        ->label(__('filament-recordkeeper::audit.fields.after')),
                ]),

            Section::make(__('filament-recordkeeper::audit.sections.context'))
                ->schema([KeyValueEntry::make('context')])
                ->visible(fn (Audit $record) => ! empty($record->context)),

            Section::make(__('filament-recordkeeper::audit.sections.http_requests'))
                ->schema([
                    RepeatableEntry::make('httpRequests')
                        ->schema([
                            TextEntry::make('method')
                                ->label(__('filament-recordkeeper::audit.fields.method'))
                                ->badge(),
                            TextEntry::make('url')
                                ->label(__('filament-recordkeeper::audit.columns.url')),
                            TextEntry::make('status_code')
                                ->label(__('filament-recordkeeper::audit.fields.status_code'))
                                ->badge()
                                ->color(fn (?int $state): string => match (true) {
                                    $state === null => 'gray',
                                    $state < 300 => 'success',
                                    $state < 400 => 'warning',
                                    default => 'danger',
                                }),
                            TextEntry::make('duration_ms')
                                ->label(__('filament-recordkeeper::audit.fields.duration'))
                                ->suffix(' ms'),
                            TextEntry::make('failed')
                                ->label(__('filament-recordkeeper::audit.fields.failed'))
                                ->badge()
                                ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                                ->formatStateUsing(fn (bool $state): string => $state
                                    ? __('filament-recordkeeper::audit.status.failed')
                                    : __('filament-recordkeeper::audit.status.ok')),
                        ])
                        ->columns(5),
                ])
                ->visible(fn (Audit $record) => config('recordkeeper.http.enabled', false)
                    && $record->httpRequests()->exists()),
        ];
    }
}
