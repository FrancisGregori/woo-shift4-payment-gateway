<?php
/**
 * Shift4 Payment Gateway Autoloader.
 */

defined('ABSPATH') || exit;

/**
 * Autoloader class.
 */
class WC_Shift4_Autoloader
{

    /**
     * Path to the includes directory.
     *
     * @var string
     */
    private $include_path = '';

    /**
     * The Constructor.
     */
    public function __construct()
    {
        if (function_exists('__autoload')) {
            spl_autoload_register('__autoload');
        }

        spl_autoload_register(array($this, 'autoload'));

        $this->include_path = untrailingslashit(plugin_dir_path(SHITF4_PLUGIN_FILE)).'/api/';
    }

    /**
     * Take a class name and turn it into a file name.
     *
     * @param  string $class Class name.
     * @return string
     */
    private function get_file_name_from_class($class)
    {
        $file = array_reverse(explode('\\', $class));

        return str_replace('_', '-', $file[0]) . '.php';
    }

    /**
     * Include a class file.
     *
     * @param  string $path File path.
     * @return bool Successful or not.
     */
    private function load_file($path)
    {
        if ($path && is_readable($path)) {
            include_once $path;
            return true;
        }
        return false;
    }

    /**
     * Auto-load WC classes on demand to reduce memory consumption.
     *
     * @param string $class Class name.
     */
    public function autoload($class)
    {

        if (false === strpos(strtolower($class), 'woo_shift4_payment_gateway')) {
            return;
        }

        $file = $this->get_file_name_from_class($class);


        $this->load_file($this->include_path . $file);

    }
}

new WC_Shift4_Autoloader();
