<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file is intended as a reference for MolProbity PHP background scripts.

INPUTS (via $_SESSION['bgjob']):
    paramName       description of parameter

OUTPUTS (via $_SESSION['bgjob']):
    paramName       description of parameter

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    session_id( $_SERVER['argv'][1] );
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    #mpSessReadOnly();
// 5. Set up reasonable values to emulate CLI behavior if we're CGI
    set_time_limit(0); // don't want to bail after 30 sec!
// 6. Record this PHP script's PID in case it needs to be killed.
    $_SESSION['bgjob']['processID'] = posix_getpid();
    mpSaveSession();

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################

/*
// Do a safety check on $script:
switch($script) {
    case "cass":
        $flag = "-cass";
        break;
    case "mchb":
        $flag = "-mchb";
        break;
    case "aasc":
        $flag = "-aasc";
        break;
    case "lots":
        $flag = "-lots";
        break;
    case "halfbonds":
        $flag = "-lots";
        $rainbow = FALSE; // the two are incompatible
        break;
    case "naba":
        $flag = "-naba";
        break;
    case "bestribbon":
        $flag = "-bestribbon";
        break;
    default:
        $flag = "-cass";
        break;
}

// Name the output file
// Given xxxx.pdb, write to xxxx-flag.kin
if( eregi("^.+\.pdb$", $pdb) ) {
    $kin = eregi_replace("^(.+)(\.pdb)$", "\\1$flag.kin", $pdb);
// Given foobar.file, write to foobar.file-flag.kin
} else {
    $kin = $pdb . "$flag.kin";
}

// Color ramp N --> C ?
if($rainbow) { $flag .= " -colornc"; }

if($script == "halfbonds")
    $cmd = "prekin $flag $working_dir/$pdb | php -f halfbonds.php > $working_dir/$kin";
else
    $cmd = "prekin $flag $working_dir/$pdb > $working_dir/$kin";

exec($cmd);
*/


############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
