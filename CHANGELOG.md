# Changelog

All notable changes to `filament-recordkeeper` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-08-28

### Added

- Filament 5 plugin with fluent configuration API
- Audit resource with paginated table, event tabs, 8 filters, and global search
- View page with before/after diff viewer, context metadata, and outbound HTTP requests
- Permission-gated rollback with dry-run preview modal
- Stats overview widget (6 cards: total, created, updated, deleted, routes, actors)
- Audit timeline widget (recent 20 entries)
- Command metrics widget (dual-axis chart with anomaly detection)
- Relation manager for embedding audit history on any resource
- `HasAuditHistory` trait for one-line integration
- `AuditFormatter` helper for consistent event colors, actor labels, and subject labels
- Publishable Blade views under `recordkeeper` namespace
