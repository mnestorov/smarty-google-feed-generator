# SM - Google Feed Generator for WooCommerce

[![Licence](https://img.shields.io/badge/LICENSE-GPL2.0+-blue)](./LICENSE)

- **Developed by:** Martin Nestorov 
    - Founder and Lead Developer at [Smarty Studio](https://smartystudio.net) | Explore more at [nestorov.dev](https://github.com/mnestorov)
- **Plugin URI:** https://github.com/mnestorov/smarty-google-feed-generator

## Overview

**SM - Google Feed Generator for WooCommerce** is a comprehensive WordPress plugin designed to dynamically generate and manage Google product and product review feeds for WooCommerce stores. This plugin automates the creation of feeds compliant with Google Merchant Center requirements, facilitating better product visibility and integration with Google Shopping.

## Features

- **Automatic Feed Generation:** Automatically generates Google product feeds and a product feed compatible with Bing Shopping, ensuring your WooCommerce store's products are readily available for Google and Bing Merchant Centers.
- **Google Product Review Feeds:** Generate feeds for product reviews to enhance product credibility and shopper confidence with user-generated content.
- **Custom Endpoints:** Provides easy access to feeds via dedicated URLs:
    - **Google Product Feed:** `/smarty-google-feed`
    - **Google Review Feed:** `/smarty-google-reviews-feed`
    - **CSV Export:** `/smarty-csv-export`
    - **Bing Product Feed:** `/smarty-bing-feed`
- **Real-time Updates:** Automatically updates feeds when products or reviews are added, modified, or removed.
- **Custom Labels:** Supports custom labels based on various product criteria, including:
    - High ratings
    - Most ordered products in a specified time frame
    - Products older or newer than specified durations
    - Category-based labels
    - Sale price identification
- **Feed Caching:** Improves performance with transients for feed caching, reducing server load during feed generation.
- **Scheduled Regeneration:** Uses WP-Cron to schedule feed updates, ensuring they are always up-to-date.
- **Dynamic Bundle Tag:** Automatically marks products with the "bundle" tag as bundled items in the feed.
- **Image Conversion:** Convert WebP images to PNG for better compatibility with Google Merchant Center.
- **Customizable Feeds:** Add attributes like `g:is_bundle`, `g:google_product_category`, `g:condition`, `g:brand`, and more.
- **Google Product Category Cleaning:** Automatically cleans the Google product category string, removing any leading numbers and hyphens for better compatibility.
- **Easy Activation and Deactivation:** Ensures a smooth setup and cleanup process through activation and deactivation hooks, managing rewrite rules and scheduled events accordingly.
- **Error Logging and Debugging:** Logs errors during feed generation for troubleshooting.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/smarty-google-feed-generator` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' menu in WordPress.

## Usage

### Access Feeds

After activating the plugin, it automatically generates product and review feeds accessible through custom endpoints:

- **Google Product Feed URL:** https://yourdomain.com/smarty-google-feed
- **Google Review Feed URL:** https://yourdomain.com/smarty-google-reviews-feed
- **CSV Feed Export:** https://yourdomain.com/smarty-csv-export
- **Bing Product Feed URL:** https://yourdomain.com/smarty-bing-feed

These URLs can be submitted to [Google Merchant Center](https://www.google.com/retail/solutions/merchant-center/) for product data and review integration.

### Settings

Configure the plugin via Settings > Google Feed Generator in the WordPress admin. Key options include:

- Selecting Google Product Categories with a dynamic Select2 dropdown.
- Defining cache durations and clearing options.
- Setting up custom labels for better categorization.
- Excluding specific patterns or categories from feeds.

### Manual Actions

Use the settings page to:

- Manually regenerate feeds.
- Convert WebP images to PNG for feed compatibility.

## Examples

### Custom Label Usage

Assign a label to high-rating products using custom label settings:

1. Go to `Settings > Google Feed Generator > Custom Labels`.
2. Set the "High Rating Value" field to "Top Rated".
3. Products with a rating of 4.0 or higher will now include the custom label `g:custom_label_2` with the value "Top Rated" in the feed.

### Excluding Categories

Exclude "Uncategorized" products:

1. Go to `Settings > Google Feed Generator > General`.
2. Select "Uncategorized" in the "Excluded Categories" field.
3. Products in the "Uncategorized" category will no **longer appear in feeds**.

### WebP to PNG Conversion

Convert product first WebP images to PNG:

1. Navigate to `Settings > Google Feed Generator > Convert Images`.
2. Click "Convert WebP to PNG".

## Hooks and Filters

The plugin hooks into various WooCommerce and WordPress actions to detect changes in products and reviews, ensuring feeds are always current. Customization options are available through WordPress filters and actions, allowing developers to extend functionalities as needed.

### Actions

- `smarty_gfg_generate_google_feed`: Triggered to generate the Google product feed.
- `smarty_gfg_generate_google_reviews_feed`: Triggered to generate the Google reviews feed.
- `smarty_gfg_generate_bing_feed`: Triggered to generate the Bing product feed.
- `smarty_gfg_invalidate_feed_cache`: Invalidates the feed cache on product changes.

### Filters

- `smarty_gfg_sanitize_checkbox`: Ensure valid checkbox values during settings save.

## Requirements

- WordPress 4.7+ or higher.
- WooCommerce 5.1.0 or higher.
- PHP 7.2+

## Changelog

For a detailed list of changes and updates made to this project, please refer to our [Changelog](./CHANGELOG.md).

## Contributing

Contributions are welcome. Please follow the WordPress coding standards and submit pull requests for any enhancements.

## Support The Project

If you find this script helpful and would like to support its development and maintenance, please consider the following options:

- **_Star the repository_**: If you're using this script from a GitHub repository, please give the project a star on GitHub. This helps others discover the project and shows your appreciation for the work done.

- **_Share your feedback_**: Your feedback, suggestions, and feature requests are invaluable to the project's growth. Please open issues on the GitHub repository or contact the author directly to provide your input.

- **_Contribute_**: You can contribute to the project by submitting pull requests with bug fixes, improvements, or new features. Make sure to follow the project's coding style and guidelines when making changes.

- **_Spread the word_**: Share the project with your friends, colleagues, and social media networks to help others benefit from the script as well.

- **_Donate_**: Show your appreciation with a small donation. Your support will help me maintain and enhance the script. Every little bit helps, and your donation will make a big difference in my ability to keep this project alive and thriving.

Your support is greatly appreciated and will help ensure all of the projects continued development and improvement. Thank you for being a part of the community!
You can send me money on Revolut by following this link: **https://revolut.me/mnestorovv**

---

## License

This project is released under the [GPL-2.0+ License](http://www.gnu.org/licenses/gpl-2.0.txt).
