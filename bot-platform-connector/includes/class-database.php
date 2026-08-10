<?php
declare(strict_types=1);

/**
 * کلاس مدیریت ایجاد جداول دیتابیس
 * 
 * این کلاس مسئول ایجاد و به‌روزرسانی جداول سفارشی پلاگین
 * در دیتابیس وردپرس می‌باشد.
 * 
 * @package Bot_Platform_Connector
 * @since 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * کلاس مدیریت دیتابیس
 */
class Bot_Platform_Connector_Database {

    /**
     * نام جدول نمونه‌های ربات
     */
    const TABLE_BOT_INSTANCES = 'bot_instances';

    /**
     * نام جدول دانلودهای ربات
     */
    const TABLE_BOT_DOWNLOADS = 'bot_downloads';

    /**
     * نسخه ساختار دیتابیس
     * برای مدیریت مهاجرت‌های آینده
     */
    const DB_VERSION = '1.0.0';

    /**
     * ایجاد جداول دیتابیس
     * 
     * این متد با استفاده از تابع dbDelta وردپرس، جداول مورد نیاز را ایجاد می‌کند.
     * اگر جدول وجود داشته باشد، فقط در صورت تغییر ساختار، به‌روزرسانی می‌شود.
     * 
     * @global wpdb $wpdb شیء دسترسی به دیتابیس وردپرس
     * @return void
     */
    public static function create_tables() {
        global $wpdb;
        
        // بررسی وجود تابع dbDelta
        if ( ! function_exists( 'dbDelta' ) ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        
        // دریافت charset و collation دیتابیس
        $charset_collate = $wpdb->get_charset_collate();
        
        // =====================
        // جدول نمونه‌های ربات (bot_instances)
        // =====================
        // این جدول اطلاعات نمونه‌های ربات فعال کاربران را ذخیره می‌کند
        // شامل: شناسه کاربر، سفارش، وضعیت، تاریخ انقضا و غیره
        
        $table_instances = $wpdb->prefix . self::TABLE_BOT_INSTANCES;
        
        $sql_instances = "CREATE TABLE {$table_instances} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            variation_id BIGINT(20) UNSIGNED NOT NULL,
            laravel_instance_id BIGINT(20) UNSIGNED NULL,
            bot_token TEXT NULL,
            bot_username VARCHAR(255) NULL,
            status ENUM('pending', 'running', 'stopped', 'expired') DEFAULT 'pending',
            expires_at DATETIME NULL,
            renewal_count INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY laravel_instance_id (laravel_instance_id),
            KEY status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        // اجرای دستور ایجاد جدول
        dbDelta( $sql_instances );
        
        // =====================
        // جدول دانلودهای ربات (bot_downloads)
        // =====================
        // این جدول اطلاعات دانلودهای محصولات دانلودی را ذخیره می‌کند
        // شامل: شناسه محصول لاراول، نسخه انتشار، لینک دانلود و زمان انقضای لینک
        
        $table_downloads = $wpdb->prefix . self::TABLE_BOT_DOWNLOADS;
        
        $sql_downloads = "CREATE TABLE {$table_downloads} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            variation_id BIGINT(20) UNSIGNED NOT NULL,
            laravel_product_id BIGINT(20) UNSIGNED NOT NULL,
            release_version VARCHAR(50) NULL,
            download_url TEXT NULL,
            url_expires_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY order_id (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        // اجرای دستور ایجاد جدول
        dbDelta( $sql_downloads );
        
        // ذخیره نسخه دیتابیس در options
        update_option( 'bot_platform_db_version', self::DB_VERSION );
    }

    /**
     * دریافت نام کامل جدول با پیشوند وردپرس
     * 
     * @param string $table_name نام جدول (بدون پیشوند)
     * @return string نام کامل جدول با پیشوند
     */
    public static function get_table_name( $table_name ) {
        global $wpdb;
        return $wpdb->prefix . $table_name;
    }

    /**
     * دریافت نام جدول نمونه‌های ربات
     * 
     * @return string نام کامل جدول
     */
    public static function get_instances_table_name() {
        return self::get_table_name( self::TABLE_BOT_INSTANCES );
    }

    /**
     * دریافت نام جدول دانلودهای ربات
     * 
     * @return string نام کامل جدول
     */
    public static function get_downloads_table_name() {
        return self::get_table_name( self::TABLE_BOT_DOWNLOADS );
    }

    /**
     * بررسی وجود جدول
     * 
     * @param string $table_name نام جدول
     * @return bool true اگر جدول وجود داشته باشد
     */
    public static function table_exists( $table_name ) {
        global $wpdb;
        
        $full_table_name = self::get_table_name( $table_name );
        $result = $wpdb->get_var( 
            $wpdb->prepare( 
                "SHOW TABLES LIKE %s", 
                $wpdb->esc_like( $full_table_name ) 
            ) 
        );
        
        return $result === $full_table_name;
    }

    /**
     * بررسی نیاز به به‌روزرسانی دیتابیس
     * 
     * این متد نسخه فعلی دیتابیس را با نسخه کد مقایسه می‌کند
     * و در صورت نیاز، عملیات مهاجرت را انجام می‌دهد.
     * 
     * @return void
     */
    public static function maybe_update_database() {
        $installed_version = get_option( 'bot_platform_db_version', '0.0.0' );
        
        // اگر نسخه نصب شده قدیمی‌تر است، به‌روزرسانی انجام شود
        if ( version_compare( $installed_version, self::DB_VERSION, '<' ) ) {
            self::create_tables();
            
            // اینجا می‌توانید کدهای مهاجرت نسخه‌های مختلف را اضافه کنید
            // مثال:
            // if ( version_compare( $installed_version, '1.0.1', '<' ) ) {
            //     self::migrate_to_version_1_0_1();
            // }
            
            // به‌روزرسانی نسخه دیتابیس
            update_option( 'bot_platform_db_version', self::DB_VERSION );
        }
    }

    /**
     * پاک‌سازی جداول هنگام حذف پلاگین (اختیاری)
     * 
     * توجه: این متد به صورت پیش‌فرض فراخوانی نمی‌شود
     * تا داده‌های کاربران حفظ شوند.
     * 
     * @return void
     */
    public static function drop_tables() {
        global $wpdb;
        
        $table_instances = self::get_instances_table_name();
        $table_downloads = self::get_downloads_table_name();
        
        // حذف جداول
        $wpdb->query( "DROP TABLE IF EXISTS {$table_instances}" );
        $wpdb->query( "DROP TABLE IF EXISTS {$table_downloads}" );
        
        // حذف گزینه نسخه دیتابیس
        delete_option( 'bot_platform_db_version' );
    }

    /**
     * افزودن نمونه ربات جدید
     * 
     * @param array $data داده‌های نمونه ربات
     * @return int|WP_Error شناسه رکورد وارد شده یا خطا
     */
    public static function insert_bot_instance( $data ) {
        global $wpdb;
        
        $table_name = self::get_instances_table_name();
        
        // داده‌های پیش‌فرض
        $defaults = array(
            'user_id'             => 0,
            'order_id'            => 0,
            'variation_id'        => 0,
            'laravel_instance_id' => null,
            'bot_token'           => null,
            'bot_username'        => null,
            'status'              => 'pending',
            'expires_at'          => null,
            'renewal_count'       => 0,
        );
        
        $data = wp_parse_args( $data, $defaults );
        
        // اعتبارسنجی داده‌ها
        if ( empty( $data['user_id'] ) || empty( $data['order_id'] ) || empty( $data['variation_id'] ) ) {
            return new WP_Error( 'invalid_data', __( 'داده‌های ورودی نامعتبر است.', 'bot-platform-connector' ) );
        }
        
        // وارد کردن به دیتابیس
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id'             => absint( $data['user_id'] ),
                'order_id'            => absint( $data['order_id'] ),
                'variation_id'        => absint( $data['variation_id'] ),
                'laravel_instance_id' => $data['laravel_instance_id'] ? absint( $data['laravel_instance_id'] ) : null,
                'bot_token'           => sanitize_text_field( $data['bot_token'] ),
                'bot_username'        => sanitize_text_field( $data['bot_username'] ),
                'status'              => sanitize_text_field( $data['status'] ),
                'expires_at'          => $data['expires_at'],
                'renewal_count'       => absint( $data['renewal_count'] ),
            ),
            array(
                '%d', // user_id
                '%d', // order_id
                '%d', // variation_id
                '%d', // laravel_instance_id
                '%s', // bot_token
                '%s', // bot_username
                '%s', // status
                '%s', // expires_at
                '%d', // renewal_count
            )
        );
        
        if ( $result === false ) {
            return new WP_Error( 'db_error', $wpdb->last_error );
        }
        
        return $wpdb->insert_id;
    }

    /**
     * افزودن دانلود ربات جدید
     * 
     * @param array $data داده‌های دانلود
     * @return int|WP_Error شناسه رکورد وارد شده یا خطا
     */
    public static function insert_bot_download( $data ) {
        global $wpdb;
        
        $table_name = self::get_downloads_table_name();
        
        // داده‌های پیش‌فرض
        $defaults = array(
            'user_id'            => 0,
            'order_id'           => 0,
            'variation_id'       => 0,
            'laravel_product_id' => 0,
            'release_version'    => null,
            'download_url'       => null,
            'url_expires_at'     => null,
        );
        
        $data = wp_parse_args( $data, $defaults );
        
        // اعتبارسنجی داده‌ها
        if ( empty( $data['user_id'] ) || empty( $data['order_id'] ) || 
             empty( $data['variation_id'] ) || empty( $data['laravel_product_id'] ) ) {
            return new WP_Error( 'invalid_data', __( 'داده‌های ورودی نامعتبر است.', 'bot-platform-connector' ) );
        }
        
        // وارد کردن به دیتابیس
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id'            => absint( $data['user_id'] ),
                'order_id'           => absint( $data['order_id'] ),
                'variation_id'       => absint( $data['variation_id'] ),
                'laravel_product_id' => absint( $data['laravel_product_id'] ),
                'release_version'    => sanitize_text_field( $data['release_version'] ),
                'download_url'       => esc_url_raw( $data['download_url'] ),
                'url_expires_at'     => $data['url_expires_at'],
            ),
            array(
                '%d', // user_id
                '%d', // order_id
                '%d', // variation_id
                '%d', // laravel_product_id
                '%s', // release_version
                '%s', // download_url
                '%s', // url_expires_at
            )
        );
        
        if ( $result === false ) {
            return new WP_Error( 'db_error', $wpdb->last_error );
        }
        
        return $wpdb->insert_id;
    }

    /**
     * به‌روزرسانی نمونه ربات
     * 
     * @param int   $instance_id شناسه نمونه
     * @param array $data داده‌های جدید
     * @return bool|int تعداد رکوردهای به‌روزرسانی شده یا false در صورت خطا
     */
    public static function update_bot_instance( $instance_id, $data ) {
        global $wpdb;
        
        $table_name = self::get_instances_table_name();
        
        $result = $wpdb->update(
            $table_name,
            $data,
            array( 'id' => absint( $instance_id ) ),
            null,
            array( '%d' )
        );
        
        return $result;
    }

    /**
     * دریافت نمونه ربات بر اساس شناسه
     * 
     * @param int $instance_id شناسه نمونه
     * @return object|null رکورد نمونه یا null
     */
    public static function get_bot_instance( $instance_id ) {
        global $wpdb;
        
        $table_name = self::get_instances_table_name();
        
        $result = $wpdb->get_row( 
            $wpdb->prepare( 
                "SELECT * FROM {$table_name} WHERE id = %d", 
                absint( $instance_id ) 
            ) 
        );
        
        return $result;
    }

    /**
     * دریافت نمونه‌های ربات یک کاربر
     * 
     * @param int    $user_id شناسه کاربر
     * @param string $status  وضعیت (اختیاری)
     * @return array آرایه‌ای از نمونه‌ها
     */
    public static function get_user_bot_instances( $user_id, $status = '' ) {
        global $wpdb;
        
        $table_name = self::get_instances_table_name();
        
        if ( ! empty( $status ) ) {
            $sql = $wpdb->prepare( 
                "SELECT * FROM {$table_name} WHERE user_id = %d AND status = %s ORDER BY created_at DESC",
                absint( $user_id ),
                sanitize_text_field( $status )
            );
        } else {
            $sql = $wpdb->prepare( 
                "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC",
                absint( $user_id )
            );
        }
        
        return $wpdb->get_results( $sql );
    }

    /**
     * دریافت دانلودهای ربات یک کاربر
     * 
     * @param int $user_id شناسه کاربر
     * @return array آرایه‌ای از دانلودها
     */
    public static function get_user_bot_downloads( $user_id ) {
        global $wpdb;
        
        $table_name = self::get_downloads_table_name();
        
        $sql = $wpdb->prepare( 
            "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC",
            absint( $user_id )
        );
        
        return $wpdb->get_results( $sql );
    }
}
