# Changelog

## [1.0.2] - 2026-04-18

### Changed
- Updated PHPUnit version constraints to exclude releases affected by published security advisories.
- Raised minimum supported PHPUnit patch versions for safer dependency resolution.

### Security
- Prevented installation of PHPUnit versions flagged by Packagist security advisories.
- Improved Composer compatibility with secure PHPUnit release lines.

## [1.0.1] - 2026-04-12

### Added
- added logo and badges and minor corrections in docs

## [1.0.0] - 2026-04-06

### Added
- Initial Laravel integration for Scenario Core.
- Integration into Laravel's console, providing installer and commands for managing, executing, and debugging scenarios.
- Filesystem and process integration.

### Stability
- Cross-platform support (Linux, Windows).

### Notes
- First stable release.
- Requires Scenario Core ^1.0.