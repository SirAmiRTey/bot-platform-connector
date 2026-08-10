<?php
declare(strict_types=1);

/**
 * کلاس مدیریت صفحه تنظیمات پلاگین
 * 
 * این کلاس صفحه تنظیمات پلاگین را در پیشخوان وردپرس ایجاد می‌کند
 * و تمام تنظیمات مربوط به اتصال، تمدید، ایمیل و عمومی را مدیریت می‌نماید.
 * 
 * @package Bot_Platform_Connector
 * @since 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// بررسی وجود کلاس برای جلوگیری از تعریف مجدد
if ( class_exists( 'Bot_Platform_Connector_Admin_Settings' ) ) {
    return;
}

/**
 * کلاس مدیریت تنظیمات ادمین
 */
class Bot_Platform_Connector_Admin_Settings {

    /**
     * شناسه صفحه تنظیمات
     */
    private $page_slug = 'bot-platform-settings';

    /**
     * مقداردهی اولیه و ثبت هوک‌ها
     */
    public function __construct() {
        // افزودن منو به پیشخوان
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        
        // ثبت تنظیمات
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // اعتبارسنجی تنظیمات
        add_filter( 'pre_update_option_bot_platform_connection_settings', array( $this, 'validate_connection_settings' ), 10, 2 );
        add_filter( 'pre_update_option_bot_platform_renewal_settings', array( $this, 'validate_renewal_settings' ), 10, 2 );
        add_filter( 'pre_update_option_bot_platform_email_settings', array( $this, 'validate_email_settings' ), 10, 2 );
        add_filter( 'pre_update_option_bot_platform_general_settings', array( $this, 'validate_general_settings' ), 10, 2 );
    }

    /**
     * افزودن منوی تنظیمات به پیشخوان وردپرس
     */
    public function add_admin_menu() {
        // افزودن منوی اصلی "ربات پلتفرم"
        add_menu_page(
            __( 'تنظیمات ربات پلتفرم', 'bot-platform-connector' ),      // عنوان صفحه
            __( 'ربات پلتفرم', 'bot-platform-connector' ),              // عنوان منو
            'manage_options',                                           // سطح دسترسی
            $this->page_slug,                                           // اسلاگ منو
            array( $this, 'render_settings_page' ),                     // تابع نمایش
            'dashicons-robot',                                          // آیکون
            56                                                          // موقعیت
        );
        
        // افزودن زیرمنوی "تنظیمات"
        add_submenu_page(
            $this->page_slug,                                           // منوی والد
            __( 'تنظیمات ربات پلتفرم', 'bot-platform-connector' ),      // عنوان صفحه
            __( 'تنظیمات', 'bot-platform-connector' ),                  // عنوان منو
            'manage_options',                                           // سطح دسترسی
            $this->page_slug,                                           // اسلاگ منو
            array( $this, 'render_settings_page' )                      // تابع نمایش
        );
    }

    /**
     * ثبت تنظیمات پلاگین
     */
    public function register_settings() {
        // =====================
        // بخش تنظیمات اتصال
        // =====================
        register_setting(
            'bot_platform_connection_group',
            'bot_platform_connection_settings',
            array(
                'type'              => 'object',
                'sanitize_callback' => array( $this, 'sanitize_connection_settings' ),
                'default'           => array(
                    'api_base_url' => '',
                    'hmac_secret'  => '',
                ),
            )
        );
        
        add_settings_section(
            'bot_platform_connection_section',
            __( 'تنظیمات اتصال', 'bot-platform-connector' ),
            array( $this, 'render_connection_section_description' ),
            $this->page_slug
        );
        
        add_settings_field(
            'api_base_url',
            __( 'آدرس API بک‌اند', 'bot-platform-connector' ),
            array( $this, 'render_api_base_url_field' ),
            $this->page_slug,
            'bot_platform_connection_section'
        );
        
        add_settings_field(
            'hmac_secret',
            __( 'کلید امنیتی HMAC', 'bot-platform-connector' ),
            array( $this, 'render_hmac_secret_field' ),
            $this->page_slug,
            'bot_platform_connection_section'
        );
        
        // =====================
        // بخش تنظیمات تمدید
        // =====================
        register_setting(
            'bot_platform_renewal_group',
            'bot_platform_renewal_settings',
            array(
                'type'              => 'object',
                'sanitize_callback' => array( $this, 'sanitize_renewal_settings' ),
                'default'           => array(
                    'base_monthly_price' => 0,
                    'discount_3_months'  => 10,
                    'discount_6_months'  => 20,
                    'discount_12_months' => 30,
                ),
            )
        );
        
        add_settings_section(
            'bot_platform_renewal_section',
            __( 'تنظیمات تمدید', 'bot-platform-connector' ),
            array( $this, 'render_renewal_section_description' ),
            $this->page_slug
        );
        
        add_settings_field(
            'base_monthly_price',
            __( 'مبلغ پایه هر ماه (تومان)', 'bot-platform-connector' ),
            array( $this, 'render_base_monthly_price_field' ),
            $this->page_slug,
            'bot_platform_renewal_section'
        );
        
        add_settings_field(
            'discount_3_months',
            __( 'تخفیف ۳ ماهه (درصد)', 'bot-platform-connector' ),
            array( $this, 'render_discount_3_months_field' ),
            $this->page_slug,
            'bot_platform_renewal_section'
        );
        
        add_settings_field(
            'discount_6_months',
            __( 'تخفیف ۶ ماهه (درصد)', 'bot-platform-connector' ),
            array( $this, 'render_discount_6_months_field' ),
            $this->page_slug,
            'bot_platform_renewal_section'
        );
        
        add_settings_field(
            'discount_12_months',
            __( 'تخفیف ۱۲ ماهه (درصد)', 'bot-platform-connector' ),
            array( $this, 'render_discount_12_months_field' ),
            $this->page_slug,
            'bot_platform_renewal_section'
        );
        
        // =====================
        // بخش تنظیمات ایمیل
        // =====================
        register_setting(
            'bot_platform_email_group',
            'bot_platform_email_settings',
            array(
                'type'              => 'object',
                'sanitize_callback' => array( $this, 'sanitize_email_settings' ),
                'default'           => array(
                    'send_warning_emails'   => 1,
                    'warning_7_days'        => 1,
                    'warning_3_days'        => 1,
                    'warning_expiration_day' => 1,
                ),
            )
        );
        
        add_settings_section(
            'bot_platform_email_section',
            __( 'تنظیمات ایمیل', 'bot-platform-connector' ),
            array( $this, 'render_email_section_description' ),
            $this->page_slug
        );
        
        add_settings_field(
            'send_warning_emails',
            __( 'ارسال ایمیل هشدار', 'bot-platform-connector' ),
            array( $this, 'render_send_warning_emails_field' ),
            $this->page_slug,
            'bot_platform_email_section'
        );
        
        add_settings_field(
            'warning_7_days',
            __( 'هشدار ۷ روز قبل', 'bot-platform-connector' ),
            array( $this, 'render_warning_7_days_field' ),
            $this->page_slug,
            'bot_platform_email_section'
        );
        
        add_settings_field(
            'warning_3_days',
            __( 'هشدار ۳ روز قبل', 'bot-platform-connector' ),
            array( $this, 'render_warning_3_days_field' ),
            $this->page_slug,
            'bot_platform_email_section'
        );
        
        add_settings_field(
            'warning_expiration_day',
            __( 'هشدار روز انقضا', 'bot-platform-connector' ),
            array( $this, 'render_warning_expiration_day_field' ),
            $this->page_slug,
            'bot_platform_email_section'
        );
        
        // =====================
        // بخش تنظیمات عمومی
        // =====================
        register_setting(
            'bot_platform_general_group',
            'bot_platform_general_settings',
            array(
                'type'              => 'object',
                'sanitize_callback' => array( $this, 'sanitize_general_settings' ),
                'default'           => array(
                    'auto_sync_interval' => 5,
                ),
            )
        );
        
        add_settings_section(
            'bot_platform_general_section',
            __( 'تنظیمات عمومی', 'bot-platform-connector' ),
            array( $this, 'render_general_section_description' ),
            $this->page_slug
        );
        
        add_settings_field(
            'auto_sync_interval',
            __( 'همگام‌سازی خودکار هر', 'bot-platform-connector' ),
            array( $this, 'render_auto_sync_interval_field' ),
            $this->page_slug,
            'bot_platform_general_section'
        );
    }

    /**
     * نمایش توضیحات بخش تنظیمات اتصال
     */
    public function render_connection_section_description() {
        echo '<p class="description">' . 
            __( 'تنظیمات مربوط به اتصال به بک‌اند لاراول را وارد کنید.', 'bot-platform-connector') . 
            '</p>';
    }

    /**
     * نمایش فیلد آدرس API
     */
    public function render_api_base_url_field() {
        $options = get_option( 'bot_platform_connection_settings', array() );
        $value   = isset( $options['api_base_url'] ) ? $options['api_base_url'] : '';
        
        echo '<input type="url" 
                   name="bot_platform_connection_settings[api_base_url]" 
                   id="api_base_url" 
                   value="' . esc_attr( $value ) . '" 
                   class="regular-text ltr" 
                   placeholder="https://api.example.com" />';
        echo '<p class="description">' . 
            __( 'آدرس کامل API بک‌اند لاراول را بدون اسلش انتهایی وارد کنید.', 'bot-platform-connector' ) . 
            '</p>';
    }

    /**
     * نمایش فیلد کلید HMAC
     */
    public function render_hmac_secret_field() {
        $options = get_option( 'bot_platform_connection_settings', array() );
        $value   = isset( $options['hmac_secret'] ) ? $options['hmac_secret'] : '';
        
        echo '<input type="password" 
                   name="bot_platform_connection_settings[hmac_secret]" 
                   id="hmac_secret" 
                   value="' . esc_attr( $value ) . '" 
                   class="regular-text ltr" 
                   autocomplete="off" />';
        echo '<p class="description">' . 
            __( 'کلید امنیتی HMAC برای امضای درخواست‌ها به بک‌اند.', 'bot-platform-connector' ) . 
            '</p>';
    }

    /**
     * نمایش توضیحات بخش تنظیمات تمدید
     */
    public function render_renewal_section_description() {
        echo '<p class="description">' . 
            __( 'تنظیمات مربوط به قیمت‌گذاری و تخفیف‌های تمدید اشتراک.', 'bot-platform-connector' ) . 
            '</p>';
    }

    /**
     * نمایش فیلد مبلغ پایه ماهانه
     */
    public function render_base_monthly_price_field() {
        $options = get_option( 'bot_platform_renewal_settings', array() );
        $value   = isset( $options['base_monthly_price'] ) ? $options['base_monthly_price'] : 0;
        
        echo '<input type="number" 
                   name="bot_platform_renewal_settings[base_monthly_price]" 
                   id="base_monthly_price" 
                   value="' . esc_attr( $value ) . '" 
                   min="0" 
                   step="1000" 
                   class="small-text" />';
        echo '<p class="description">' . 
            __( 'مبلغ پایه تمدید اشتراک برای هر ماه به تومان.', 'bot-platform-connector' ) . 
            '</p>';
    }

    /**
     * نمایش فیلد تخفیف ۳ ماهه
     */
    public function render_discount_3_months_field() {
        $options = get_option( 'bot_platform_renewal_settings', array() );
        $value   = isset( $options['discount_3_months'] ) ? $options['discount_3_months'] : 10;
        
        echo '<input type="number" 
                   name="bot_platform_renewal_settings[discount_3_months]" 
                   id="discount_3_months" 
                   value="' . esc_attr( $value ) . '" 
                   min="0" 
                   max="100" 
                   class="small-text" />';
        echo '<p class="description">' . 
            __( 'درصد تخفیف برای تمدید ۳ ماهه.', 'bot-platform-connector' ) . 
            '</p>';
    }

    /**
     * نمایش فیلد تخفیف ۶ ماهه
     */
    public function render_discount_6_months_field() {
        $options = get_option( 'bot_platform_renewal_settings', array() );
        $value   = isset( $options['discount_6_months'] ) ? $options['discount_6_months'] : 20;
        
        echo '<input type="number" 
                   name="bot_platform_renewal_settings[discount_6_months]" 
                   id="discount_6_months" 
                   value="' . esc_attr( $value ) . '" 
                   min="0" 
                   max="100" 
                   class="small-text" />';
        echo '<p class="description">' . 
            __( 'درصد تخفیف برای تمدید ۶ ماهه.', 'bot-platform-connector' ) . 
            '</p>';
    }

    /**
     * نمایش فیلد تخفیف ۱۲ ماهه
     */
    public function render_discount_12_months_field() {
        $options = get_option( 'bot_platform_renewal_settings', array() );
        $value   = isset( $options['discount_12_months'] ) ? $options['discount_12_months'] : 30;
        
        echo '<input type="number" 
                   name="bot_platform_renewal_settings[discount_12_months]" 
                   id="discount_12_months" 
                   value="' . esc_attr( $value ) . '" 
                   min="0" 
                   max="100" 
                   class="small-text" />';
        echo '<p class="description">' . 
            __( 'درصد تخفیف برای تمدید ۱۲ ماهه.', 'bot-platform-connector' ) . 
            '</p>';
    }

    /**
     * نمایش توضیحات بخش تنظیمات ایمیل
     */
    public function render_email_section_description() {
        echo '<p class="description">' . 
            __( 'تنظیمات مربوط به ارسال ایمیل‌های هشدار انقضا به کاربران.', 'bot-platform-connector' ) . 
            '</p>';
    }

    /**
     * نمایش فیلد ارسال ایمیل هشدار
     */
    public function render_send_warning_emails_field() {
        $options = get_option( 'bot_platform_email_settings', array() );
        $value   = isset( $options['send_warning_emails'] ) ? $options['send_warning_emails'] : 1;
        
        echo '<label><input type="checkbox" 
                            name="bot_platform_email_settings[send_warning_emails]" 
                            value="1" ' . checked( $value, 1, false ) . ' /> ' .
            __( 'فعال‌سازی ارسال ایمیل‌های هشدار انقضا', 'bot-platform-connector' ) . 
            '</label>';
    }

    /**
     * نمایش فیلد هشدار ۷ روز قبل
     */
    public function render_warning_7_days_field() {
        $options = get_option( 'bot_platform_email_settings', array() );
        $value   = isset( $options['warning_7_days'] ) ? $options['warning_7_days'] : 1;
        
        echo '<label><input type="checkbox" 
                            name="bot_platform_email_settings[warning_7_days]" 
                            value="1" ' . checked( $value, 1, false ) . ' /> ' .
            __( 'ارسال هشدار ۷ روز قبل از انقضا', 'bot-platform-connector' ) . 
            '</label>';
    }

    /**
     * نمایش فیلد هشدار ۳ روز قبل
     */
    public function render_warning_3_days_field() {
        $options = get_option( 'bot_platform_email_settings', array() );
        $value   = isset( $options['warning_3_days'] ) ? $options['warning_3_days'] : 1;
        
        echo '<label><input type="checkbox" 
                            name="bot_platform_email_settings[warning_3_days]" 
                            value="1" ' . checked( $value, 1, false ) . ' /> ' .
            __( 'ارسال هشدار ۳ روز قبل از انقضا', 'bot-platform-connector' ) . 
            '</label>';
    }

    /**
     * نمایش فیلد هشدار روز انقضا
     */
    public function render_warning_expiration_day_field() {
        $options = get_option( 'bot_platform_email_settings', array() );
        $value   = isset( $options['warning_expiration_day'] ) ? $options['warning_expiration_day'] : 1;
        
        echo '<label><input type="checkbox" 
                            name="bot_platform_email_settings[warning_expiration_day]" 
                            value="1" ' . checked( $value, 1, false ) . ' /> ' .
            __( 'ارسال هشدار در روز انقضا', 'bot-platform-connector' ) . 
            '</label>';
    }

    /**
     * نمایش توضیحات بخش تنظیمات عمومی
     */
    public function render_general_section_description() {
        echo '<p class="description">' . 
            __( 'تنظیمات عمومی و سیستمی پلاگین.', 'bot-platform-connector' ) . 
            '</p>';
    }

    /**
     * نمایش فیلد بازه همگام‌سازی خودکار
     */
    public function render_auto_sync_interval_field() {
        $options = get_option( 'bot_platform_general_settings', array() );
        $value   = isset( $options['auto_sync_interval'] ) ? $options['auto_sync_interval'] : 5;
        
        echo '<input type="number" 
                   name="bot_platform_general_settings[auto_sync_interval]" 
                   id="auto_sync_interval" 
                   value="' . esc_attr( $value ) . '" 
                   min="1" 
                   max="60" 
                   class="small-text" /> ' .
            __( 'دقیقه', 'bot-platform-connector' );
        echo '<p class="description">' . 
            __( 'فاصله زمانی بین همگام‌سازی خودکار با بک‌اند لاراول.', 'bot-platform-connector' ) . 
            '</p>';
    }

    /**
     * اعتبارسنجی تنظیمات اتصال
     * 
     * @param array $new_value مقادیر جدید
     * @param array $old_value مقادیر قدیمی
     * @return array مقادیر اعتبارسنجی شده
     */
    public function validate_connection_settings( $new_value, $old_value ) {
        // بررسی nonce برای امنیت
        if ( ! isset( $_POST['_wpnonce'] ) || 
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'bot-platform-settings-options' ) ) {
            add_settings_error(
                'bot_platform_connection_settings',
                'invalid_nonce',
                __( 'خطای امنیتی: لطفاً دوباره تلاش کنید.', 'bot-platform-connector' ),
                'error'
            );
            return $old_value;
        }
        
        return $new_value;
    }

    /**
     * اعتبارسنجی تنظیمات تمدید
     * 
     * @param array $new_value مقادیر جدید
     * @param array $old_value مقادیر قدیمی
     * @return array مقادیر اعتبارسنجی شده
     */
    public function validate_renewal_settings( $new_value, $old_value ) {
        // بررسی مقادیر عددی
        $new_value['base_monthly_price'] = absint( $new_value['base_monthly_price'] );
        $new_value['discount_3_months']  = min( 100, max( 0, absint( $new_value['discount_3_months'] ) ) );
        $new_value['discount_6_months']  = min( 100, max( 0, absint( $new_value['discount_6_months'] ) ) );
        $new_value['discount_12_months'] = min( 100, max( 0, absint( $new_value['discount_12_months'] ) ) );
        
        // بررسی منطق تخفیف‌ها
        if ( $new_value['discount_3_months'] > $new_value['discount_6_months'] ) {
            add_settings_error(
                'bot_platform_renewal_settings',
                'invalid_discount',
                __( 'تخفیف ۳ ماهه نمی‌تواند بیشتر از تخفیف ۶ ماهه باشد.', 'bot-platform-connector' ),
                'error'
            );
        }
        
        if ( $new_value['discount_6_months'] > $new_value['discount_12_months'] ) {
            add_settings_error(
                'bot_platform_renewal_settings',
                'invalid_discount',
                __( 'تخفیف ۶ ماهه نمی‌تواند بیشتر از تخفیف ۱۲ ماهه باشد.', 'bot-platform-connector' ),
                'error'
            );
        }
        
        return $new_value;
    }

    /**
     * اعتبارسنجی تنظیمات ایمیل
     * 
     * @param array $new_value مقادیر جدید
     * @param array $old_value مقادیر قدیمی
     * @return array مقادیر اعتبارسنجی شده
     */
    public function validate_email_settings( $new_value, $old_value ) {
        $new_value['send_warning_emails']    = isset( $new_value['send_warning_emails'] ) ? 1 : 0;
        $new_value['warning_7_days']         = isset( $new_value['warning_7_days'] ) ? 1 : 0;
        $new_value['warning_3_days']         = isset( $new_value['warning_3_days'] ) ? 1 : 0;
        $new_value['warning_expiration_day'] = isset( $new_value['warning_expiration_day'] ) ? 1 : 0;
        
        return $new_value;
    }

    /**
     * اعتبارسنجی تنظیمات عمومی
     * 
     * @param array $new_value مقادیر جدید
     * @param array $old_value مقادیر قدیمی
     * @return array مقادیر اعتبارسنجی شده
     */
    public function validate_general_settings( $new_value, $old_value ) {
        $new_value['auto_sync_interval'] = min( 60, max( 1, absint( $new_value['auto_sync_interval'] ) ) );
        
        return $new_value;
    }

    /**
     * پاک‌سازی تنظیمات اتصال
     * 
     * @param array $settings تنظیمات خام
     * @return array تنظیمات پاک‌سازی شده
     */
    public function sanitize_connection_settings( $settings ) {
        $sanitized = array();
        
        if ( isset( $settings['api_base_url'] ) ) {
            $sanitized['api_base_url'] = esc_url_raw( trim( $settings['api_base_url'] ) );
        }
        
        if ( isset( $settings['hmac_secret'] ) ) {
            $sanitized['hmac_secret'] = sanitize_text_field( $settings['hmac_secret'] );
        }
        
        return $sanitized;
    }

    /**
     * پاک‌سازی تنظیمات تمدید
     * 
     * @param array $settings تنظیمات خام
     * @return array تنظیمات پاک‌سازی شده
     */
    public function sanitize_renewal_settings( $settings ) {
        $sanitized = array();
        
        $sanitized['base_monthly_price'] = absint( $settings['base_monthly_price'] ?? 0 );
        $sanitized['discount_3_months']  = min( 100, max( 0, absint( $settings['discount_3_months'] ?? 10 ) ) );
        $sanitized['discount_6_months']  = min( 100, max( 0, absint( $settings['discount_6_months'] ?? 20 ) ) );
        $sanitized['discount_12_months'] = min( 100, max( 0, absint( $settings['discount_12_months'] ?? 30 ) ) );
        
        return $sanitized;
    }

    /**
     * پاک‌سازی تنظیمات ایمیل
     * 
     * @param array $settings تنظیمات خام
     * @return array تنظیمات پاک‌سازی شده
     */
    public function sanitize_email_settings( $settings ) {
        return array(
            'send_warning_emails'    => isset( $settings['send_warning_emails'] ) ? 1 : 0,
            'warning_7_days'         => isset( $settings['warning_7_days'] ) ? 1 : 0,
            'warning_3_days'         => isset( $settings['warning_3_days'] ) ? 1 : 0,
            'warning_expiration_day' => isset( $settings['warning_expiration_day'] ) ? 1 : 0,
        );
    }

    /**
     * پاک‌سازی تنظیمات عمومی
     * 
     * @param array $settings تنظیمات خام
     * @return array تنظیمات پاک‌سازی شده
     */
    public function sanitize_general_settings( $settings ) {
        return array(
            'auto_sync_interval' => min( 60, max( 1, absint( $settings['auto_sync_interval'] ?? 5 ) ) ),
        );
    }

    /**
     * نمایش صفحه تنظیمات
     */
    public function render_settings_page() {
        // بررسی دسترسی کاربر
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'شما اجازه دسترسی به این صفحه را ندارید.', 'bot-platform-connector' ) );
        }
        
        // نمایش پیام‌های خطا/موفقیت
        settings_errors( 'bot_platform_connection_settings' );
        settings_errors( 'bot_platform_renewal_settings' );
        settings_errors( 'bot_platform_email_settings' );
        settings_errors( 'bot_platform_general_settings' );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <form action="options.php" method="post">
                <?php
                // تنظیمات اتصال
                settings_fields( 'bot_platform_connection_group' );
                do_settings_sections( $this->page_slug, 'bot_platform_connection_section' );
                
                submit_button( __( 'ذخیره تنظیمات اتصال', 'bot-platform-connector' ) );
                ?>
            </form>
            
            <hr />
            
            <form action="options.php" method="post">
                <?php
                // تنظیمات تمدید
                settings_fields( 'bot_platform_renewal_group' );
                do_settings_sections( $this->page_slug, 'bot_platform_renewal_section' );
                
                submit_button( __( 'ذخیره تنظیمات تمدید', 'bot-platform-connector' ) );
                ?>
            </form>
            
            <hr />
            
            <form action="options.php" method="post">
                <?php
                // تنظیمات ایمیل
                settings_fields( 'bot_platform_email_group' );
                do_settings_sections( $this->page_slug, 'bot_platform_email_section' );
                
                submit_button( __( 'ذخیره تنظیمات ایمیل', 'bot-platform-connector' ) );
                ?>
            </form>
            
            <hr />
            
            <form action="options.php" method="post">
                <?php
                // تنظیمات عمومی
                settings_fields( 'bot_platform_general_group' );
                do_settings_sections( $this->page_slug, 'bot_platform_general_section' );
                
                submit_button( __( 'ذخیره تنظیمات عمومی', 'bot-platform-connector' ) );
                ?>
            </form>
        </div>
        <?php
    }
}

// ایجاد نمونه از کلاس
new Bot_Platform_Connector_Admin_Settings();
