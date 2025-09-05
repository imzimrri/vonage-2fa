# Vonage 2FA Sonnet - WordPress Plugin

A clean, working implementation of Two-Factor Authentication using Vonage's Verify API for WordPress.

## Features

- ✅ Clean, modern code architecture
- ✅ Based on working Python implementation
- ✅ Comprehensive error handling and logging
- ✅ Built-in API testing functionality
- ✅ User-friendly setup and configuration
- ✅ Secure session management
- ✅ Phone number validation
- ✅ Custom 2FA login form

## Installation

1. Upload the `vonage-2fa-sonnet` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → Vonage 2FA Sonnet to configure your API credentials

## Configuration

### 1. Get Vonage API Credentials

1. Sign up for a Vonage account at [dashboard.nexmo.com](https://dashboard.nexmo.com/sign-up)
2. Get your API Key and API Secret from the dashboard
3. Make sure your account has sufficient credit for SMS messages

### 2. Configure the Plugin

1. Go to **Settings → Vonage 2FA Sonnet** in your WordPress admin
2. Enter your API Key and API Secret
3. Click "Test API Connection" to verify your credentials work

### 3. Enable 2FA for Users

1. Go to **Users → Your Profile** (or any user's profile)
2. Scroll down to the "Vonage Two-Factor Authentication" section
3. Enter your phone number (digits only, with country code, e.g., `16193278653`)
4. Check "Enable two-factor authentication for my account"
5. Save the profile

## How It Works

1. **Login Attempt**: User enters username/password normally
2. **2FA Check**: If 2FA is enabled for the user, the plugin intercepts the login
3. **Send Code**: A verification code is sent to the user's phone via Vonage API
4. **Verify Code**: User enters the code on a custom verification form
5. **Complete Login**: If code is correct, user is logged in normally

## API Endpoints Used

This plugin uses Vonage's Verify API v1 endpoints:

- **Send Code**: `POST https://api.vonage.com/verify/json`
- **Verify Code**: `POST https://api.vonage.com/verify/check/json`

## Phone Number Format

Phone numbers should be entered as digits only with country code:

- ✅ Correct: `16193278653` (US number)
- ✅ Correct: `447700900123` (UK number)
- ❌ Wrong: `+1-619-327-8653`
- ❌ Wrong: `(619) 327-8653`

## Debugging

The plugin logs detailed information to help with troubleshooting:

- All API calls and responses are logged
- Error messages include specific details
- Check your WordPress debug log for entries starting with "Vonage 2FA Sonnet:"

## Security Features

- ✅ Secure session management
- ✅ Request ID validation
- ✅ User ID verification
- ✅ Nonce protection for admin functions
- ✅ Capability checks for user management
- ✅ Input sanitization and validation

## Troubleshooting

### "Failed to send verification code"

- Check your API credentials are correct
- Ensure your Vonage account has sufficient credit
- Verify the phone number format is correct (digits only)

### "Invalid verification session"

- This happens if the session expires or is tampered with
- Try logging in again to get a fresh verification code

### API Test Fails

- Double-check your API Key and Secret
- Make sure your Vonage account is active
- Check if your server can make outbound HTTPS requests

## Differences from Original Plugin

This plugin fixes several issues in the original Vonage 2FA plugin:

1. **Correct API Format**: Uses proper POST data format instead of query parameters
2. **Better Error Handling**: Comprehensive error messages and logging
3. **Session Security**: Proper session validation and cleanup
4. **Phone Validation**: Improved phone number format validation
5. **Modern Code**: Clean, object-oriented architecture
6. **API Testing**: Built-in functionality to test API credentials

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Active Vonage account with API credentials
- Server with outbound HTTPS capability

## License

Apache License 2.0 - Same as the original Vonage plugin

## Support

This plugin is based on the working Python implementation and uses the same API calls that are proven to work. If you encounter issues:

1. Check the WordPress debug log for detailed error messages
2. Use the built-in API test function to verify your credentials
3. Ensure your phone number format is correct (digits only with country code)

## Credits

- Based on the original Vonage 2FA WordPress plugin
- Reimplemented using working Python code as reference
- Created to solve the SMS delivery issues in the original plugin
