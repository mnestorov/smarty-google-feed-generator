# Changelog

### 1.0.0 (2024.02.13)
- Initial release

### 1.0.1 (2025.01.16)
- Added missing doc blocks for functions
- Updated README.md

### 1.0.2 (2025.01.16)
- Code refactoring
- Removing of unused functions
- Added additional functionality for the plugin settings page
- Bug fixes

### 1.0.3 (2025.01.21)
- Bug fixes
- Added a product feed generation functionality compatible with Bing Shopping

### 1.0.4 (2025.01.30)
- Added [HPOS (High-Performance Order Storage)](https://woocommerce.com/document/high-performance-order-storage/) compatibility declaration. The HPOS replaces the old post-based order system with custom database tables.

### 1.0.5 (2025.02.12)
- Added `post_type` attribute for Google and Bing product feeds
- Added `shipping` attribute for the Bing product feeds based on WooCommerce shipping zones
    - Extracted shipping costs dynamically for **Germany (DE) and Austria (AT)**
    - Included multiple shipping methods per country (e.g., **Standard and Free Shipping**)
    - Ensured shipping prices match WooCommerce rates
    - Fixed formatting issues with XML output for better compatibility with Bing
    - Improved caching logic to refresh feed when WooCommerce settings are updated

### 1.0.6 (2025.02.13)
- Added `shipping` attribute for the Google product feeds based on WooCommerce shipping zones
- Bug fix on `shipping` attribute display for the Bing product feeds

### 1.0.7 (2025.03.25)
- Optimized `smarty_gfg_invalidate_feed_cache` to prevent excessive feed regeneration during bulk product updates. Now invalidates cache without immediate regeneration and schedules a single feed update after 60 seconds, reducing server and Redis load.
- Added checks for WP-Cron and AJAX to skip cache invalidation during mass operations, improving stability.