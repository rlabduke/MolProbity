<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Runs all the analysis tasks -- CB deviation, clashes, Ramachandran, etc.

INPUTS (via $_SESSION['bgjob']):
    model           ID code for model to process

OUTPUTS (via $_SESSION['bgjob']):
    paramName       description of parameter

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// PHP's working dir is set by the script that is begins execution with.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(getcwd().'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/analyze.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    session_id( $_SERVER['argv'][1] );
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    #mpSessReadOnly();
// 5. Set up reasonable values to emulate CLI behavior if we're CGI
    set_time_limit(0); // don't want to bail after 30 sec!

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
$model = $_SESSION['models'][ $_SESSION['bgjob']['model'] ];

// C-betas
$outfile = "$model[dir]/$model[prefix]cbdev.data";
runCbetaDev("$model[dir]/$model[pdb]", $outfile);
$cbdev = loadCbetaDev($outfile);

// Rotamers
$outfile = "$model[dir]/$model[prefix]rota.data";
runRotamer("$model[dir]/$model[pdb]", $outfile);
$rotamer = loadRotamer($outfile);

// Ramachandran
$outfile = "$model[dir]/$model[prefix]rama.data";
runRamachandran("$model[dir]/$model[pdb]", $outfile);
$rama = loadRamachandran($outfile);

// Clashes
$outfile = "$model[dir]/$model[prefix]clash.data";
runClashlist("$model[dir]/$model[pdb]", $outfile);
$clashlist = loadClashlist($outfile);

############################################################################
// Clean up and go home
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
