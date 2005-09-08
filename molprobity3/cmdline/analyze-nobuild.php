<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This provides a simple demonstration of running MolProbity from a scripted interface.
    We create a session, "upload" a file, run the basic analysis, harvest the
    results as a ZIP file, and then destroy the session.

INPUTS (via $_SERVER['argv']):
    the path to the PDB file to process
    the path to write the ZIP file to

OUTPUTS:

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
    require_once(MP_BASE_DIR.'/lib/analyze.php');
// 5. Set up reasonable values to emulate CLI behavior if we're CGI
    set_time_limit(0); // don't want to bail after 30 sec!

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################
// First argument is the name of this script...
if(is_array($_SERVER['argv'])) foreach(array_slice($_SERVER['argv'], 1) as $arg)
{
    if(!isset($infile))
        $infile = $arg;
    elseif(!isset($outfile))
        $outfile = $arg;
    else
        die("Too many or unrecognized arguments: '$arg'\n");
}

if(! isset($infile))
    die("No input file specified.\n");
elseif(! (is_file($infile) && filesize($infile) > 0))
    die("Input file '$infile' does not exist or has size 0.\n");
elseif(! isset($outfile))
    die("No output file specified.\n");
    
echo "$infile -> $outfile\n";

// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
// This way, we don't create a session unless our input is semi-valid.
mpStartSession(true); // create a new session

$modelID = addModelOrEnsemble($infile, basename($infile));
if(isset($_SESSION['ensembles'][$modelID]))
{
    echo "$infile contains multiple MODELs; aborting...\n";
}
else
{
    echo("Added '$infile' to session ".session_id()." as model '$modelID'\n");
    
    $opts = array(
        'doAAC'             => true,
        'showHbonds'        => true,
        'showContacts'      => true,
        'doRama'            => true,
        'doRota'            => true,
        'doCbDev'           => true,
        'doBaseP'           => true,
        'doSummaryStats'    => false,
        'doMultiKin'        => true,
        'multiKinExtras'    => true,
        'doMultiChart'      => false,
        'doRemark42'        => false,
    );
    runAnalysis($modelID, $opts);
    
    $tmpfile = $_SESSION['dataDir'] . '/' . makeZipForSession();
    copy($tmpfile, $outfile);
}
############################################################################
// Clean up and go home
mpDestroySession();
?>
