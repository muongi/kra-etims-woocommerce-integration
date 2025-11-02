# Contributing to KRA eTims WooCommerce Integration

Thank you for your interest in contributing to the KRA eTims WooCommerce Integration plugin! This document provides guidelines and information for contributors.

## 🤝 How to Contribute

We welcome contributions from the community! Here are the main ways you can contribute:

### 🐛 Reporting Bugs
- Use the GitHub issue tracker
- Provide detailed bug reports with steps to reproduce
- Include system information and error logs
- Check existing issues before creating new ones

### 💡 Suggesting Features
- Use the GitHub issue tracker with the "enhancement" label
- Describe the feature and its benefits
- Provide use cases and examples
- Consider implementation complexity

### 🔧 Code Contributions
- Fork the repository
- Create a feature branch
- Make your changes
- Test thoroughly
- Submit a pull request

## 🚀 Development Setup

### Prerequisites
- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- Git
- Local development environment (XAMPP, MAMP, etc.)

### Local Development Setup

1. **Clone the Repository**
   ```bash
   git clone https://github.com/your-username/kra-etims-connector.git
   cd kra-etims-woocommerce
   ```

2. **Set Up WordPress Environment**
   - Install WordPress locally
   - Install and activate WooCommerce
   - Copy plugin files to `/wp-content/plugins/`

3. **Configure Development Environment**
   ```php
   // Add to wp-config.php for debugging
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

4. **Install Dependencies**
   - No external dependencies required
   - Uses WordPress and WooCommerce built-in functions

## 📝 Coding Standards

### PHP Standards
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use PSR-4 autoloading standards
- Maintain backward compatibility
- Add proper documentation

### Code Style
```php
/**
 * Example function with proper documentation
 *
 * @param string $param1 Description of parameter
 * @param int    $param2 Description of parameter
 * @return array|false Array on success, false on failure
 */
function example_function( $param1, $param2 ) {
    // Use WordPress naming conventions
    $result = array();
    
    // Add proper error handling
    if ( empty( $param1 ) ) {
        return false;
    }
    
    // Use WordPress functions when available
    $sanitized_param = sanitize_text_field( $param1 );
    
    return $result;
}
```

### File Organization
```
kra-etims-woocommerce/
├── includes/
│   ├── class-kra-etims-wc.php              # Main plugin class
│   ├── class-kra-etims-wc-api.php          # API handling
│   ├── class-kra-etims-wc-category-handler.php  # Category management
│   ├── class-kra-etims-wc-product-handler.php   # Product management
│   ├── class-kra-etims-wc-order-handler.php     # Order processing
│   ├── class-kra-etims-wc-sync.php              # Sync interface
│   ├── class-kra-etims-wc-receipt-display.php   # Receipt display
│   ├── class-kra-etims-wc-qr-code.php           # QR code generation
│   └── admin/
│       └── class-kra-etims-wc-admin.php         # Admin interface
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── kra-etims-woocommerce.php               # Main plugin file
├── README.md
├── INSTALLATION.md
├── API_DOCUMENTATION.md
├── CHANGELOG.md
├── CONTRIBUTING.md
└── LICENSE
```

## 🧪 Testing Guidelines

### Manual Testing
- Test all features in a clean WordPress installation
- Test with different WooCommerce configurations
- Test API integration with mock endpoints
- Test error handling and edge cases

### Test Cases
1. **Category Management**
   - Create categories with different item codes
   - Test API synchronization
   - Verify SID retrieval

2. **Product Management**
   - Create products with categories
   - Test inheritance of category data
   - Verify validation rules

3. **Order Processing**
   - Create test orders
   - Verify receipt generation
   - Test TIN validation

4. **API Integration**
   - Test all API endpoints
   - Verify error handling
   - Test SSL/TLS compatibility

### Testing Checklist
- [ ] Plugin activates without errors
- [ ] All admin pages load correctly
- [ ] Category management works
- [ ] Product management works
- [ ] Order processing works
- [ ] API integration works
- [ ] Error handling works
- [ ] No PHP errors or warnings
- [ ] No JavaScript errors
- [ ] Responsive design works

## 🔄 Pull Request Process

### Before Submitting
1. **Test Your Changes**
   - Test in a clean WordPress environment
   - Test all affected functionality
   - Check for PHP errors and warnings

2. **Code Review**
   - Review your own code
   - Ensure coding standards are followed
   - Add proper documentation

3. **Update Documentation**
   - Update README if needed
   - Update API documentation if needed
   - Update changelog if needed

### Pull Request Guidelines
1. **Create Descriptive Title**
   - Use clear, descriptive titles
   - Reference issue numbers if applicable

2. **Provide Detailed Description**
   - Explain what the PR does
   - List any breaking changes
   - Include testing instructions

3. **Include Screenshots**
   - For UI changes, include screenshots
   - Show before and after if applicable

4. **Reference Issues**
   - Link to related issues
   - Use keywords like "Fixes #123" or "Closes #456"

### Example Pull Request
```markdown
## Description
This PR adds support for bulk category synchronization and improves error handling.

## Changes
- Added bulk sync functionality for categories
- Improved error messages for API failures
- Added retry mechanism for failed requests
- Updated documentation

## Testing
- Tested bulk sync with 50+ categories
- Verified error handling with invalid API responses
- Tested retry mechanism with network failures

## Screenshots
[Include screenshots of new features]

Fixes #123
```

## 🐛 Bug Reports

### Bug Report Template
```markdown
## Bug Description
Brief description of the bug

## Steps to Reproduce
1. Go to '...'
2. Click on '...'
3. Scroll down to '...'
4. See error

## Expected Behavior
What you expected to happen

## Actual Behavior
What actually happened

## Environment
- WordPress Version: X.X.X
- WooCommerce Version: X.X.X
- PHP Version: X.X.X
- Plugin Version: X.X.X
- Browser: Chrome/Firefox/Safari

## Error Logs
[Include relevant error logs]

## Screenshots
[Include screenshots if applicable]
```

## 💡 Feature Requests

### Feature Request Template
```markdown
## Feature Description
Brief description of the feature

## Use Case
Why this feature is needed

## Proposed Implementation
How you think it should be implemented

## Benefits
What benefits this feature would provide

## Alternatives Considered
Any alternative solutions you've considered
```

## 📚 Documentation

### Code Documentation
- Add PHPDoc comments for all functions
- Document parameters and return values
- Include usage examples for complex functions

### User Documentation
- Update README for new features
- Add installation instructions if needed
- Include troubleshooting steps

### API Documentation
- Document new API endpoints
- Include request/response examples
- Update error codes and messages

## 🔒 Security

### Security Guidelines
- Never commit sensitive data (API keys, passwords)
- Validate and sanitize all user input
- Use WordPress nonces for forms
- Check user capabilities before actions
- Escape output properly

### Security Checklist
- [ ] All user input is validated
- [ ] All output is escaped
- [ ] Nonces are used for forms
- [ ] Capabilities are checked
- [ ] No sensitive data in code
- [ ] SQL queries are prepared
- [ ] File uploads are validated

## 📞 Getting Help

### Before Asking for Help
1. Check existing documentation
2. Search existing issues
3. Test in a clean environment
4. Check WordPress error logs

### Where to Get Help
- GitHub Issues: For bugs and feature requests
- GitHub Discussions: For general questions
- WordPress.org Forums: For WordPress-specific issues
- WooCommerce Support: For WooCommerce-specific issues
- **Rhenium Group Limited**: For KRA eTims API integration support
  - Phone: +254721638836
  - Email: info@rheniumgroup.co.ke

## 🎯 Contribution Areas

### High Priority
- Bug fixes
- Security improvements
- Performance optimizations
- Documentation improvements

### Medium Priority
- New features
- UI/UX improvements
- Code refactoring
- Testing improvements

### Low Priority
- Cosmetic changes
- Minor optimizations
- Additional documentation

## 📋 Code of Conduct

### Our Standards
- Be respectful and inclusive
- Use welcoming and inclusive language
- Be collaborative and constructive
- Focus on what is best for the community

### Unacceptable Behavior
- Harassment or discrimination
- Trolling or insulting comments
- Publishing others' private information
- Other conduct inappropriate for a professional environment

## 📄 License

By contributing to this project, you agree that your contributions will be licensed under the same license as the project (GPL v2 or later).

## 👨‍💻 Developer Information

**Rhenium Group Limited**
- **Phone**: +254721638836
- **Email**: info@rheniumgroup.co.ke
- **Website**: [Rhenium Group Limited](https://rheniumgroup.co.ke)

### KRA eTims API Integration Services
Rhenium Group Limited provides complete KRA eTims API integration services including:
- API setup and configuration
- Custom endpoint development
- Connection testing and troubleshooting
- Ongoing technical support

---

Thank you for contributing to the KRA eTims WooCommerce Integration plugin! Your contributions help make this plugin better for everyone. For API integration services, contact Rhenium Group Limited. 