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

    biolunit        true if we should get the biological, rather   }
                    than asymmetric, unit from the PDB             } for pdbCode
    eds_2fofc       true if the user wants the 2Fo-Fc map from EDS } only...
    eds_fofc        true if the user wants the Fo-Fc map from EDS  }

OUTPUTS (via $_SESSION['bgjob']):
    Adds a new entry to $_SESSION['models'].
    newModel        the ID of the model just added, or null on failure
                    In the case of multiple of models, it will be the first one.
    labbookEntry    the labbook entry number for adding this new model

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

#{{{ getMaps
############################################################################
function getMaps($code)
{
    global $map_notebook_msg;
    $map_notebook_msg = "";
    $prog = getProgressTasks();
    if($_SESSION['bgjob']['eds_2fofc']) $prog['2fofc'] = "Download 2Fo-Fc map from the EDS";
    if($_SESSION['bgjob']['eds_fofc'])  $prog['fofc']  = "Download Fo-Fc (difference) map from the EDS";

    $mapDir = "$_SESSION[dataDir]/".MP_DIR_EDMAPS;
    if(!file_exists($mapDir)) mkdir($mapDir, 0777);

    if($_SESSION['bgjob']['eds_2fofc'])
    {
        setProgress($prog, '2fofc');
        $mapName = "$code.omap.gz";
        $mapPath = "$mapDir/$mapName";
        if(!file_exists($mapPame))
        {
            $tmpMap = getEdsMap($code, 'omap', '2fofc');
            if($tmpMap && copy($tmpMap, $mapPath))
            {
                unlink($tmpMap);
                $_SESSION['edmaps'][$mapName] = $mapName;
                mpLog("edmap-eds:User requested 2Fo-Fc map for $code from the EDS");
                $map_notebook_msg .= "<p>The 2Fo-Fc map for $code was successfully retrieved from the EDS.</p>\n";
            }
            else $map_notebook_msg .= "<p><div class='alert'>The 2Fo-Fc map for $code could not be retrieved from the EDS.</div></p>\n";
        }
        else echo "Map file already exists";
    }
    if($_SESSION['bgjob']['eds_fofc'])
    {
        setProgress($prog, 'fofc');
        $mapName = "$code-diff.omap.gz";
        $mapPath = "$mapDir/$mapName";
        if(!file_exists($mapPame))
        {
            $tmpMap = getEdsMap($code, 'omap', 'fofc');
            if($tmpMap && copy($tmpMap, $mapPath))
            {
                unlink($tmpMap);
                $_SESSION['edmaps'][$mapName] = $mapName;
                mpLog("edmap-eds:User requested Fo-Fc map for $code from the EDS");
                $map_notebook_msg .= "<p>The Fo-Fc map for $code was successfully retrieved from the EDS.</p>\n";
            }
            else $map_notebook_msg .= "<p><div class='alert'>The Fo-Fc map for $code could not be retrieved from the EDS.</div></p>\n";
        }
    }

    setProgress($prog, null);
}
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
if(isset($_SESSION['bgjob']['pdbCode']))
{
    // Better upper case it to make sure we find the file in the database
    $code = strtoupper($_SESSION['bgjob']['pdbCode']);

    if(preg_match('/^[0-9A-Z]{4}$/i', $code))
    {
        setProgress(array("pdb" => "Retrieve PDB file $code over the network"), "pdb");
        $tmpfile = getPdbModel($code, $_SESSION['bgjob']['biolunit']);
        $fileSource = "http://www.pdb.org/";
    }
    else if(preg_match('/^[0-9A-Z]{6,10}$/i', $code))
    {
        setProgress(array("pdb" => "Retrieve NDB file $code over the network (can take 30+ seconds)"), "pdb");
        $tmpfile = getNdbModel($code);
        $fileSource = "http://ndbserver.rutgers.edu/";
    }
    else $tmpfile == null;

    if($tmpfile == null)
    {
        $_SESSION['bgjob']['newModel'] = null;
    }
    else
    {
        $id = addModelOrEnsemble($tmpfile,
            strtolower("$code.pdb"), // lower case is nicer for readability
            $_SESSION['bgjob']['isCnsFormat'],
            $_SESSION['bgjob']['ignoreSegID'],
            false /* came from public database */);
        if(isset($_SESSION['bgjob']['xray']))
            {
                addXraydata(strtolower($code), $id);
            }

        // Clean up temp files
        unlink($tmpfile);

        if(preg_match('/^[0-9A-Z]{4}$/i', $code)
        &&($_SESSION['bgjob']['eds_2fofc']
        || $_SESSION['bgjob']['eds_fofc']))
        {
            getMaps($code);
        }
    }
}
else
{
    // Remove illegal chars from the upload file name
    $origName = censorFileName($_SESSION['bgjob']['origName'], array("pdb", "ent", "xyz", "mtz", "cif"));
    $fileSource = "local disk";

    if(isset($_SESSION['bgjob']['tmpPdb'])) 
    {
        $id = addModelOrEnsemble($_SESSION['bgjob']['tmpPdb'],
            $origName,
            $_SESSION['bgjob']['isCnsFormat'],
            $_SESSION['bgjob']['ignoreSegID'],
            true /* came from upload */);
        // Clean up temp files
        unlink($_SESSION['bgjob']['tmpPdb']);
    }
    elseif(isset($_SESSION['bgjob']['tmpMtz'])) 
    {
        addMtz($_SESSION['bgjob']['tmpMtz'], $origName);
        // Clean up temp files
        unlink($_SESSION['bgjob']['tmpMtz']);
    }

}

// Automatic labbook entry
if(isset($id))
{
    // this is now the "working model" until overriden (could also be an ensemble)
    $_SESSION['lastUsedModelID'] = $id;
    $_SESSION['bgjob']['newModel'] = $id;

    if(isset($_SESSION['ensembles'][$id]))
    {
        $idList = $_SESSION['ensembles'][$id]['models'];
        $model = $_SESSION['models'][ reset($idList) ];
    }
    else
    {
        $idList = array();
        $model = $_SESSION['models'][ $id ];
    }

    // Original task list set during addModel()
    $tasks = getProgressTasks();
    $tasks['thumbnail'] = "Make a thumbnail kinemage using <code>prekin -cass -colornc</code>";
    setProgress($tasks, "thumbnail");

    // Make a thumbnail kin for the lab notebook
    $modelDir = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
    $kinDir = $_SESSION['dataDir'].'/'.MP_DIR_KINS;
    $kinURL = $_SESSION['dataURL'].'/'.MP_DIR_KINS;
    if(!file_exists($kinDir)) mkdir($kinDir, 0777);
    exec("prekin -cass -colornc $modelDir/$model[pdb] > $kinDir/$model[prefix]thumbnail.kin");

    $s = "";
    $s .= "<div class='side_options'>\n";
    $s .= "<center><small><i>drag to rotate</i></small></center>\n";
    $s .= "<applet code='Magelet.class' archive='magejava.jar' width='150' height='150'>\n";
    $s .= "  <param name='kinemage' value='$kinURL/$model[prefix]thumbnail.kin'>\n";
    $s .= "  <param name='buttonpanel' value='no'>\n";
    $s .= "</applet>\n";
    $s .= "<center><small><a href='help/java.html' target='_blank'>Don't see anything?</a></i></small></center>\n";
    $s .= "</div>\n";

    if(count($idList) > 1) // NMR/multiple models
    {
        $ensemble = $_SESSION['ensembles'][$id];
        $title = "Uploaded ".count($idList)."-model ensemble as $ensemble[pdb]";
        $s .= "Your file from $fileSource was uploaded as an ensemble of ".count($idList)." separate models.\n";
        $s .= "The following description applies to the first of these models, which is shown in the thumbnail kinemage:\n";
    }
    else // xray/single model
    {
        $title = "Uploaded PDB file as $model[pdb]";
        $s .= "Your file from $fileSource was uploaded as $model[pdb].\n";
    }
    $details = describePdbStats($model['stats'], true);
    $s .= "<ul>\n";
    foreach($details as $detail) $s .= "<li>$detail</li>\n";
    $s .= "</ul>\n";

    $s .= $map_notebook_msg;

    if($model['stats']['originalInputH'])
    {
      $cur = $_SESSION['lastUsedModelID'];
      if(isset($_SESSION['ensembles'][$cur]))
      {
        $orig = "the original ensemble (or individual model)";
      }
      else
      {
        foreach($_SESSION['models'] as $id_m => $model_m)
          {
            if($_SESSION['lastUsedModelID'] != $id_m) $orig = $model_m['pdb'];
          }
      }
      $s .= "<p><div class='alert'>The hydrogen atoms from your input model have been removed.<br>\n";
      $s .= "You will be able to add hydrogens at either electron cloud positions".(count($idList) > 1 ? "" : " (default)").", which are best for X-ray crystallographic models, <br>";
      $s .= "or at nuclear positions, which are best for NMR models, neutron diffraction models, etc.<br>";
      $s .= "<br>If you prefer to use the input hydrogen positions, please select $orig from the 'Currently working on' list on the next page.</div></p>";
    }

    if($model['segmap'])
    {
        $s .= "<p><div class='alert'>Because this model had more segment IDs than chainIDs,\n";
        $s .= "the segment IDs were automagically turned into new chain IDs for this model.\n";
        $s .= "If you would prefer the original chain IDs, please check the <b>Ignore segID field</b>\n";
        $s .= "on the file upload page.</div></p>";
    }

    $idList[] = $id; // make sure ensemble ID also appears in notebook
    $_SESSION['bgjob']['labbookEntry'] = addLabbookEntry(
        $title,
        $s,
        implode('|', $idList),
        "auto",
        "pdb_icon.png"
    );

    setProgress($tasks, null);
}

############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
