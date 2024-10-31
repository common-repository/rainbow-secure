=== Rainbow Secure – Advanced MFA & SSO Plugin ===
Contributors: rsecurewp
Tags: SSO, SAML, MFA, login, OTP, Security
Requires at least: 5.0
Tested up to: 6.6
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Boost your WordPress site’s security with advanced multi-layer MFA and seamless SSO integration.

== Description ==

Rainbow Secure’s MFA and SSO Plugin provides enterprise-level protection with customizable authentication workflows to secure your website and user accounts from credential theft, brute-force attacks, phishing, and more. Empower your users with secure and seamless logins, while protecting your site from cyber threats.

**Key Features:**

1. **Multi-Factor Authentication (MFA)**  
   Safeguard your WordPress site with various MFA options, including:  
   * Formatted Passwords: Customize passwords with additional layers like color and style.  
   * OTP Challenges: Receive OTPs via Email, SMS, or Mobile App for every login attempt.  
   * Adaptive Authentication: Security adjusted based on device, time of access, location, and behavior.  
   * Remember Device: Trusted devices can be whitelisted to reduce repeated MFA prompts.  
   * Location-based MFA: Define trusted work locations for seamless, secure access.  

2. **Single Sign-On (SSO)**  
   Simplify login for your users with SSO integration:  
   * Supports SAML, Integrate with 1000+ SaaS applications including Microsoft Office, Google Workspace, Salesforce, Zoom, Canva, Stripe, Cloud Providers including Azure AD, AWS, Google, IAM providers including Entra, Okta, Ping Identity.
   * User provisioning and de-provisioning for automatic WordPress account management.  
   * Role mapping and session management across multiple apps.  

3. **Customizable Security Policies**  
   Define security rules tailored to your organization’s needs:  
   * Set Conditional Access rules based on IP, role, location, or time.  
   * Restrict access to trusted devices or enforce custom password policies with Rainbow Secure features.  
   * Role-based access control ensures only authorized users reach key areas of your site.  

4. **Compliance and Reporting**  
   Achieve regulatory compliance and maintain control over user activities:  
   * GDPR & CCPA compliance: Tools to manage user data privacy with export and deletion capabilities.  
   * Audit logs: Track login attempts and SSO/MFA activity for accountability and monitoring.  
   * Secure data transmission with AES-256 encryption for credentials and tokens.  

5. **WooCommerce & BuddyPress Integration**  
   Protect your eCommerce transactions with WooCommerce support, and extend MFA and SSO security to BuddyPress for community sites.  

6. **Custom Branding & Login Flows**  
   Customize your login screens with your brand’s logos and colors, and create passwordless login options for a seamless, secure user experience.  

7. **Premium Support & Enterprise Features**  
   Enjoy 24/7 priority support and enterprise-grade features such as load balancing, high availability, and multi-factor backups for scaling your security.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/rainbow-secure` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to "Rainbow Secure" in the WordPress admin menu to configure the plugin settings.
4. Follow the instructions to integrate with Rainbow Secure's Identity Provider and set up MFA.

== Frequently Asked Questions ==

= How do I configure the plugin with Rainbow Secure's Identity Provider and MFA? =  
Navigate to the Rainbow Secure settings page in the WordPress admin dashboard. Follow the setup instructions to configure your Identity Provider (IDP) details, such as entity ID, SSO URL, and X.509 certificate, along with configuring MFA methods.

= Can I map custom attributes from my IDP to WordPress user fields? =  
Yes, Rainbow Secure allows you to map custom attributes from your IDP to WordPress user fields. You can configure these mappings in the Attribute Mapping section of the plugin settings.

= Does the plugin support Single Logout (SLO)? =  
Yes, Rainbow Secure supports Single Logout (SLO). You can enable this feature in the plugin settings.

== Screenshots ==

1. Plugin settings page
2. Request Activation Key Page
3. User login screen with SSO and MFA enabled

== Changelog ==

= 1.0.0 =  
* Initial release of Rainbow Secure  
* Multi-Factor Authentication (MFA) options including OTP and Adaptive Authentication  
* Single Sign-On (SSO) functionality with SAML  
* User auto-provisioning and attribute mapping  
* Role mapping, WooCommerce, and BuddyPress support  
* Customizable security policies and login flows  
* Compliance tools and audit logs for GDPR & CCPA  

== Upgrade Notice ==

= 1.0.0 =  
Initial release with MFA and SSO support. Configure MFA options and security policies for enhanced protection.

== License ==

This plugin is licensed under the GPLv2 or later. For more information, see the [GNU General Public License](https://www.gnu.org/licenses/gpl-2.0.html).
