<?php
/**
 * Prod config file for Timber
 */

return array(

  // Default Config
  "DEFAULT" => array(

    "level"     => Timber::FATAL,
    "backtrace" => false,
    "firephp"   => false,
  ),

  // FirePHP example
  "FIREPHP" => array(

    "level"     => Timber::DEBUG,
    "tag"       => "FirePHP",
    "backtrace" => true,
    "firephp"   => true,
  ),

  // File example
  "FILE" => array(

    "level"     => Timber::DEBUG,
    "tag"       => "File",
    "printer"   => "LogPrinterFile",
    "printer_options" => array("file" => $_SERVER["DOCUMENT_ROOT"]."/logs/timber.log"),
  ),

  // Email example
  "EMAIL" => array(

    "level"     => Timber::DEBUG,
    "tag"       => "Email",
    "backtrace" => true,
    "printer"   => "LogPrinterEmail",
    "printer_options" => array("emails" => "timber@mailinator.com", "from"=>"timber@mailinator.com"),
  ),

);

?>
