<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file takes a 'raw' PDB file and prepares it to be a new model for
    the session.

INPUTS (via $_SESSION['bgjob']):
    tmpPdb          the (temporary) file where the upload is stored.
    origName        the name of the file on the user's system.
    isCnsFormat     true if the user thinks he has CNS atom names

OUTPUTS (via $_SESSION['bgjob']):
    Adds a new entry to $_SESSION['models'].
    newModel        the ID of the model just added

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// PHP's working dir is set by the script that is begins execution with.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(getcwd().'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
    require_once(MP_BASE_DIR.'/lib/pdbstat.php');
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

# MAIN - the beginning of execution for this page
############################################################################
// Try stripping file extension
if(preg_match('/^(.+)\.(pdb|xyz|ent)$/i', $_SESSION['bgjob']['origName'], $m))
    $id = $m[1];
else
    $id = $_SESSION['bgjob']['origName'];

// Make sure this is a unique name
while( isset($_SESSION['models'][$id.$serial]) )
    $serial++;
$id .= $serial;

// Create directory
$modelDir = $_SESSION['dataDir'].'/'.$id;
mkdir($modelDir, 0777);

// Process file - this is the part that matters
$infile     = $_SESSION['bgjob']['tmpPdb'];
$outname    = $id.'.pdb';
$outpath    = $modelDir.'/'.$outname;
preparePDB($infile, $outpath, $_SESSION['bgjob']['isCnsFormat']);

// Create the model entry
$_SESSION['models'][$id] = array(
    'id'        => $id,
    'dir'       => $modelDir,
    'prefix'    => $id.'-',
    'pdb'       => $outname,
    'stats'     => pdbstat($outpath),
    'history'   => 'Original file uploaded by user'
);

$_SESSION['bgjob']['newModel'] = $id;

// Clean up temp files
unlink($infile);
############################################################################
// Clean up and go home
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
