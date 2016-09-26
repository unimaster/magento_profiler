<?php

$mageRoot      = MAGENTO_ROOT . DIRECTORY_SEPARATOR;
$mageVarDir    = $mageRoot . 'var' . DIRECTORY_SEPARATOR;
$mageFile      = $mageRoot . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';
$mageMd5        = md5_file($mageFile);
$mageCacheFile = $mageVarDir . DIRECTORY_SEPARATOR . 'MageDev-' . $mageMd5 . '.php';

if (!file_exists($mageCacheFile)) {
    $mageCode = file_get_contents($mageFile);

    $replace  = [
        'final class Mage'                                             => 'class MageOriginal',
        ' private '                                                    => ' protected ',
        "define('BP', dirname(dirname(__FILE__)));"                    => '',
        "Mage::register('original_include_path', get_include_path());" => '$originalIncludePath = get_include_path();',
        "Mage::registry('original_include_path')"                      => '$originalIncludePath',
        'new Mage_Core_Model_App()'                                    => 'new Ecocode_Profiler_Model_AppDev()',
        'self::printException($e);'                                    => 'throw $e;'
    ];
    $mageCode = str_replace(array_keys($replace), array_values($replace), $mageCode);

    $replace  = [
        '/(?<!= )self::/' => 'static::'
    ];
    $mageCode = preg_replace(array_keys($replace), array_values($replace), $mageCode);

    file_put_contents($mageCacheFile, $mageCode);
}

require_once $mageCacheFile;

class Mage extends MageOriginal
{
    protected static $_logs = [];

    public static function terminate()
    {
        self::dispatchEvent('mage_terminate');
    }

    public static function log($message, $level = null, $file = '', $forceLog = false)
    {
        $level = is_null($level) ? Zend_Log::DEBUG : $level;
        $file  = empty($file) ? 'system.log' : $file;

        self::$_logs[] = [$file, $level, $message];

        return parent::log($message, $level, $file, $forceLog);
    }

    public static function run($code = '', $type = 'store', $options = [])
    {
        try {
            ob_start();
            ob_start();
            parent::run($code, $type, $options);
            ob_end_flush();
        } catch (Exception $e) {
            ob_clean();
            throw $e;
        }
    }


    public static function getLogEntries()
    {
        return self::$_logs;
    }

    public static function dispatchDebugEvent($name, array $data = [])
    {
        $data['debug'] = true;
        return parent::dispatchEvent($name, $data); // TODO: Change the autogenerated stub
    }
}
