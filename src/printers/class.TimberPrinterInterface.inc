<?php

/*
 * An interface for printing classes that out put log messages.
 * Every class that implements this must have the following methods:
 *
 * - __construct( string config = null )
 *    The constructor takes an optional array of config options that is
 *    stored in the main config paraments "printer_options."
 *
 * - output( int level, string tag, string message, array dump = null )
 *    This method outputs the log.  The printer class determines what is
 *    done with each of the required parameters:
 *      level:    an int that represents one of the Timber levels
 *                (DEBUG, WARN, ERROR, FATAL)
 *      tag:      a string that contains a tag to describe the log
 *      message:  the log message
 *      dump:     an optional array of objects to include in the log output
 */
interface TimberPrinterInterface {

  public function __construct( $config = null );
  public function output( $level, $tag, $message, $dump );
}


?>
