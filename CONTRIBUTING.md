# Contributing to `laravel-attendance-hub`

Thank you for considering contributing to `laravel-attendance-hub`! We welcome contributions from everyone.

## Development Setup

1. Fork and clone the repository:
   ```bash
   git clone https://github.com/imrandevbd/laravel-attendance-hub.git
   cd laravel-attendance-hub
   ```

2. Install Composer dependencies:
   ```bash
   composer install
   ```

3. Run the test suite:
   ```bash
   composer test
   # or: vendor/bin/phpunit
   ```

## Coding Standards

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards.
- Add unit or feature tests for any new functionality or bug fixes.
- Ensure all tests pass before submitting a pull request.

## Adding a New Device Driver

To add support for a new hardware brand:
1. Create your driver class in `src/Drivers/` implementing `ImranDevBd\AttendanceHub\Contracts\DeviceDriverInterface` (or extending `AbstractDeviceDriver`).
2. Register the provider name in `AttendanceHubManager::provider()`.
3. Add corresponding test coverage in `tests/Unit/` or `tests/Feature/`.
4. Update the hardware support table in `README.md`.

## Pull Request Guidelines

- Describe what your change accomplishes and why it is needed.
- Reference any related issues.
- Keep pull requests focused on a single feature or bug fix.
