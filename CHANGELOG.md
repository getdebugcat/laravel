# Changelog

All notable changes to `debugcat/laravel` are documented here. This project
adheres to [Semantic Versioning](https://semver.org/).

## v0.2.0

### Changed
- **Breaking:** the ingest endpoint is now hardcoded to the DebugCat hosted
  collection (`https://debugcat.co/api/ingest`). The `DEBUGCAT_HOST` env var and
  the `host` config key have been removed — applications can no longer redirect
  reports elsewhere. Existing `DEBUGCAT_HOST` settings are silently ignored.

## v0.1.0

### Added
- Initial release: capture exceptions and ship them to a DebugCat project.
- Automatic reporting via Laravel's exception handler (no `bootstrap/app.php` edit).
- Backtrace with in-app frame detection and source snippets.
- Request, user, and environment/release context providers.
- Sensitive-field censoring before any data leaves the application.
- Optional queued delivery, `DebugCat` facade, and `debugcat:test` command.
