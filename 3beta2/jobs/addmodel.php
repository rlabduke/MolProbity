<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file takes a 'raw' PDB file and prepares it to be a new model for
    the session.

INPUTS (via $_SESSION['bgjob']):
    tmpPdb          the (temporary) file where the upload is stored.
    origName        the name of the file on the user's system.
    pdbCode         the PDB or NDB code for the molecule
    (EITHER pdbCode OR tmpPdb and origName will be set)
    
    isCnsFormat     true if the user thinks he has CNS atom names
    ignoreSegID     true if the user wants to never map segIDs to chainIDs

OUTPUTS (via $_SESSION['bgjob']):
    Adds a new entry to $_SESSION['models'].
    newModel        the ID of the model just added, or null on failure

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
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

# MAIN - the beginning of execution for this page
############################################################################
if(isset($_SESSION['bgjob']['pdbCode']))
{
    $code = strtoupper($_SESSION['bgjob']['pdbCode']);
    
    if(preg_match('/^[0-9A-Z]{4}$/i', $code))
    {
        setProgress(array("pdb" => "Retrieve PDB file $code over the network"), "pdb");
        $tmpfile = getPdbModel($code);
    }
    else if(preg_match('/^[0-9A-Z]{6,10}$/i', $code))
    {
        setProgress(array("pdb" => "Retrieve NDB file $code over the network (takes more than 30 sec)"), "pdb");
        $tmpfile = getNdbModel($code);
    }
    else $tmpfile == null;
    
    if($tmpfile == null)
    {
        $_SESSION['bgjob']['newModel'] = null;
    }
    else
    {
        $id = addModel($tmpfile,
            "$code.pdb",
            $_SESSION['bgjob']['isCnsFormat'],
            $_SESSION['bgjob']['ignoreSegID']);
        
        $_SESSION['bgjob']['newModel'] = $id;
        
        // Clean up temp files
        unlink($tmpfile);
    }
}
else
{
    // Remove illegal chars from the upload file name
    $origName = censorFileName($_SESSION['bgjob']['origName']);
    
    $id = addModel($_SESSION['bgjob']['tmpPdb'],
        $origName,
        $_SESSION['bgjob']['isCnsFormat'],
        $_SESSION['bgjob']['ignoreSegID']);
    
    $_SESSION['bgjob']['newModel'] = $id;
    
    // Clean up temp files
    unlink($_SESSION['bgjob']['tmpPdb']);
}
############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
