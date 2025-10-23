# Security Policy

## Supported Versions

We release patches for security vulnerabilities in the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

We take the security of Lifegence AITranslator seriously. If you believe you have found a security vulnerability, please report it to us as described below.

### Please Do NOT:

- Open a public GitHub issue for security vulnerabilities
- Disclose the vulnerability publicly before it has been addressed
- Exploit the vulnerability for any purpose other than testing

### Please DO:

1. **Email us privately** at [security@lifegence.com](mailto:security@lifegence.com)
2. **Include the following information**:
   - Type of vulnerability
   - Full paths of source file(s) related to the vulnerability
   - Location of the affected source code (tag/branch/commit or direct URL)
   - Step-by-step instructions to reproduce the issue
   - Proof-of-concept or exploit code (if possible)
   - Impact of the issue, including how an attacker might exploit it

### What to Expect:

- **Acknowledgment**: We will acknowledge receipt of your report within 48 hours
- **Initial Assessment**: We will provide an initial assessment within 5 business days
- **Regular Updates**: We will keep you informed of our progress
- **Fix Timeline**: We aim to release a fix within 30 days for critical issues
- **Disclosure**: We will work with you on responsible disclosure timing
- **Credit**: We will credit you in our security advisory (unless you prefer to remain anonymous)

## Security Best Practices

### For Plugin Users

1. **Keep Updated**: Always use the latest version of the plugin
2. **Secure API Keys**: Never commit API keys to version control
3. **File Permissions**: Ensure proper file permissions on your server
4. **HTTPS**: Always use HTTPS for your WordPress site
5. **Regular Backups**: Maintain regular backups of your site
6. **Strong Passwords**: Use strong passwords for WordPress admin accounts

### For Developers

When contributing to this project, please follow these security guidelines:

#### Input Validation

```php
// Always sanitize user inputs
$text = sanitize_text_field( $_POST['text'] );
$lang = sanitize_text_field( $_POST['lang'] );
```

#### Output Escaping

```php
// Always escape outputs
echo esc_html( $translation );
echo esc_attr( $language_code );
echo esc_url( $api_endpoint );
```

#### Nonce Verification

```php
// Always verify nonces for forms and AJAX
if ( ! wp_verify_nonce( $_POST['nonce'], 'lg_aitrans_action' ) ) {
    wp_die( 'Security check failed' );
}
```

#### Capability Checks

```php
// Always check user capabilities
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Insufficient permissions' );
}
```

#### SQL Queries

```php
// Always use prepared statements
$wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}table WHERE id = %d",
    $id
);
```

## Known Security Considerations

### API Key Storage

- API keys are stored in WordPress options table
- Keys are not encrypted (uses WordPress standard practices)
- Keys are never exposed in frontend JavaScript
- Admin-only access to key management

### Translation Cache

- Cache uses WordPress transients or Redis
- No sensitive user data stored in cache
- Cache keys include content hash to prevent poisoning

### REST API Endpoints

- Public endpoints rate-limited
- Admin endpoints require authentication
- All inputs sanitized and validated

## Security Audit History

- **1.0.0 (2024-10-21)**: Initial release security review completed
  - WordPress Plugin Check passed
  - All inputs sanitized
  - All outputs escaped
  - Nonce verification implemented
  - Capability checks in place

## Responsible Disclosure

We follow responsible disclosure practices and work with security researchers to:

- Verify and respond to reports promptly
- Develop and test fixes
- Release security patches
- Coordinate public disclosure
- Credit researchers appropriately

## Contact

For security-related inquiries:
- **Email**: security@lifegence.com
- **PGP Key**: [Available upon request]

For general inquiries:
- **GitHub Issues**: For non-security bugs
- **GitHub Discussions**: For questions and ideas

---

Thank you for helping keep Lifegence AITranslator and its users safe!
