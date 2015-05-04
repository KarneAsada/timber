<?php
/**
 * Base config file for Timber
 *
 * Contains defaults and loads config file for appropriate environment
 */


return Timber::config_merge(array(

  // Default Default Config
  "DEFAULT" => array(

    "level"     => Timber::ERROR,
    "tag"       => "",
    "backtrace" => false,
    "firephp"   => false,
    "printer"   => "LogPrinterStdErr",
  ),

),  (getenv('ENVIRONMENT')
      && getenv('TIMBER_CONFIGS')
      && file_exists(getenv('TIMBER_CONFIGS') . '/Timber.' . getenv('ENVIRONMENT') . '.config.php'))
    ? include getenv('TIMBER_CONFIGS') . '/Timber.' . getenv('ENVIRONMENT') . '.config.php'
    : array()
);

?>
