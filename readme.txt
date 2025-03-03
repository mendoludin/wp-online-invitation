=== WP Online Invitation ===
Contributor: mendoludin
Tags: invitation, woocommerce, acf, cpt, elementor, rsvp
Minimum required: 5.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin to manage and customize online wedding invitations, integrated with WooCommerce, Custom Post Types (CPT) via CPT UI, Advanced Custom Fields (ACF), and Elementor Pro. This plugin allows users to personalize their invitation page after completing an order in WooCommerce, which can be accessed via the "My Account" page.

== Description ==
This plugin was developed to replace the complex functions previously managed in `functions.php`, offering a dedicated solution for creating and customizing online invitation websites. The main goal is to provide an automated system where clients can:
- Set up a custom web invitation page on the myaccount page after completing an order on the website.
- Manage and customize invitations through the "My Account" page, including order details, guest management, and more.

### Key Features:
1. **Show WooCommerce Order Details**: Display completed orders associated with online invitations.
2. **Order History**: View the history of previously ordered invitations.
3. **ACF Integration**: Customize invitation fields (e.g., bride and groom names, date, pre-wedding photos, date/time picker, link) using ACF dynamic tags in Elementor Pro.
4. **Guest Management**: Insert and display guest details (name, contact) on the invitation cover, with potential integration with other plugins for sharing.
5. **RSVP Feature**: Manage guest attendance confirmations (may require additional plugin integration). 6. **Guest Comments**: Allow guests to leave comments on the invitation page (may require additional plugin integration).

7. **Background Music**: Allows users to upload and change background audio for invitations.

8. **Automatic CPT Linking**: Automatically links WooCommerce orders to related CPTs based on the selected theme/design.

### CPT and Theme Logic:

- The plugin uses a CPT UI with slug `wedding_invitation` as the initial CPT, integrated with Elementor Pro Theme Builder for a single design theme.

- Future plans include generating unique CPT slugs for different invitation themes (e.g., `wedding_theme1`, `wedding_theme2`) to increase market appeal.

- Logic: When a user orders a product in WooCommerce, the plugin will:

1. Detect the product theme selected during the order.

2. Automatically generate a new CPT instance with the appropriate slug (e.g., `wedding_theme1`) after the order is completed.

3. Link the CPT to the user's "My Account" page for customization.

== Installation ==
1. Upload the `wp-online-invitation` folder to the `/wp-content/plugins/` directory.

2. Activate the plugin via the ‘Plugins’ menu in WordPress.

3. Make sure WooCommerce, CPT UI, ACF, and Elementor Pro are installed and activated.

4. Configure the plugin settings (if applicable) via the WordPress admin panel or “My Account” page.

== Frequently Asked Questions ==

= Can I add more themes? =
Yes, add new CPT slugs via CPT UI and update the plugin logic to map WooCommerce products to their respective CPTs.

== Screenshots ==
1. Custom invitation page with ACF field.
2. My Account tab showing order details.
3. RSVP and guest comments section.

== Changelog ==
= 1.0 =
* Initial release with basic invitation management and WooCommerce integration.

== Upgrade Notice ==
= 1.0 =
Early version. Test thoroughly before upgrading to future versions.
