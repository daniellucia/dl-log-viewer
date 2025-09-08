<?php

namespace DL\LogViewer;

class Plugin
{

    private  $log_file;
    private $level_colors;
    private $lines;


    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('dl_log_viewer_before_filters', [$this, 'render_filters_form'], 20);
        add_action('dl_log_viewer_before_filters', [$this, 'render_pagination'], 10);

        $this->log_file = WP_CONTENT_DIR . '/debug.log';
        $this->level_colors = [
            'NOTICE' => 'color: #1d72b8;',
            'WARNING' => 'color: #d9822b;',
            'ERROR'   => 'color: #d92b2b;',
        ];

        $this->lines = isset($_GET['lines']) ? intval($_GET['lines']) : 100;
    }

    /**
     * Muestra la página del visor de logs.
     * @return void
     * @author Daniel Lucia
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'tools.php',
            __('Log Viewer', 'dl-log-viewer'),
            __('Log Viewer', 'dl-log-viewer'),
            'manage_options',
            'dl-log-viewer',
            [$this, 'render_log_viewer_page']
        );
    }

    /**
     * Renderiza la página del visor de logs.
     * @return void
     * @author Daniel Lucia
     */
    public function render_log_viewer_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $log_lines = $this->read_log_file($this->log_file, $this->lines);
        ?>
        <div class="wrap">

            <h1><?php echo esc_html(__('Log Viewer', 'dl-log-viewer')); ?></h1>

            <div style="display: flex; gap: 30px;justify-content: space-between;">
                <?php do_action('dl_log_viewer_before_filters'); ?>
            </div>

             <?php
            if (empty($log_lines)) {
                echo esc_html(__('Log file is empty or does not exist.', 'dl-log-viewer'));
            } else {

                ?>
                <table style="width:100%; border-collapse: collapse; margin-top: 20px;" class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th style="width: 160px;"><?php echo esc_html(__('Date', 'dl-log-viewer')); ?></th>
                            <th style="width: 84px;"><?php echo esc_html(__('Level', 'dl-log-viewer')); ?></th>
                            <th><?php echo esc_html(__('Message', 'dl-log-viewer')); ?></th>
                            <th style="width: 50px;"><?php echo esc_html(__('Line', 'dl-log-viewer')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        
                        foreach ($log_lines as $line) {

                            $level_filter = isset($_GET['level']) ? strtoupper(sanitize_text_field($_GET['level'])) : '';
                            $file_filter  = isset($_GET['file']) ? sanitize_text_field($_GET['file']) : '';
                            $line_filter  = isset($_GET['line']) ? sanitize_text_field($_GET['line']) : '';

                            $pattern = '/^\[(.*?)\]\s+PHP\s+(\w+):\s+(.*)\s+in\s+(.*?)\s+on line\s+(\d+)/i';
                            if (preg_match($pattern, $line, $matches)) {
                                $level = strtoupper($matches[2]);
                                $file  = $matches[4];
                                $lineNo = $matches[5];

                                if ($level_filter && $level !== $level_filter) {
                                    continue;
                                }
                                if ($file_filter && stripos($file, $file_filter) === false) {
                                    continue;
                                }
                                if ($line_filter && $lineNo !== $line_filter) {
                                    continue;
                                }
                            }

                            echo $this->format_line($line);
                        }

                        ?>
                    </tbody>
                </table>
                <?php                
            }
            ?>

            <?php do_action('dl_log_viewer_after_filters'); ?>
        </div>
    <?php
    }

    /**
     * Formatea una línea del log para su visualización.
     * @param string $line
     * @return string
     * @author Daniel Lucia
     */
    private function format_line(string $line)
    {
        $line = trim($line);

        // 1. [fecha] PHP {nivel}: mensaje in archivo on line N
        // Modificado: ya no extraemos el nombre del fichero, solo mostramos el mensaje completo
        $pattern_php = '/^\[?(.*?)\]?\s+PHP\s+([^:]+):\s+(.*?)\s+on line\s+(\d+)\s*$/i';
        if (preg_match($pattern_php, $line, $matches)) {
            $date_raw   = $matches[1];
            $level_raw  = trim($matches[2]);
            $msg_raw    = $matches[3];
            $lineNo     = $matches[4];

            $level_norm  = $this->normalize_level($level_raw);
            $level_style = $this->level_colors[$level_norm] ?? 'color: #555;';
            $msg_safe = function_exists('wp_kses_post') ? wp_kses_post($msg_raw) : esc_html($msg_raw);

            return sprintf(
                '<tr style="font-family: monospace; margin-bottom:6px;">
                    <td style="color:#999; vertical-align: top;">%s</td>
                    <td style="%s; vertical-align: top;">%s</td>
                    <td style="vertical-align: top;">%s</td>
                    <td style="color:#2a7d2a; vertical-align: top;">%s</td>
                </tr>',
                $this->format_date($date_raw),
                $level_style,
                esc_html($level_raw),
                $msg_safe,
                esc_html($lineNo)
            );
        }

        // 2. [fecha] {nivel}: mensaje (sin archivo ni línea)
        $pattern_level = '/^\[?(.*?)\]?\s+([A-Z ]+):\s+(.*)$/i';
        if (preg_match($pattern_level, $line, $matches)) {
            $date_raw  = $matches[1];
            $level_raw = trim($matches[2]);
            $msg_raw   = $matches[3];

            $level_norm  = $this->normalize_level($level_raw);
            $level_style = $this->level_colors[$level_norm] ?? 'color: #555;';
            $msg_safe = function_exists('wp_kses_post') ? wp_kses_post($msg_raw) : esc_html($msg_raw);

            return sprintf(
                '<tr style="font-family: monospace; margin-bottom:6px;">
                    <td style="color:#999; vertical-align: top;">%s</td>
                    <td style="%s; vertical-align: top;">%s</td>
                    <td style="vertical-align: top;" colspan="2">%s</td>
                </tr>',
                $this->format_date($date_raw),
                $level_style,
                esc_html($level_raw),
                $msg_safe
            );
        }

        // 3. [fecha] mensaje (sin nivel)
        $pattern_date = '/^\[?(.*?)\]?\s+(.*)$/';
        if (preg_match($pattern_date, $line, $matches)) {
            $date_raw = $matches[1];
            $msg_raw  = $matches[2];

            $msg_safe = function_exists('wp_kses_post') ? wp_kses_post($msg_raw) : esc_html($msg_raw);

            return sprintf(
                '<tr style="font-family: monospace; margin-bottom:6px;">
                    <td style="color:#999; vertical-align: top;">%s</td>
                    <td style="color:#555; vertical-align: top;"></td>
                    <td style="vertical-align: top;" colspan="2">%s</td>
                </tr>',
                $this->format_date($date_raw),
                $msg_safe
            );
        }

        // 4. Mensaje sin fecha (línea suelta)
        $msg_safe = function_exists('wp_kses_post') ? wp_kses_post($line) : esc_html($line);
        return sprintf(
            '<tr><td colspan="4" style="color:#555; font-family: monospace;">%s</td></tr>',
            $msg_safe
        );
    }

    /**
     * Normaliza el texto del nivel a NOTICE / WARNING / ERROR
     * @param string $level
     * @return string
     */
    private function normalize_level(string $level): string
    {
        $l = strtolower($level);

        if (strpos($l, 'notice') !== false) {
            return 'NOTICE';
        }

        if (strpos($l, 'deprecated') !== false || strpos($l, 'strict') !== false || strpos($l, 'warning') !== false) {
            return 'WARNING';
        }

         if (strpos($l, 'parse') !== false || strpos($l, 'fatal') !== false || strpos($l, 'error') !== false) {
            return 'ERROR';
        }

        return 'NOTICE';
    }

    /**
     * Formatea una fecha para su visualización.
     * @param mixed $dateString
     * @return string
     * @author Daniel Lucia
     */
    private function format_date($dateString) {
        
        $timestamp = strtotime($dateString);

        if (!$timestamp) {
            return ''; 
        }

        $date_format = get_option('date_format'); 
        $time_format = get_option('time_format');

        $date = date_i18n($date_format, $timestamp);
        $time = date_i18n($time_format, $timestamp);

        return sprintf(
            '<div style="font-family: monospace; color:#999; line-height:1.4em;">
                <div>%s</div>
                <div>%s</div>
            </div>',
            esc_html($date),
            esc_html($time)
        );
    }

    /**
     * Muestra el formulario de filtros.
     * @return void
     * @author Daniel Lucia
     */
    public function render_filters_form() {

        $current_level = isset($_GET['level']) ? sanitize_text_field($_GET['level']) : '';
        $current_search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $current_line = isset($_GET['line']) ? sanitize_text_field($_GET['line']) : '';
        ?>

        <?php $this->check_debug_log_status(); ?>

        <form method="get" action="" style="margin-top:0;">
            <input type="hidden" name="page" value="dl-log-viewer">

            <label for="level"><?php echo esc_html(__('Level:', 'dl-log-viewer')); ?></label>
            <select id="level" name="level">
                <option value=""><?php echo esc_html(__('All', 'dl-log-viewer')); ?></option>
                <option value="NOTICE" <?php selected($current_level, 'NOTICE'); ?>>Notice</option>
                <option value="WARNING" <?php selected($current_level, 'WARNING'); ?>>Warning</option>
                <option value="ERROR" <?php selected($current_level, 'ERROR'); ?>>Error</option>
            </select>

            <label for="search" style="margin-left:10px;"><?php echo esc_html(__('Search:', 'dl-log-viewer')); ?></label>
            <input type="text" id="search" name="search" value="<?php echo esc_attr($current_search); ?>" placeholder="functions.php">

            <label for="line" style="margin-left:10px;"><?php echo esc_html(__('Line:', 'dl-log-viewer')); ?></label>
            <input type="text" id="line" name="line" value="<?php echo esc_attr($current_line); ?>" placeholder="6121">

            <input type="submit" value="<?php echo esc_attr(__('Apply Filters', 'dl-log-viewer')); ?>" class="button">
        </form>
        <?php
    }

    /**
     * Renderiza el formulario de paginación.
     * @return void
     * @author Daniel Lucia
     */
    public function render_pagination() {
        
        ?>
        <form method="get" action="" style="margin: 0;">
            <input type="hidden" name="page" value="dl-log-viewer">
            <label for="lines"><?php echo esc_html(__('Number of lines to display:', 'dl-log-viewer')); ?></label>
            <input type="number" id="lines" name="lines" value="<?php echo esc_attr($this->lines); ?>" min="1" max="1000">
            <input type="submit" value="<?php echo esc_attr(__('View Log', 'dl-log-viewer')); ?>" class="button button-primary">
        </form>
        <?php
    }

    /**
     * Lee un archivo de log y devuelve las últimas n líneas.
     * @param mixed $file
     * @param mixed $lines
     * @return array<bool|string>
     * @author Daniel Lucia
     */
    function read_log_file($file, $lines = 100)
    {
        if (!file_exists($file)) {
            return [];
        }

        $f = fopen($file, "r");
        $cursor = -1;
        $lines_array = [];

        fseek($f, $cursor, SEEK_END);
        $char = fgetc($f);

        while ($lines > 0 && ftell($f) > 1) {
            if ($char === "\n") {
                $lines--;
                if ($lines == 0) {
                    break;
                }
            }
            $cursor--;
            fseek($f, $cursor, SEEK_END);
            $char = fgetc($f);
        }

        while (!feof($f)) {
            $lines_array[] = fgets($f);
        }

        fclose($f);
        return array_reverse($lines_array);
    }

   private function check_debug_log_status() {
        if (!defined('WP_DEBUG') || WP_DEBUG !== true) {
            echo '<div class="notice notice-error"><p><strong>WP_DEBUG</strong> no está activado en tu instalación de WordPress.</p></div>';
            return;
        }

        if (!defined('WP_DEBUG_LOG') || WP_DEBUG_LOG !== true) {
            echo '<div class="notice notice-warning"><p><strong>WP_DEBUG_LOG</strong> no está activado. No se generarán logs en <code>wp-content/debug.log</code>.</p></div>';
        }
    }
 
}
