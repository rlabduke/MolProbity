<?php
// CONFIG.PHP
//
// Configuration info for MolProbity.
//
// This file controls paths to working space and to binaries.
// Edit this file to match your system configuration.

// MP_BIN_PATH
//  Directory(s) where all MolProbity-specific binary
//  executables are stored. Remember, apache must
//  have execute permission for these.
//
//  Does not need to include the bin/, bin/macosx/,
//  and/or bin/linux-rh73 directories -- these are
//  included automatically as appropriate.
//
//  These directories have highest precedence of all.
//
//  Default: ""
//  Example: "/usr/local/php/bin:/opt/j2/bin"
//  Full absolute paths only -- no relative ones!
define("MP_BIN_PATH", "/usr/local/php/bin:/opt/j2/bin");

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
define("MP_DEFAULT_TIMEZONE", 'EST');

// How long a session can go unused, in seconds
define("MP_SESSION_LIFETIME", 60*60*24*14); // 14 days

// How large a session can grow, in bytes
define("MP_SESSION_MAX_SIZE", 200*1024*1024); // 200 Mb


// Don't change this! It will break user bookmarks.
define("MP_SESSION_NAME", "MolProbSID");
define("MP_VERSION", "3pre8");


?>
