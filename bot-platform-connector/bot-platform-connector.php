<?php
declare(strict_types=1);

/**
 * Plugin Name: اتصال به پلتفرم ربات
 * Plugin URI: https://botplatform.example.com
 * Description: مدیریت ربات‌های تلگرام و فروش اشتراک
 * Version: 1.0.0
 * Author: Bot Platform Team
 * Author URI: https://botplatform.example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bot-platform-connector
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 8.2
 * WC tested up to: 9.0
 */

// جلوگیری از دسترسی مستقیم
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// تعریف ثابت‌های پلاگین
define( 'BOT_PLATFORM_CONNECTOR_VERSION', '1.0.0' );
define( 'BOT_PLATFORM_CONNECTOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BOT_PLATFORM_CONNECTOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BOT_PLATFORM_CONNECTOR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * بارگذاری فایل‌های کلاس
 */
require_once BOT_PLATFORM_CONNECTOR_PLUGIN_DIR . 'includes/class-database.php';
require_once BOT_PLATFORM_CONNECTOR_PLUGIN_DIR . 'includes/class-variation-fields.php';
require_once BOT_PLATFORM_CONNECTOR_PLUGIN_DIR . 'includes/class-admin-settings.php';

/**
 * بارگذاری دامنه ترجمه
 */
function bot_platform_connector_load_textdomain() {
    load_plugin_textdomain(
        'bot-platform-connector',
        false,
        dirname( BOT_PLATFORM_CONNECTOR_PLUGIN_BASENAME ) . '/languages'
    );
}
add_action( 'plugins_loaded', 'bot_platform_connector_load_textdomain' );

/**
 * هوک فعال‌سازی پلاگین - ایجاد جداول دیتابیس
 */
function bot_platform_connector_activate() {
    // بررسی پیش‌نیازها
    if ( ! class_exists( 'WooCommerce' ) ) {
        deactivate_plugins( BOT_PLATFORM_CONNECTOR_PLUGIN_BASENAME );
        wp_die(
            __( 'این پلاگین نیازمند نصب و فعال‌سازی ووکامرس است.', 'bot-platform-connector' ),
            __( 'خطای فعال‌سازی', 'bot-platform-connector' ),
            array( 'back_link' => true )
        );
    }

    // ایجاد جداول دیتابیس
    Bot_Platform_Connector_Database::create_tables();

    // پاک‌سازی کش rewrite rules
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'bot_platform_connector_activate' );

/**
 * هوک غیرفعال‌سازی پلاگین
 */
function bot_platform_connector_deactivate() {
    // پاک‌سازی rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'bot_platform_connector_deactivate' );

/**
 * هوک حذف پلاگین (اختیاری - برای پاک‌سازی داده‌ها)
 */
function bot_platform_connector_uninstall() {
    // اگر می‌خواهید داده‌ها هنگام حذف پلاگین پاک شوند، کد مربوطه را اینجا قرار دهید
    // فعلاً خالی است تا داده‌ها حفظ شوند
}
// register_uninstall_hook( __FILE__, 'bot_platform_connector_uninstall' );

/**
 * افزودن لینک تنظیمات به صفحه پلاگین‌ها
 */
function bot_platform_connector_add_settings_link( $links ) {
    $settings_link = '<a href="' . admin_url( 'admin.php?page=bot-platform-settings' ) . '">' .
        __( 'تنظیمات', 'bot-platform-connector' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . BOT_PLATFORM_CONNECTOR_PLUGIN_BASENAME, 'bot_platform_connector_add_settings_link' );

/**
 * اعلام سازگاری با ووکامرس HPOS (High-Performance Order Storage)
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});
