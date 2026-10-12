<?php

return [
    'resource' => [
        'label' => 'Audit',
        'plural_label' => 'Audits',
        'navigation_group' => 'Audit',
    ],

    'columns' => [
        'time' => 'Time',
        'event' => 'Event',
        'subject' => 'Subject',
        'actor' => 'Actor',
        'guard' => 'Guard',
        'batch' => 'Batch',
        'ip' => 'IP',
        'url' => 'URL',
    ],

    'sections' => [
        'overview' => 'Overview',
        'changes' => 'Changes',
        'context' => 'Context',
        'http_requests' => 'Outbound HTTP Requests',
    ],

    'fields' => [
        'before' => 'Before',
        'after' => 'After',
        'method' => 'Method',
        'status_code' => 'Status Code',
        'duration' => 'Duration',
        'failed' => 'Failed',
    ],

    'tabs' => [
        'all' => 'All',
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'routes' => 'Routes',
    ],

    'actions' => [
        'revert' => 'Revert',
        'revert_this_change' => 'Revert this change',
        'prune_selected' => 'Prune selected',
    ],

    'widgets' => [
        'total_audits' => 'Total Audits',
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'route_hits' => 'Route Hits',
        'distinct_actors' => 'Distinct Actors',
        'total_actors' => 'Total Actors',
        'most_active' => 'Most Active',
        'active_today' => 'Active Today',
        'system_actions' => 'System Actions',
        'no_actor' => 'No actor attributed',
        'audits_count' => ':count audits',
        'recent_activity' => 'Recent Audit Activity',
        'command_performance' => 'Command Performance',
        'duration_ms' => 'Duration (ms)',
        'audit_impact' => 'Audit Impact',
    ],

    'rollback' => [
        'changes_reverted' => 'The following changes will be reverted:',
        'attribute' => 'Attribute',
        'current' => 'Current',
        'restoring_to' => 'Restoring to',
        'no_changes' => 'No attribute changes found.',
        'event' => 'Event',
    ],

    'actor' => [
        'system' => 'system',
    ],

    'relation' => [
        'title' => 'History',
    ],

    'filters' => [
        'model' => 'Model',
        'actor_type' => 'Actor type',
        'actor_id' => 'Actor ID',
        'from' => 'From',
        'until' => 'Until',
        'batch_id' => 'Batch ID',
        'only_rollbackable' => 'Only rollbackable',
    ],

    'status' => [
        'ok' => 'OK',
        'failed' => 'Failed',
    ],
];
