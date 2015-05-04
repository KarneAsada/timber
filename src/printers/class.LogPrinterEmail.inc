<?php
/*
 * Timber printer that outputs log to email
 *
 * Options:
 *   - emails: list of email addresses
 */

include_once 'class.TimberPrinterInterface.inc';

class LogPrinterEmail implements TimberPrinterInterface {

  private $emailAddresses = array();
  private $fromAddress = '';

  public function __construct( $config = null ) {

    // Set email addresses
    if (isset($config["emails"])) {
      $this->emailAddresses = is_array($config["emails"])
                            ? $config["emails"]
                            : array($config["emails"]);
    } else {
      error_log("Timber LogPrinterEmail requires atleast one email address.");
    }
    if (isset($config["from"])) {
      $this->fromAddress = array($config["from"]);
    } else {
      error_log("Timber LogPrinterEmail requires a from email address.");
    }
  }

  public function output( $level, $tag, $message, $dump ) {

    if ( ! $this->emailAddresses ) return;

    $output = "<h1>" . Timber::$levelMap[$level];
    if ($tag) $output .= " [" . $tag . "]";
    $output .=  "</h1><h2> " . $message . "</h2>";

    // Output vars
    foreach ($dump as $var) {

      // Capture HTML from var_dump
      ob_start();
      var_dump($var);
      $output .= ob_get_contents();
      ob_end_clean();
    }

    // Send email
    try {
      mail(
        implode(", ", $this->emailAddresses),
        "Timber: "
          . Timber::$levelMap[$level]
          . ($tag ? "[".$tag."] " : '')
          . substr($message, 0, 20),
          str_replace("\n", "<br/>", $output),
          "From: ".$this->fromAddress
        );
    } catch (Exception $e) {
      error_log(__CLASS__." failed to send email: ".$e->getMessage());
    }
  }

}
?>
