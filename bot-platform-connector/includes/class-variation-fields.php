<?php
declare(strict_types=1);

/**
 * کلاس مدیریت فیلدهای سفارشی برای تغییرات محصول ووکامرس
 * 
 * این کلاس فیلدهای مربوط به ربات تلگرام را به صفحه تنظیمات
 * تغییرات محصول (Product Variations) اضافه می‌کند.
 * 
 * @package Bot_Platform_Connector
 * @since 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * کلاس مدیریت فیلدهای تغییرات محصول
 */
class Bot_Platform_Connector_Variation_Fields {

    /**
     * مقداردهی اولیه و ثبت هوک‌ها
     */
    public function __construct() {
        // نمایش فیلدها در صفحه تغییرات محصول
        add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'add_variation_fields' ), 10, 3 );
        
        // ذخیره مقادیر فیلدها
        add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_fields' ), 10, 2 );
        
        // افزودن ستون‌ها به لیست تغییرات (اختیاری - برای نمایش بهتر)
        add_filter( 'woocommerce_available_variation', array( $this, 'add_variation_data' ), 10, 3 );
    }

    /**
     * افزودن فیلدهای سفارشی به فرم تغییرات محصول
     * 
     * @param int     $variation_id شناسه تغییرات
     * @param int     $i ایندکس تغییرات
     * @param array   $variation_data داده‌های تغییرات
     */
    public function add_variation_fields( $variation_id, $i, $variation_data ) {
        // بررسی امنیت با nonce
        wp_nonce_field( 'bot_platform_save_variation_' . $variation_id, 'bot_platform_variation_nonce' );
        
        // دریافت مقادیر ذخیره شده
        $bot_type          = get_post_meta( $variation_id, '_bot_type', true );
        $bot_duration      = get_post_meta( $variation_id, '_bot_duration', true );
        $laravel_product_id = get_post_meta( $variation_id, '_bot_laravel_product_id', true );
        
        // مقدار پیش‌فرض برای نوع ربات
        if ( empty( $bot_type ) ) {
            $bot_type = 'downloadable';
        }
        
        // مقدار پیش‌فرض برای مدت زمان (0 برای دانلودی)
        if ( empty( $bot_duration ) ) {
            $bot_duration = 0;
        }
        ?>
        <div class="form-field form-row">
            <label><?php _e( 'نوع ربات', 'bot-platform-connector' ); ?></label>
            <select name="bot_type[<?php echo esc_attr( $variation_id ); ?>]" id="bot_type_<?php echo esc_attr( $variation_id ); ?>">
                <option value="downloadable" <?php selected( $bot_type, 'downloadable' ); ?>>
                    <?php _e( 'دانلودی', 'bot-platform-connector' ); ?>
                </option>
                <option value="subscription" <?php selected( $bot_type, 'subscription' ); ?>>
                    <?php _e( 'اشتراکی', 'bot-platform-connector' ); ?>
                </option>
            </select>
            <span class="description"><?php _e( 'نوع محصول را مشخص کنید: دانلودی (کد منبع) یا اشتراکی (میزبانی)', 'bot-platform-connector' ); ?></span>
        </div>
        
        <div class="form-field form-row">
            <label><?php _e( 'مدت زمان اشتراک (روز)', 'bot-platform-connector' ); ?></label>
            <input type="number" 
                   name="bot_duration[<?php echo esc_attr( $variation_id ); ?>]" 
                   id="bot_duration_<?php echo esc_attr( $variation_id ); ?>" 
                   value="<?php echo esc_attr( $bot_duration ); ?>" 
                   min="0" 
                   step="1" 
                   placeholder="0" />
            <span class="description"><?php _e( 'برای محصولات دانلودی مقدار 0 وارد کنید. برای اشتراکی، تعداد روزهای اعتبار را وارد کنید.', 'bot-platform-connector' ); ?></span>
        </div>
        
        <div class="form-field form-row">
            <label><?php _e( 'شناسه محصول در لاراول', 'bot-platform-connector' ); ?></label>
            <input type="text" 
                   name="bot_laravel_product_id[<?php echo esc_attr( $variation_id ); ?>]" 
                   id="bot_laravel_product_id_<?php echo esc_attr( $variation_id ); ?>" 
                   value="<?php echo esc_attr( $laravel_product_id ); ?>" 
                   placeholder="<?php esc_attr_e( 'مثلاً: 123', 'bot-platform-connector' ); ?>" />
            <span class="description"><?php _e( 'شناسه محصول در سیستم بک‌اند لاراول را وارد کنید.', 'bot-platform-connector' ); ?></span>
        </div>
        <?php
    }

    /**
     * ذخیره مقادیر فیلدهای سفارشی
     * 
     * @param int $variation_id شناسه تغییرات
     * @param int $i ایندکس تغییرات
     */
    public function save_variation_fields( $variation_id, $i ) {
        // بررسی امنیت nonce
        if ( ! isset( $_POST['bot_platform_variation_nonce'] ) || 
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bot_platform_variation_nonce'] ) ), 'bot_platform_save_variation_' . $variation_id ) ) {
            return;
        }
        
        // ذخیره نوع ربات
        if ( isset( $_POST['bot_type'][ $variation_id ] ) ) {
            $bot_type = sanitize_text_field( wp_unslash( $_POST['bot_type'][ $variation_id ] ) );
            // اطمینان از مقدار معتبر
            if ( in_array( $bot_type, array( 'downloadable', 'subscription' ), true ) ) {
                update_post_meta( $variation_id, '_bot_type', $bot_type );
            }
        }
        
        // ذخیره مدت زمان اشتراک
        if ( isset( $_POST['bot_duration'][ $variation_id ] ) ) {
            $bot_duration = absint( wp_unslash( $_POST['bot_duration'][ $variation_id ] ) );
            update_post_meta( $variation_id, '_bot_duration', $bot_duration );
        }
        
        // ذخیره شناسه محصول لاراول
        if ( isset( $_POST['bot_laravel_product_id'][ $variation_id ] ) ) {
            $laravel_product_id = sanitize_text_field( wp_unslash( $_POST['bot_laravel_product_id'][ $variation_id ] ) );
            update_post_meta( $variation_id, '_bot_laravel_product_id', $laravel_product_id );
        }
    }

    /**
     * افزودن داده‌های سفارشی به آرایه تغییرات
     * 
     * @param array                $variation_data داده‌های تغییرات
     * @param WC_Product_Variable  $product محصول متغیر
     * @param WC_Product_Variation $variation تغییرات
     * @return array داده‌های تغییرات با فیلدهای سفارشی
     */
    public function add_variation_data( $variation_data, $product, $variation ) {
        $variation_id = $variation->get_id();
        
        $variation_data['bot_type']          = get_post_meta( $variation_id, '_bot_type', true );
        $variation_data['bot_duration']      = get_post_meta( $variation_id, '_bot_duration', true );
        $variation_data['bot_laravel_product_id'] = get_post_meta( $variation_id, '_bot_laravel_product_id', true );
        
        return $variation_data;
    }
}

// ایجاد نمونه از کلاس
new Bot_Platform_Connector_Variation_Fields();
