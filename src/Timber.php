<?php
/**
 * Timber - a logger for PHP
 *
 * A class that consolidates logging using four log
 * levels: debug, warn, error, and fatal.  Whether the
 * logs are outputted depends on a combination of the
 * current environment (dev, prod, etc) and the config
 * setting that the logger is instantiated with.  The
 * logger's constructor takes one or more tokens that
 * represent it's config setting.  Additional tokens cause
 * the logger to output logs in additional configurations.
 *
 * Config files are located in the directory set via the
 * TIMBER_CONFIGS environmental variable.  Based on
 * these settings, the logger determines whether to output
 * the log and what method to output (stderr, email, etc).
 *
 * The logger is accessed via methods named after each
 * log level.  For example warn() outputs a
 * warn log.
 *
 * The logger offers an additional feature to output log
 * messages to http headers compatible with the Firebug
 * plugin FirePHP.
 *
 * Usage:
 *
 *    $logger = Timber::instance(); // Default settings
 *    $logger->debug("A simple debug log");
 *    $logger->warn("warning with objects to dump", $string, $array);
 *    $logger->fatal("fatality!");
 *
 *    $logger2 = Timber::instance("AUTH"); // Settings for authentication logs
 *    $logger2->error("An authentication error log");
 *
 *    // Static method
 *    Timber::instance('BILLING')->debug('A billing log');
 *
 * @author karnage@gmail.com
 * @link https://github.com/KarneAsada/timber.git
 */

class Timber {

  const CONFIG = 'config/Timber.config.php';

  const DEBUG = 100;
  const WARN  = 200;
  const ERROR = 300;
  const FATAL = 400;

  public static $levelMap = array(
      Timber::DEBUG => "DEBUG",
      Timber::WARN  => "WARN",
      Timber::ERROR => "ERROR",
      Timber::FATAL => "FATAL",
      );

  private $logger;
  private $config;
  private $printer;
  private $firephp;

  // For holding additional loggers
  // when constructor is passed multple tokens
  private $children = array();

  // Registry for singleton factory
  private static $registry = array();

  /**
   * Create Timber instance
   * Optionally pass one or more tokens referencing config settings
   *
   * @param array config
   */
  public function __construct( $config=null ) {

    // Load dotenv - assumes root/vendor/karneasada/timber/src
    Dotenv::load(dirname(dirname(dirname(dirname(dirname(__FILE__))))));

    // Require the ENVIRONMENT variable
    Dotenv::required(array('ENVIRONMENT', 'TIMBER_CONFIGS'));

    $this->setConfig( $config );

    // If multiple configs were passed, load those as additonal loggers
    $extraConfigs = func_get_args();
    $extraConfigs = array_slice($extraConfigs, 1);
    foreach( $extraConfigs as $extraConfig ) {

      // Array override current configuration
      if (is_array($extraConfig)) {
        $this->overrideConfig( $extraConfig );

      // Tokens get added as children
      } else {
        $this->addLogger( $extraConfig );
      }
    }
  }

  /**
   * Statically creates a Timber instance and registers it
   * for later use.  A Singleton Factory pattern.
   *
   * @param array config
   */
  public static function instance( $config=null ) {

    $configs  = func_get_args();
    $token    = serialize( $configs );

    // Create a new instance, if none exists, and store
    if ( ! isset(self::$registry[$token]) ) {
      $className = __CLASS__;
      $logger = new $className( $config );

      // Create children for other tokens
      foreach( array_slice($configs, 1) as $conf ) {

        // Array override current configuration
        if (is_array($conf)) {
          $logger->overrideConfig( $conf );

        // Tokens get added as children
        } else {
          $logger->addLogger( $conf );
        }
      }

      self::$registry[$token] = $logger;
    }

    return self::$registry[$token];
  }


  // When the logger is constructed with multiple tokens, additional
  // logger objects are created and added to the children array.
  public function addLogger( $config ) {
    $className = __CLASS__;
    $child = new $className( $config );
    $child->setFirePHP(false);

    $this->children[] = $child;
  }

  /*
   * Debug (lowest) log level
   * @param string $message
   * @param mixed dump variables
   */
  public function debug( $message ) {
    $args = func_get_args();
    $this->log(self::DEBUG, $message, array_slice($args, 1) );
  }

  /*
   * Warn log level
   * @param string $message
   * @param mixed dump variables
   */
  public function warn( $message ) {
    $args = func_get_args();
    $this->log(self::WARN, $message, array_slice($args, 1) );
  }

  /*
   * Error log level
   * @param string $message
   * @param mixed dump variables
   */
  public function error( $message ) {
    $args = func_get_args();
    $this->log(self::ERROR, $message, array_slice($args, 1) );
  }

  /*
   * Fatal log level
   * @param string $message
   * @param mixed dump variables
   */
  public function fatal( $message ) {
    $args = func_get_args();
    $this->log(self::FATAL, $message, array_slice($args, 1) );
  }

  /*
   * Send log message to printer(s)
   * @param int $level
   * @param string $errorMessage
   * @param array $varDump
   */
  public function log( $level, $errorMessage, $varDump = array() ) {

    // Exit if this log level has been silenced
    if ($level < $this->config["level"]) return;

    // Exit if there is no message
    if (empty($errorMessage)) return;

    $message = $errorMessage;

    // Append backtrace if turned on
    if ($this->config['backtrace']) {
      $backTrace = debug_backtrace();
      $message .= " in " . $backTrace[1]["file"] . " on line #"
                . $backTrace[1]["line"];

      if (isset($backTrace[2])) {
        $message .= " called from function \""
                  . $backTrace[2]["function"]
                  . "\" from line #"
                  . $backTrace[2]["line"]
                  . " of "
                  . $backTrace[2]["file"]
                  ;
      }
    }

    // Get output printer
    if (isset($this->printer)) {
      $this->printer->output( $level, $this->config["tag"], $message, $varDump );
    } else {
      error_log($message);
      $backTrace = debug_backtrace();
      $callee = $backTrace[1]["file"] . " on line #"
              . $backTrace[1]["line"];
      error_log('Timber is misconfigured in ' . $callee);
    }

    // Output error to Firebug with FirePHP
    if ($this->firephp) {
      if ( ! headers_sent() ) { // Prevent "headers already sent" error in Firebug library
        try {
          if ($level >= self::ERROR) {
            $this->firephp->error($message);
          } else if( $level >= self::WARN) {
            $this->firephp->warn($message);
          } else {
            $this->firephp->log($message);
          }
        } catch( Exception $e ) {
          error_log("Timber Firebug error: " . $e->getMessage());
        }
      }
    }

    // Send log to children
    foreach($this->children as $child) {
      $child->log( $level, $errorMessage, $varDump );
    }
  }

  // === SETTERS

  /**
   * Sets the config settings based on the passed token
   * @param string $configToken
   */
  public function setConfig( $configToken ) {

    // Load config
    $configData = include self::CONFIG;

    // Load config object
    $this->config = $configData["DEFAULT"];
    if (isset($configData[$configToken])) {
      $this->overrideConfig( $configData[$configToken] );
    } else {
      $this->_setupOutput();
    }
  }

  /**
   * Override values in config with passed array values
   *
   * @param array $configArray
   */
  public function overrideConfig( $configArray ) {

    $this->config = Timber::config_merge( $this->config, $configArray );
    $this->_setupOutput();
  }

  /*
   * Enables/Disables FirePHP output
   */
  public function setFirePHP( $enable = true ) {

    if ($enable) {
      //include_once dirname(__FILE__). "/firephp/FirePHP.class.php";
      $this->firephp = FirePHP::getInstance(true);
    } else {
      $this->firephp = null;
    }
  }

  // === PRIVATE

  /**
   * Load the config file
   */
  private function _loadConfig() {


  }

  /**
   * Setup the printer and firePHP for output
   */
  private function _setupOutput() {

    // Load Printer
    $printerFile = dirname(__FILE__) . "/printers/class." . $this->config["printer"] . ".inc";
    if (file_exists($printerFile)) {
      include_once $printerFile;
      $printerOptions = isset($this->config["printer_options"]) ? $this->config["printer_options"] : null;
      $this->printer = new $this->config["printer"]( $printerOptions );
    }

    // Enable/Disable FirePHP
    $this->setFirePHP( !empty($this->config["firephp"]) );
  }


  // === UTILITIES


  /**
   * Similar to array_merge_recursive, except it overwrites instead of appending
   *
   * @params arrays to merge
   * @return array merged
   */
  public static function config_merge() {
    $arrays = func_get_args();

    if (count($arrays) < 2) {
      return count($arrays) == 1 ? $arrays[0] : array();
    }
    $merged = array();
    while ($arrays) {
      $array = array_shift($arrays);
      if ($array && is_array($array)) {
        foreach ($array as $key => $value) {
          if (is_string($key)) {
            if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key])) {
              $merged[$key] = call_user_func(__METHOD__, $merged[$key], $value);
            } else {
              $merged[$key] = $value;
            }
          } else {
              $merged[] = $value;
          }
        }
      }
    }
    return $merged;
  }

  /**
   * Returns a pretty printed display of a variable
   */
  public static function prettyPrint( $var ) {
    if (is_bool($var)) {
        return $var ? 'true' : 'false';
    } else if (is_array($var) || is_object($var) || is_null($var)) {
        return print_r($var, true);
    } else {
        return $var;
    }
  }

}
?>
