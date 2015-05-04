# Timber
A logging class for PHP that allows for environment based
configuration.

Timber uses four log
levels: debug, warn, error, and fatal.  Whether the
logs are outputted depends on a combination of the
current environment (dev, prod, etc) and the config
setting that the logger is instantiated with.  The
logger's constructor takes one or more tokens that
represent it's config setting.  Additional tokens cause
the logger to output logs in additional configurations.

Config files are located in the directory set via the
TIMBER_CONFIGS environmental variable.  Based on
these settings, the logger determines whether to output
the log and what method to output (stderr, email, etc).

The logger is accessed via methods named after each
log level.  For example warn() outputs a
warn log.

The logger offers an additional feature to output log
messages to http headers compatible with the Firebug
plugin FirePHP.

## Installation with composer
Add the following to your composer.json
```
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/karneasada/timber"
        }
    ],

    "require" : {
      "karneasada/timber":"dev-master"
  }
}
```

Create a ```.env``` file in your root directory and add a value for ```ENVIRONMENT``` and ```TIMBER_CONFIGS```

## Usage
```
   $logger = Timber::instance(); // Default settings
   $logger->debug("A simple debug log");
   $logger->warn("warning with objects to dump", $string, $array);
   $logger->fatal("fatality!");

   $logger2 = Timber::instance("AUTH"); // Settings for authentication logs
   $logger2->error("An authentication error log");

   $logger3 = Timber::instance("AUTH", "EMAIL"); // Log to multiple outputs
   $logger3->error("An authentication error log and via email");
```
