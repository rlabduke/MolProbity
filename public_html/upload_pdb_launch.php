<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file is on the receiving side of the upload tab. The relevant file(s)
    are transferred to temporary locations if necessary, and then a background
    job is launched for further processing of them.

INPUTS (via Get or Post):
    cmd             either "Upload this file", "Get this file",
                    or something else (guess intended action).
    uploadFile      the uploaded file (data in $_FILES['uploadFile'][...])
    isCnsFormat     true if the user thinks he has CNS atom names
    ignoreSegID     true if the user wants to never map segIDs to chainIDs
    pdbCode         the four-character PDB identifier (mixed case)
    get2FoFc        true if user wants map from EDS
    getFoFc         true if user wants map from EDS

OUTPUTS (via $_SESSION['bgjob']):
    tmpPdb          the (temporary) file where the upload is stored.
    origName        the name of the file on the user's system.
    pdbCode         the PDB or NDB code for the molecule
    (EITHER pdbCode OR tmpPdb and origName will be set)
    
    isCnsFormat     true if the user thinks he has CNS atom names
    ignoreSegID     true if the user wants to never map segIDs to chainIDs

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// PHP's working dir is set by the script that is begins execution with.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(getcwd().'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    #mpSessReadOnly();

#{{{ failMsg - terminate upload with failure message
############################################################################
function failMsg($msg)
{
    echo mpPageHeader("Sorry!");
    echo $msg;
    echo "\n<p><a href='upload_tab.php?$_SESSION[sessTag]'>Try again</a>\n";
    echo mpPageFooter();
    die();
}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################
if($_REQUEST['pdbCode'] != '')                  $mode = "pdb/ndb";
elseif($_FILES['uploadFile']['size'] > 0)       $mode = "upload";
elseif($_REQUEST['cmd'] == "Get this file" )    $mode = "pdb/ndb";
elseif($_REQUEST['cmd'] == "Upload this file")  $mode = "upload";


if($mode == "upload")
{
    $tmpfile = tempnam(MP_BASE_DIR."/tmp", "tmp_pdb_");
    if( !$_FILES['uploadFile']['error'] && $_FILES['uploadFile']['size'] > 0
    &&  move_uploaded_file($_FILES['uploadFile']['tmp_name'], $tmpfile))
    {
        unset($_SESSION['bgjob']); // Clean up any old data
        $_SESSION['bgjob']['tmpPdb']        = $tmpfile;
        $_SESSION['bgjob']['origName']      = $_FILES['uploadFile']['name'];
        $_SESSION['bgjob']['isCnsFormat']   = $_REQUEST['isCnsFormat'];
        $_SESSION['bgjob']['ignoreSegID']   = $_REQUEST['ignoreSegID'];
        
        // launch background job
        launchBackground(MP_BASE_DIR."/jobs/addmodel.php", "upload_pdb_done.php?$_SESSION[sessTag]", 3);
        
        // include() status monitoring page
        include(MP_BASE_DIR."/public_html/job_progress.php");
        die();
    }
    else
    {
        echo $tmpfile;
        unlink($tmpfile);
        failMsg("File upload failed for unknown reason; error code $_FILES[uploadFile][error].");
    }
}
elseif($mode == "pdb/ndb")
{
    unset($_SESSION['bgjob']); // Clean up any old data
    $_SESSION['bgjob']['pdbCode']       = $_REQUEST['pdbCode'];
    $_SESSION['bgjob']['isCnsFormat']   = false;
    $_SESSION['bgjob']['ignoreSegID']   = false;
    
    // launch background job
    launchBackground(MP_BASE_DIR."/jobs/addmodel.php", "upload_pdb_done.php?$_SESSION[sessTag]", 3);
    
    // include() status monitoring page
    include(MP_BASE_DIR."/public_html/job_progress.php");
    die();
}
else
    failMsg("What did you want to do?");

############################################################################
?>
