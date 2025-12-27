<?php
/**
 * Plugin Name: Gelismis Pop-up Uyari Sistemi
 * Description: Ziyaretçilere reklam engelleyici kapatma uyarısı gösteren özelleştirilebilir bir pop-up eklentisi.
 * Version: 4.3
 * Author: Solderet
 * Text Domain: reklam-uyarisi
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('ReklamUyarisiPlugin')) {
    class ReklamUyarisiPlugin {
        private $defaults = array(
            'popup_enabled' => 1,
            'popup_type' => 'adblock',
            'popup_theme' => 'dark',
            'popup_position' => 'center',
            'popup_animation' => 'scale',
            'popup_headline' => 'Lutfen Reklam Engelleyicinizi Kapatin!',
            'main_description' => 'Iceriklerimizi ucretsiz sunabilmemiz icin reklamlara ihtiyacimiz var.',
            'advantages_list' => "Sitemiz daha sik guncellenir\nDaha cok icerik\nKullanici deneyimi gelisir",
            'close_button_text' => 'Anladim',
            'show_delay_seconds' => 0,
            'countdown_seconds' => 3,
            'cookie_expiry_hours' => 24,
            'backdrop_blur' => 8,
            'backdrop_opacity' => 85,
            'primary_color' => '#8b0000',
            'accent_color' => '#dc2626',
            'bg_color' => '#050505',
            'text_color' => '#ffffff',
            'show_close_x' => 0,
            'excluded_pages' => '',
            'mobile_enabled' => 1,
            'show_on_scroll' => 0,
            'scroll_percentage' => 50,
            'exit_intent' => 0,
            'custom_css' => '',
            'enable_stats' => 1,
            'target_mode' => 'exclude', // 'exclude' or 'include'
            'target_pages' => '' // Renamed from excluded_pages for clarity, but keeping backward compat if needed or just migrating
        );

        public function __construct() {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'register_settings'));
            add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
            add_action('wp_footer', array($this, 'render_popup'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            add_action('wp_ajax_popup_track', array($this, 'track_stats'));
            add_action('wp_ajax_nopriv_popup_track', array($this, 'track_stats'));
            register_activation_hook(__FILE__, array($this, 'activate'));
        }

        public function activate() {
            $current_options = get_option('reklam_uyarisi_options', array());
            $new_options = wp_parse_args($current_options, $this->defaults);
            update_option('reklam_uyarisi_options', $new_options);
            
            if (!get_option('reklam_uyarisi_stats')) {
                update_option('reklam_uyarisi_stats', array('views' => 0, 'closes' => 0));
            }
        }

        public function opt($key) {
            $options = get_option('reklam_uyarisi_options', array());
            if (isset($options[$key])) {
                return $options[$key];
            }
            return isset($this->defaults[$key]) ? $this->defaults[$key] : '';
        }

        public function add_admin_menu() {
            add_menu_page(
                'Pop-up Ayarlari',
                'Pop-up Uyari',
                'manage_options',
                'reklam-uyarisi',
                array($this, 'settings_page'),
                'dashicons-megaphone',
                80
            );
            add_submenu_page(
                'reklam-uyarisi',
                'Istatistikler',
                'Istatistikler',
                'manage_options',
                'reklam-uyarisi-stats',
                array($this, 'stats_page')
            );
        }

        public function register_settings() {
            register_setting('reklam_uyarisi_group', 'reklam_uyarisi_options', array($this, 'sanitize'));

            add_settings_section('general', 'Genel Ayarlar', null, 'reklam-uyarisi');
            $this->add_field('popup_enabled', 'Pop-up Aktif', 'checkbox', 'general');
            $this->add_field('popup_type', 'Tur', 'type_select', 'general');
            $this->add_field('popup_theme', 'Tema', 'theme_select', 'general');
            $this->add_field('popup_position', 'Konum', 'position_select', 'general');
            $this->add_field('popup_animation', 'Animasyon', 'animation_select', 'general');

            add_settings_section('content', 'Icerik Ayarlari', null, 'reklam-uyarisi');
            $this->add_field('popup_headline', 'Baslik', 'text', 'content');
            $this->add_field('main_description', 'Aciklama', 'textarea', 'content');
            $this->add_field('advantages_list', 'Avantajlar', 'textarea', 'content');
            $this->add_field('close_button_text', 'Buton Metni', 'text', 'content');

            add_settings_section('timing', 'Zamanlama', null, 'reklam-uyarisi');
            $this->add_field('show_delay_seconds', 'Gosterim Gecikmesi (sn)', 'number', 'timing', array('min' => 0, 'max' => 300));
            $this->add_field('countdown_seconds', 'Buton Gecikmesi (sn)', 'number', 'timing', array('min' => 0, 'max' => 60));
            $this->add_field('cookie_expiry_hours', 'Tekrar Gosterim (saat)', 'number', 'timing', array('min' => 0, 'max' => 720));
            $this->add_field('show_on_scroll', 'Scrollda Goster', 'checkbox', 'timing');
            $this->add_field('scroll_percentage', 'Scroll Yuzdesi', 'number', 'timing', array('min' => 10, 'max' => 100));
            $this->add_field('exit_intent', 'Cikis Niyeti (Exit Intent)', 'checkbox', 'timing');

            add_settings_section('appearance', 'Gorunum', null, 'reklam-uyarisi');
            $this->add_field('primary_color', 'Ana Renk (Buton/Vurgu)', 'color', 'appearance');
            $this->add_field('accent_color', 'Yan Renk (Ikon/Efekt)', 'color', 'appearance');
            $this->add_field('bg_color', 'Arka Plan Rengi', 'color', 'appearance');
            $this->add_field('text_color', 'Yazi Rengi', 'color', 'appearance');
            $this->add_field('backdrop_blur', 'Bulaniklik (px)', 'number', 'appearance', array('min' => 0, 'max' => 30));
            $this->add_field('backdrop_opacity', 'Opaklik (%)', 'number', 'appearance', array('min' => 0, 'max' => 100));
            $this->add_field('show_close_x', 'X Butonu', 'checkbox', 'appearance');

            add_settings_section('advanced', 'Gelismis', null, 'reklam-uyarisi');
            $this->add_field('mobile_enabled', 'Mobilde Goster', 'checkbox', 'advanced');
            $this->add_field('enable_stats', 'Istatistik Kaydet', 'checkbox', 'advanced');
            $this->add_field('target_mode', 'Hedefleme Modu', 'select_target_mode', 'advanced');
            $this->add_field('target_pages', 'Sayfa Listesi (URL parcasi)', 'textarea', 'advanced');
            $this->add_field('custom_css', 'Ozel CSS', 'textarea', 'advanced');
        }

        private function add_field($name, $label, $type, $section, $extra = array()) {
            add_settings_field(
                $name,
                $label,
                array($this, 'render_field'),
                'reklam-uyarisi',
                $section,
                array_merge(array('name' => $name, 'type' => $type), $extra)
            );
        }

        public function render_field($args) {
            $name = $args['name'];
            $type = $args['type'];
            $value = $this->opt($name);
            $field_name = "reklam_uyarisi_options[$name]";

            switch ($type) {
                case 'text':
                    echo '<input type="text" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" class="regular-text">';
                    break;
                case 'textarea':
                    echo '<textarea name="' . esc_attr($field_name) . '" rows="4" class="large-text">' . esc_textarea($value) . '</textarea>';
                    break;
                case 'number':
                    $min = isset($args['min']) ? $args['min'] : 0;
                    $max = isset($args['max']) ? $args['max'] : 100;
                    echo '<input type="number" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" class="small-text">';
                    break;
                case 'checkbox':
                    echo '<input type="checkbox" name="' . esc_attr($field_name) . '" value="1" ' . checked($value, 1, false) . '>';
                    break;
                case 'color':
                    echo '<input type="color" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '">';
                    break;
                case 'select_target_mode':
                    echo '<select name="' . esc_attr($field_name) . '">';
                    $modes = array('exclude' => 'Haric Tut (Bu sayfalar disinda goster)', 'include' => 'Dahil Et (Sadece bu sayfalarda goster)');
                    foreach ($modes as $k => $v) {
                        echo '<option value="' . esc_attr($k) . '" ' . selected($value, $k, false) . '>' . esc_html($v) . '</option>';
                    }
                    echo '</select>';
                    break;
                case 'type_select':
                    echo '<select name="' . esc_attr($field_name) . '">';
                    $types = array('adblock' => 'Reklam Engelleyici', 'announcement' => 'Duyuru', 'warning' => 'Uyari', 'cookie' => 'Cerez', 'newsletter' => 'Bulten', 'donation' => 'Bagis / Destek');
                    foreach ($types as $k => $v) {
                        echo '<option value="' . esc_attr($k) . '" ' . selected($value, $k, false) . '>' . esc_html($v) . '</option>';
                    }
                    echo '</select>';
                    break;
                case 'theme_select':
                    echo '<select name="' . esc_attr($field_name) . '">';
                    $themes = array('dark' => 'Koyu', 'light' => 'Acik', 'glass' => 'Cam', 'gradient' => 'Gradient', 'neon' => 'Neon');
                    foreach ($themes as $k => $v) {
                        echo '<option value="' . esc_attr($k) . '" ' . selected($value, $k, false) . '>' . esc_html($v) . '</option>';
                    }
                    echo '</select>';
                    break;
                case 'position_select':
                    echo '<select name="' . esc_attr($field_name) . '">';
                    $positions = array('center' => 'Orta', 'top' => 'Ust', 'bottom' => 'Alt', 'bottom-right' => 'Sag Alt', 'bottom-left' => 'Sol Alt');
                    foreach ($positions as $k => $v) {
                        echo '<option value="' . esc_attr($k) . '" ' . selected($value, $k, false) . '>' . esc_html($v) . '</option>';
                    }
                    echo '</select>';
                    break;
                case 'animation_select':
                    echo '<select name="' . esc_attr($field_name) . '">';
                    $animations = array('scale' => 'Olcekleme', 'fade' => 'Solma', 'slide-up' => 'Yukari', 'slide-down' => 'Asagi', 'bounce' => 'Ziplama', 'flip' => 'Cevirme');
                    foreach ($animations as $k => $v) {
                        echo '<option value="' . esc_attr($k) . '" ' . selected($value, $k, false) . '>' . esc_html($v) . '</option>';
                    }
                    echo '</select>';
                    break;
            }
        }

        public function sanitize($input) {
            $sanitized = array();
            $defaults = $this->defaults;
            
            foreach ($defaults as $key => $default_val) {
                if (!isset($input[$key])) {
                    if (is_int($default_val)) {
                        $sanitized[$key] = 0; // Checkbox unchecked case
                    } else {
                        $sanitized[$key] = $default_val;
                    }
                    continue;
                }

                $val = $input[$key];
                
                if (is_int($default_val) && $key !== 'show_delay_seconds' && $key !== 'countdown_seconds' && $key !== 'cookie_expiry_hours' && $key !== 'backdrop_blur' && $key !== 'backdrop_opacity' && $key !== 'scroll_percentage') {
                     // Checkboxes
                     $sanitized[$key] = 1;
                } elseif (is_int($default_val)) {
                    // Numeric inputs
                    $sanitized[$key] = absint($val);
                } elseif ($key === 'main_description' || $key === 'popup_headline') {
                     $sanitized[$key] = wp_kses_post($val);
                } elseif ($key === 'advantages_list' || $key === 'excluded_pages' || $key === 'target_pages' || $key === 'custom_css') {
                     $sanitized[$key] = sanitize_textarea_field($val);
                } elseif ($key === 'target_mode') {
                     $sanitized[$key] = sanitize_text_field($val);
                } elseif ($key === 'primary_color' || $key === 'accent_color' || $key === 'bg_color' || $key === 'text_color') {
                     $sanitized[$key] = sanitize_hex_color($val);
                } else {
                     $sanitized[$key] = sanitize_text_field($val);
                }
            }
            return $sanitized;
        }

        public function settings_page() {
            if (!current_user_can('manage_options')) {
                return;
            }
            ?>
            <div class="wrap reklam-uyarisi-admin">
                <h1><span class="dashicons dashicons-megaphone"></span> <?php echo esc_html(get_admin_page_title()); ?></h1>
                <form action="options.php" method="post">
                    <?php
                    settings_fields('reklam_uyarisi_group');
                    do_settings_sections('reklam-uyarisi');
                    submit_button('Ayarlari Kaydet', 'primary large');
                    ?>
                </form>
            </div>
            <?php
        }

        public function stats_page() {
            if (!current_user_can('manage_options')) {
                return;
            }

            $stats = get_option('reklam_uyarisi_stats', array('views' => 0, 'closes' => 0));
            
            // Check for reset
            if (isset($_POST['reset_stats']) && check_admin_referer('reset_popup_stats')) {
                $stats = array('views' => 0, 'closes' => 0);
                update_option('reklam_uyarisi_stats', $stats);
                echo '<div class="notice notice-success"><p>Istatistikler sifirlandi!</p></div>';
            }

            $rate = $stats['views'] > 0 ? round(($stats['closes'] / $stats['views']) * 100, 1) : 0;
            ?>
            <div class="wrap reklam-uyarisi-admin">
                <h1><span class="dashicons dashicons-chart-bar"></span> Pop-up Istatistikleri</h1>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><span class="dashicons dashicons-visibility"></span></div>
                        <div class="stat-value"><?php echo number_format($stats['views']); ?></div>
                        <div class="stat-label">Goruntulenme</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                        <div class="stat-value"><?php echo number_format($stats['closes']); ?></div>
                        <div class="stat-label">Kapatma</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><span class="dashicons dashicons-chart-pie"></span></div>
                        <div class="stat-value"><?php echo $rate; ?>%</div>
                        <div class="stat-label">Oran</div>
                    </div>
                </div>
                <form method="post" style="margin-top:20px;">
                    <?php wp_nonce_field('reset_popup_stats'); ?>
                    <button type="submit" name="reset_stats" class="button">Istatistikleri Sifirla</button>
                </form>
            </div>
            <?php
        }

        public function admin_scripts($hook) {
            if (strpos($hook, 'reklam-uyarisi') !== false) {
                wp_enqueue_style('reklam-uyarisi-admin', plugin_dir_url(__FILE__) . 'assets/admin.css', array(), '4.1');
            }
        }

        public function track_stats() {
            check_ajax_referer('popup_nonce', 'nonce');
            
            $stats = get_option('reklam_uyarisi_stats', array('views' => 0, 'closes' => 0));
            $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
            
            if ($type === 'view') {
                $stats['views']++;
            } elseif ($type === 'close') {
                $stats['closes']++;
            }
            
            update_option('reklam_uyarisi_stats', $stats);
            wp_send_json_success();
        }

        public function should_show() {
            if (!$this->opt('popup_enabled')) {
                return false;
            }
            if (!$this->opt('mobile_enabled') && wp_is_mobile()) {
                return false;
            }
            
            $target_mode = $this->opt('target_mode');
            $target_pages = $this->opt('target_pages');
            
            // Backward compatibility for 'excluded_pages' if 'target_pages' is empty but 'excluded_pages' exists
            if (empty($target_pages) && $this->opt('excluded_pages')) {
                $target_pages = $this->opt('excluded_pages');
                $target_mode = 'exclude';
            }

            if (!empty($target_pages)) {
                $pages_list = array_filter(array_map('trim', explode("\n", $target_pages)));
                $current_uri = $_SERVER['REQUEST_URI'];
                $match = false;
                
                foreach ($pages_list as $page) {
                    if (!empty($page) && strpos($current_uri, $page) !== false) {
                        $match = true;
                        break;
                    }
                }
                
                if ($target_mode === 'include') {
                    return $match; // Show only if matched
                } else {
                    return !$match; // Show only if NOT matched (exclude)
                }
            }
            
            // If include mode and no pages defined, maybe show nowhere? Or everywhere? 
            // Usually if include mode is on but list is empty, it implies "nowhere". 
            // But for safety let's assume default behavior if list is empty is show everywhere unless strict include logic.
            // Let's stick to: empty list = show everywhere (like default).
            
            return true;
        }

        public function enqueue_assets() {
            if (!$this->should_show()) {
                return;
            }

            wp_enqueue_style('reklam-uyarisi-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '4.3');
            
            $custom_css = $this->opt('custom_css');
            $css_vars = ':root { 
                --popup-primary: ' . esc_attr($this->opt('primary_color')) . ' !important; 
                --popup-accent: ' . esc_attr($this->opt('accent_color')) . ' !important; 
                --popup-bg: ' . esc_attr($this->opt('bg_color')) . ' !important; 
                --popup-text: ' . esc_attr($this->opt('text_color')) . ' !important; 
                --popup-blur: ' . esc_attr($this->opt('backdrop_blur')) . 'px !important; 
                --popup-opacity: ' . (esc_attr($this->opt('backdrop_opacity')) / 100) . ' !important; 
            }';
            
            if ($custom_css) {
                $css_vars .= "\n" . $custom_css;
            }
            
            wp_add_inline_style('reklam-uyarisi-style', $css_vars);
            
            wp_enqueue_script('reklam-uyarisi-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '4.3', true);
            
            wp_localize_script('reklam-uyarisi-script', 'popupData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('popup_nonce'),
                'delay' => intval($this->opt('show_delay_seconds')),
                'countdown' => intval($this->opt('countdown_seconds')),
                'cookieHours' => intval($this->opt('cookie_expiry_hours')),
                'buttonText' => esc_html($this->opt('close_button_text')),
                'animation' => esc_attr($this->opt('popup_animation')),
                'position' => esc_attr($this->opt('popup_position')),
                'showOnScroll' => intval($this->opt('show_on_scroll')),
                'scrollPercent' => intval($this->opt('scroll_percentage')),
                'exitIntent' => intval($this->opt('exit_intent')),
                'enableStats' => intval($this->opt('enable_stats')),
                'showCloseX' => intval($this->opt('show_close_x'))
            ));
        }

        public function render_popup() {
            if (!$this->should_show()) {
                return;
            }
            
            $type = $this->opt('popup_type');
            $theme = $this->opt('popup_theme');
            $position = $this->opt('popup_position');
            $animation = $this->opt('popup_animation');
            $headline = $this->opt('popup_headline') ?: 'Lutfen Reklam Engelleyicinizi Kapatin!';
            $desc = $this->opt('main_description');
            $btnText = $this->opt('close_button_text') ?: 'Anladim';
            $showX = $this->opt('show_close_x');
            
            $advList = array();
            $advRaw = $this->opt('advantages_list');
            if ($advRaw) {
                $advList = array_filter(array_map('trim', explode("\n", $advRaw)));
            }

            $icons = array(
                'adblock' => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.88-11.71L10 14.17l-1.88-1.88a.996.996 0 10-1.41 1.41l2.59 2.59c.39.39 1.02.39 1.41 0L17.3 9.7a.996.996 0 10-1.41-1.41z',
                'announcement' => 'M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z',
                'warning' => 'M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z',
                'cookie' => 'M21.95 13.04c-1.66 0-3.17-.56-4.41-1.5-.7-.53-1.62-.26-1.82.6-.32 1.37-.9 2.61-1.7 3.69-.6.8-1.77.72-2.13-.23-.27-.71-.62-1.37-1.03-1.98-.56-.89-1.84-.78-2.29.17-.75 1.59-1.82 2.96-3.14 4.08-.82.68-1.92.17-2.02-.89-.06-.57-.16-1.13-.3-1.68-.21-.86-1.37-1.16-1.96-.53C.48 15.63 0 16.78 0 18c0 3.31 2.69 6 6 6 2.8 0 5.17-1.95 5.84-4.57.17-.67.92-.95 1.47-.53 1.25.96 2.8 1.5 4.45 1.43 3.42-.14 6.24-3.04 6-6.47-.07-.98-.76-1.82-1.81-1.82zM12 6c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3z',
                'newsletter' => 'M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z',
                'donation' => 'M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'
            );
            $icon = isset($icons[$type]) ? $icons[$type] : $icons['adblock'];
            ?>
            <div id="popup-overlay" class="popup-hidden popup-theme-<?php echo esc_attr($theme); ?> popup-position-<?php echo esc_attr($position); ?> popup-animation-<?php echo esc_attr($animation); ?> popup-type-<?php echo esc_attr($type); ?>">
                <div class="popup-container">
                    <?php if ($showX): ?>
                        <button class="popup-close-x" aria-label="Kapat">&times;</button>
                    <?php endif; ?>
                    <div class="popup-icon">
                        <svg viewBox="0 0 24 24"><path d="<?php echo $icon; ?>"/></svg>
                    </div>
                    <h2 class="popup-title"><?php echo wp_kses_post($headline); ?></h2>
                    <?php if ($desc): ?>
                        <p class="popup-desc"><?php echo wp_kses_post($desc); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($advList)): ?>
                        <ul class="popup-list">
                            <?php foreach ($advList as $i => $item): ?>
                                <li class="<?php echo $i === 0 ? 'highlighted' : ''; ?>">
                                    <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                    <span><?php echo esc_html($item); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <button id="popup-close-btn" class="popup-button" disabled>
                        <?php echo esc_html($btnText); ?>
                    </button>
                </div>
            </div>
            <?php
        }
    }
    new ReklamUyarisiPlugin();
}
