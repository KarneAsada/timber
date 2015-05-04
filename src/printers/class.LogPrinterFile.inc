<?php

/*
 * Timber printer that outputs log to file
 *
 * Options:
 *  - file: name of file to output log to
 */
include_once 'class.TimberPrinterInterface.inc';

class LogPrinterFile implements TimberPrinterInterface {

  private $logFile = null;

  public function __construct( $config = null ) {

    // Set file for appending
    if (isset($config["file"])) {
      $this->logFile = $config["file"];
    } else {
      error_log("Timber LogPrinterFile requires a file to print to.");
    }
  }

  public function output( $level, $tag, $message, $dump ) {

    if ( ! $this->logFile ) return;

    // Eastern Timezone
    $date = new DateTime(date("Y-m-d H:i:s"),  new DateTimeZone(date_default_timezone_get()));
    $date->setTimezone(new DateTimezone('America/New_York'));
    $timeStamp = $date->format("Y-m-d H:i:s");

    $output  = "(" . $timeStamp . " - " . getmypid() . ") ";
    $output .= Timber::$levelMap[$level];
    if ($tag) $output .= " [" . $tag . "]";
    $output .=  ": " . $message;

    // Output vars
    if ( ! is_array( $dump ) ) {
      $dump = array( $dump );
    }

    foreach ($dump as $var) {
      $output .= "\n" . Timber::prettyPrint($var);
    }

    // Add new line
    $output .= "\n";

    // Flag indicating that the file is new
    $isNew = !file_exists( $this->logFile );

    try {
      file_put_contents( $this->logFile, $output, FILE_APPEND | FILE_USE_INCLUDE_PATH );
    } catch (Exception $e) {
      error_log(__CLASS__." failed to print to file: ".$e->getMessage());
    }
  }

}
?>
