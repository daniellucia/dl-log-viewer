<?php

/**
 * Plugin Name:       Log viewer
 * Description:       Easily view log files
 * Version:           0.0.5
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Daniel Lucia
 * Author URI:        http://www.daniellucia.es/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        http://www.daniellucia.es/
 * Text Domain:       dl-log-viewer
 * Domain Path:       /languages
 */

/*
Copyright (C) 2025  Daniel Lucia (https://daniellucia.es)

Este programa es software libre: puedes redistribuirlo y/o modificarlo
bajo los términos de la Licencia Pública General GNU publicada por
la Free Software Foundation, ya sea la versión 2 de la Licencia,
o (a tu elección) cualquier versión posterior.

Este programa se distribuye con la esperanza de que sea útil,
pero SIN NINGUNA GARANTÍA; ni siquiera la garantía implícita de
COMERCIABILIDAD o IDONEIDAD PARA UN PROPÓSITO PARTICULAR.
Consulta la Licencia Pública General GNU para más detalles.

Deberías haber recibido una copia de la Licencia Pública General GNU
junto con este programa. En caso contrario, consulta <https://www.gnu.org/licenses/gpl-2.0.html>.
*/

use DL\LogViewer\Plugin;

defined('ABSPATH') || exit;

require_once __DIR__ . '/vendor/autoload.php';

define('DL_LOG_VIEWER_VERSION', '0.0.5');
define('DL_LOG_VIEWER_FILE', __FILE__);

add_action('plugins_loaded', function () {

    load_plugin_textdomain('dl-log-viewer', false, dirname(plugin_basename(DL_LOG_VIEWER_FILE)) . '/languages');

    $plugin = new Plugin();
});
