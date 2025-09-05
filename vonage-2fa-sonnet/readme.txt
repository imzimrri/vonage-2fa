=== Vonage 2FA Sonnet ===
Contributors: vonage-community
Tags: two-factor-authentication, 2fa, security, vonage, sms, verification
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: Apache 2.0
License URI: https://www.apache.org/licenses/LICENSE-2.0

Secure two-factor authentication for WordPress using Vonage's SMS Verify API.

== Description ==

Vonage 2FA Sonnet adds an extra layer of security to your WordPress site by requiring users to verify their identity with a code sent to their mobile phone via SMS.

**Key Features:**

* **Easy Setup**: Simple configuration with your Vonage API credentials
* **User Control**: Each user can enable/disable 2FA for their own account
* **Secure**: Uses Vonage's proven SMS delivery infrastructure
* **Reliable**: Built-in API testing and comprehensive error handling
* **Clean Code**: Modern, object-oriented architecture
* **No Conflicts**: Designed to work alongside other security plugins

**How It Works:**

1. User enters username and password normally
2. If 2FA is enabled, a verification code is sent to their phone
3. User enters the code to complete login
4. Access granted only after successful verification

**Requirements:**

* Active Vonage account with API credentials
* Sufficient credit in your Vonage account for SMS messages
* WordPress 5.0 or higher
* PHP 7.4 or higher

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/vonage-2fa-sonnet/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings → Vonage 2FA Sonnet to configure your API credentials
4. Users can enable 2FA in their profile settings

== Frequently Asked Questions ==

= Where do I get Vonage API credentials? =

Sign up for a Vonage account at [dashboard.nexmo.com](https://dashboard.nexmo.com/sign-up) and get your API Key and Secret from the dashboard.

= What phone number format should I use? =

Enter phone numbers as digits only with country code. For example: `16193278653` for a US number, `447700900123` for a UK number.

= Can I test if my API credentials work? =

Yes! Go to Settings → Vonage 2FA Sonnet and click "Test API Connection" to verify your credentials.

= What happens if I enter the wrong password? =

You'll get the normal "incorrect password" error. 2FA only activates after your password is verified as correct.

= What if I get "Concurrent verifications not allowed"? =

This means a verification code was already sent recently. You can either use the existing code or wait 2-3 minutes before requesting a new one.

== Screenshots ==

1. Plugin settings page with API configuration
2. User profile 2FA settings
3. Two-factor authentication verification form

== Changelog ==

= 1.0.0 =
* Initial release
* SMS-based two-factor authentication
* Built-in API testing
* User-friendly setup and configuration
* Comprehensive error handling
* Clean, modern code architecture

== Upgrade Notice ==

= 1.0.0 =
Initial release of Vonage 2FA Sonnet plugin.

== Support ==

For support and documentation, visit the plugin's GitHub repository or contact the Vonage Developer Relations team.

== Privacy Policy ==

This plugin sends phone numbers to Vonage's API for SMS delivery. Please ensure you comply with your local privacy laws and inform users about this data processing.