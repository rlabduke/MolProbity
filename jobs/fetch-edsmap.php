<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Downloads map(s) from the EDS
    
INPUTS (via $_SESSION['bgjob']):
    pdbCode         the PDB or NDB code for the molecule
    
    eds_2fofc       true if the user wants the 2Fo-Fc map from EDS
    eds_fofc        true if the user wants the Fo-Fc map from EDS

OUTPUTS (via $_SESSION['bgjob']):
    labbookEntry    the labbook entry number for adding these file(s)

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
    require_once(MP_BASE_DIR.'/lib/labbook.php');
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
// Better upper case it to make sure we find the file in the database
$code = strtoupper($_SESSION['bgjob']['pdbCode']);


if(preg_match('/^[0-9A-Z]{4}$/i', $code))
{
    $title = "Retrieved electron density for $code";
    $s = ""; // notebook entry
    
    $prog = array();
    if($_SESSION['bgjob']['eds_2fofc']) $prog['2fofc'] = "Download 2Fo-Fc map from the EDS";
    if($_SESSION['bgjob']['eds_fofc'])  $prog['fofc']  = "Download Fo-Fc (difference) map from the EDS";
    
    $mapDir = "$_SESSION[dataDir]/".MP_DIR_EDMAPS;
    if(!file_exists($mapDir)) mkdir($mapDir, 0777);
    
    if($_SESSION['bgjob']['eds_2fofc'])
    {
        setProgress($prog, '2fofc');
        $mapName = "$code.ccp4.gz";
        $mapPath = "$mapDir/$mapName";
        if(!file_exists($mapPame))
        {
            $tmpMap = getEdsMap($code, 'ccp4', '2fofc');
            if($tmpMap && copy($tmpMap, $mapPath))
            {
                unlink($tmpMap);
                $_SESSION['edmaps'][$mapName] = $mapName;
                mpLog("edmap-eds:User requested 2Fo-Fc map for $code from the EDS");
                $s .= "<p>The 2Fo-Fc map for $code was successfully retrieved from the EDS.</p>\n";
            }
            else $s .= "<p><div class='alert'>The 2Fo-Fc map for $code could not be retrieved from the EDS.</div></p>\n";
        }
        else $s .= "<p><div class='alert'>The 2Fo-Fc map for $code could not be retrieved, because a file of the same name already exists.</div></p>\n";
    }
    if($_SESSION['bgjob']['eds_fofc'])
    {
        setProgress($prog, 'fofc');
        $mapName = "$code-diff.ccp4.gz";
        $mapPath = "$mapDir/$mapName";
        if(!file_exists($mapPame))
        {
            $tmpMap = getEdsMap($code, 'ccp4', 'fofc');
            if($tmpMap && copy($tmpMap, $mapPath))
            {
                unlink($tmpMap);
                $_SESSION['edmaps'][$mapName] = $mapName;
                mpLog("edmap-eds:User requested Fo-Fc map for $code from the EDS");
                $s .= "<p>The Fo-Fc map for $code was successfully retrieved from the EDS.</p>\n";
            }
            else $s .= "<p><div class='alert'>The Fo-Fc map for $code could not be retrieved from the EDS.</div></p>\n";
        }
        else $s .= "<p><div class='alert'>The Fo-Fc map for $code could not be retrieved, because a file of the same name already exists.</div></p>\n";
    }
}
else
{
    $title = "Failed to retrieve electron density from the EDS";
    $s .= "Unable to retrieve maps from the EDS because '$code' is not a valid PDB ID.";
}

// I'm too lazy to put it all the hyperlinks by hand  :)
$s = preg_replace('/EDS/', "<a href='http://eds.bmc.uu.se/' target='_blank'>EDS</a>", $s);

$_SESSION['bgjob']['labbookEntry'] = addLabbookEntry(
    $title,
    $s,
    "",
    "auto"
);

setProgress($tasks, null);

############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
