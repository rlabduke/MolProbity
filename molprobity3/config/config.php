<?php
// CONFIG.PHP
//
// Configuration info for MolProbity.
//
// This file controls paths to working space and to binaries.
// Edit this file to match your system configuration.

// MP_EMAIL_WEBMASTER
//  An email address for the owner of this site.
//  If you run this code, set this to your address.
define("MP_EMAIL_WEBMASTER", "webmaster@$_SERVER[SERVER_NAME]");

// MP_BIN_PATH
//  Directory(s) where all MolProbity-specific binary
//  executables are stored. Remember, apache must
//  have execute permission for these.
//
//  Does not need to include the bin/, bin/macosx/,
//  and/or bin/linux directories -- these are
//  included automatically as appropriate.
//
//  These directories have highest precedence of all.
//
//  Default: ""
//  Example: "/usr/local/php/bin:/opt/j2/bin"
//  Full absolute paths only -- no relative ones!
define("MP_BIN_PATH", "/usr/local/php5/bin:/opt/j2/bin");

// MP_REDUCE_HET_DICT
//  Path to Reduce's heterogen dictionary
//  Default: /usr/local/reduce_het_dict.txt
define("MP_REDUCE_HET_DICT", MP_BASE_DIR."/lib/reduce_het_dict.txt");

// Limit for Reduce's -limit flag
define("MP_REDUCE_LIMIT", 10000);

// MP_UMASK
//  This is a standard Unix file umask, which means it
//  specifies which bits WON'T be set in the file permissions.
//  This gets applied to all files created by MolProbity.
//
//  Default is 0000.
//  For highest security, use 0077.
define("MP_UMASK", 0);

// Default timezone. See lib/timezones.php for allowed keys.
// If left undefined (commented out), MolProbity can usually guess correctly.
//define("MP_DEFAULT_TIMEZONE", 'EST');

// How long a session can go unused, in seconds
define("MP_SESSION_LIFETIME", 60*60*24*14); // 14 days

// How large a session can grow, in bytes
define("MP_SESSION_MAX_SIZE", 200*1000*1000); // 200 Mb

// Kinemages above this size will be gzipped (in most cases).
// To disable, set to a very large value, like 100 Gb.
define("MP_KIN_GZIP_THRESHOLD", 1*1000*1000); // 1 Mb

// Alternating colors for striped tables
define("MP_TABLE_ALT1", "#ffffff");
define("MP_TABLE_ALT2", "#f0f0f0");
// Highlight color for striped tables
define("MP_TABLE_HIGHLIGHT", "#cc9999");

// Subdirectories for things to be stored in.
define("MP_DIR_SYSTEM", "system");              // Session data, lab notebook, etc.
define("MP_DIR_WORK", "temporary");             // Temporary working files
define("MP_DIR_MODELS", "coordinates");         // PDB files
define("MP_DIR_EDMAPS", "electron_density");    // electron density
define("MP_DIR_TOPPAR", "dictionaries");        // het dicts, etc. (named for CNS TOPology and PARameter)
define("MP_DIR_KINS", "kinemages");             // kinemage visualizations
define("MP_DIR_RAWDATA", "raw_data");           // raw (text) data like .tab files
define("MP_DIR_CHARTS", "charts");              // CSV files for HTML/Excel tables, PDFs, etc

//============================================================================
//===  PLEASE DON'T CHANGE THINGS BELOW THIS LINE  ===========================
//============================================================================

// MP_EMAIL_AUTHOR
//  The email address of the (current) author and maintainer
//  of the MolProbity source code. Please DO NOT modify this
//  entry; this is how we get feedback about bugs, etc.
//  Change MP_EMAIL_WEBMASTER instead.
define("MP_EMAIL_AUTHOR", "moler@kinemage.biochem.duke.edu,iwd@duke.edu");

// Don't change this! It will break user bookmarks.
define("MP_SESSION_NAME", "MolProbSID");

// Current "internal reference" version number. Please DO NOT change.
define("MP_VERSION", "3beta20");

?>
