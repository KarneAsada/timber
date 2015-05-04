<?php

/*
 * Timber printer that outputs log to StdErr
 */
include_once 'class.TimberPrinterInterface.inc';

class LogPrinterStdErr implements TimberPrinterInterface {

  public function __construct( $config = null ) {
    // No config for std error
  }

  public function output( $level, $tag, $message, $dump ) {

    $output = Timber::$levelMap[$level];
    if ($tag) $output .= " [" . $tag . "]";
    $output .=  ": " . $message;

    // Output vars
    foreach ($dump as $var) {
      $output .= "\n" . Timber::prettyPrint($var);
    }

    error_log($output);
  }

}
?>
