# Security Policy

## Reporting Security Vulnerabilities

If you discover a security vulnerability within `imrandevbd/laravel-attendance-hub`, please send an email to Imran Ahmed at **me@imrandev.bd**. All security vulnerabilities will be promptly addressed.

Please **do not** report security vulnerabilities via public GitHub issues.

## Biometric Data & GDPR Compliance

This package processes employee biometric data (fingerprint and face templates) and connection credentials.

### Built-in Security Protections:
- **Encryption at Rest**:
  - `device_biometric_templates.template_data` uses Laravel's `encrypted` cast, encrypting raw templates before database storage using your application's `APP_KEY`.
  - `attendance_devices.connection_settings` uses `encrypted:array`, ensuring hardware passwords, comm keys, and tokens are protected from database dump exposure.
- **HMAC Signatures**: Outgoing webhooks are signed using SHA-256 HMAC tokens.
- **Deduplication Hashing**: Punches are hashed deterministically (`SHA-256`) to prevent replay attacks and duplicate logs.

### Compliance Best Practices:
1. Ensure your database server and backups are encrypted at rest.
2. Comply with local biometric privacy laws (e.g., GDPR Special Category Data under Article 9 in the EU, BIPA in Illinois, and local data protection regulations).
3. Obtain explicit employee consent where required by your jurisdiction before storing biometric templates.
