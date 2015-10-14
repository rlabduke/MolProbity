<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file runs the standard Reduce -build command on an existing
    model in this session and creates a new model entry for the Reduced file.

INPUTS (via $_SESSION['bgjob']):
    modelID         ID code for model to process
    makeFlipkin     true if the user wants a Flipkin made

OUTPUTS (via $_SESSION['bgjob']):
    Adds a new entry to $_SESSION['models'].
    modelID         the ID of the model just added

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
    require_once(MP_BASE_DIR.'/lib/visualize.php');
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
$modelID = $_SESSION['bgjob']['modelID'];
$model = $_SESSION['models'][$modelID];
$pdb = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$model['pdb'];

$reduce_blength = $_SESSION['bgjob']['reduce_blength'];
$_SESSION['reduce_blength'] = $reduce_blength;

$_SESSION['nqh_regularize'] = false;

// Set up progress message
if ($reduce_blength == 'ecloud')
{
  $tasks['reduce'] = "Add H with <code>reduce -build</code>";
}
elseif($reduce_blength == 'nuclear')
{
  $tasks['reduce'] = "Add H with <code>reduce -build -nuclear</code>";
}
if($_SESSION['bgjob']['nqh_regularize'])
{
  $tasks['regularize'] = "Regularize flipped N/Q/H geometry with <code>CCTBX</code> (if necessary)";
}
if($_SESSION['bgjob']['makeFlipkin'])
{
  $tasks['flipkin'] = "Create Asn/Gln and His <code>flipkin</code> kinemages";
}

setProgress($tasks, 'reduce');
$prereduceid = $_SESSION['lastUsedModelID'];
$newModel = createModel($modelID."FH");
$outname = $newModel['pdb'];
$outpath    = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
if(!file_exists($outpath)) mkdir($outpath, 0777); // shouldn't ever happen, but might...
$outpath .= '/'.$outname;
reduceBuild($pdb, $outpath, $reduce_blength);
$newModel['stats']          = pdbstat($outpath);
$newModel['parent']         = $modelID;

$changes = decodeReduceUsermods($outpath);
// Check to see if any cliques couldn't be solved by looking for scores = -9.9e+99
// At the same time, check to see if anything at all was flipped...
$didnt_solve = $did_flip = false;
$n = count($changes[0]); // How many changes are in the table?
for($c = 0; $c < $n; $c++)
{
    if($changes[6][$c] == "-9.9e+99")
        $didnt_solve = true;
    if(!$did_flip && ($changes[4][$c] == "FLIP" || $changes[4][$c] == "CLS-FL"))
        $did_flip = true;
}

// transfer mtz to reduced model
if(isset($_SESSION['models'][$modelID]['mtz_file']))
    $newModel['mtz_file'] = $_SESSION['models'][$modelID]['mtz_file'];

if($reduce_blength == 'ecloud')
{
  $newModel['history']        = "Derived from $model[pdb] by Reduce -build";
}
elseif($reduce_blength == 'nuclear')
{
  $newModel['history']        = "Derived from $model[pdb] by Reduce -build -nuclear";
}
$newModel['isUserSupplied'] = $model['isUserSupplied'];
$newModel['isReduced']      = true;
$newModel['isBuilt']        = true;
$_SESSION['models'][ $newModel['id'] ] = $newModel;
$_SESSION['bgjob']['modelID'] = $newModel['id'];
$_SESSION['lastUsedModelID'] = $newModel['id']; // this is now the current model

if($did_flip && $_SESSION['bgjob']['nqh_regularize'])
{
  setProgress($tasks, 'regularize');
  $flipInpath = $outpath;
  $minModel = createModel($modelID."FH_reg");
  $outname2 = $minModel['pdb'];
  $outpath2 = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
  $outpath2 .= '/'.$outname2;
  $temp = $_SESSION['dataDir'].'/tmp';
  if(file_exists($flipInpath))
  {
    regularizeNQH($flipInpath, $outpath2, $temp);
  }
  unset($_SESSION['models'][$_SESSION['lastUsedModelID']]);
  if(!file_exists($outpath2))
  {
    # We are deleting the reduced file because we'd rather not have users
    # play with the unregularized, nqh-flipped pdb. 
    unlink($flipInpath);
    $_SESSION['lastUsedModelID'] = $prereduceid;
    unset($_SESSION['bgjob']['processID']);
    $_SESSION['bgjob']['endTime']   = time();
    $_SESSION['bgjob']['isRunning'] = false;
    # there was an error. Let's see if it is an element type issue.
    $errfile = $_SESSION['dataDir']."/".MP_DIR_SYSTEM."/errors";
    $elementerror = is_elementerror($errfile);
    if($elementerror) $_SESSION['bgjob']['elementError'] = true;
    $modelerror = is_modelerror($errfile);
    if($modelerror) $_SESSION['bgjob']['modelError'] = true;
    else $_SESSION['bgjob']['cctbxError'] = true;
    die();
  }
  $minModel['stats']           = pdbstat($outpath2);
  $minModel['parent']          = $modelID;
  $minModel['modelID_pre_min'] = $newModel['id']; // for reduce-fix

  // transfer mtz to reduced model
  if(isset($_SESSION['models'][$modelID]['mtz_file']))
    $minModel['mtz_file'] = $_SESSION['models'][$modelID]['mtz_file'];

  if($reduce_blength == 'ecloud')
  {
    $minModel['history']        = "Derived from $model[pdb] by Reduce -build w/ CCTBX side-chain regularization";
  }
  elseif($reduce_blength == 'nuclear')
  {
    $minModel['history']        = "Derived from $model[pdb] by Reduce -build -nuclear w/ CCTBX side-chain regularization";
  }
  $minModel['isUserSupplied'] = $model['isUserSupplied'];
  $minModel['isReduced']      = true;
  $minModel['isBuilt']        = true;
  $minModel['isRegularized']  = true;
  $_SESSION['models'][ $minModel['id'] ] = $minModel;
  $_SESSION['bgjob']['modelID'] = $minModel['id'];
  $_SESSION['lastUsedModelID'] = $minModel['id']; // this is now the current model
  $_SESSION['nqh_regularize'] = $_SESSION['bgjob']['nqh_regularize'];
}

if($_SESSION['bgjob']['makeFlipkin'])
{
    setProgress($tasks, 'flipkin');
    $outpath    = $_SESSION['dataDir'].'/'.MP_DIR_KINS;
    if(!file_exists($outpath)) mkdir($outpath, 0777);
    $curModel = $_SESSION['models'][$_SESSION['lastUsedModelID']];
    $inPath = $_SESSION['dataDir'].'/'.MP_DIR_MODELS."/$curModel[pdb]";
    if(file_exists($inPath))
    {
      makeFlipkin($inPath,
        "$outpath/$curModel[prefix]flipnq.kin",
        "$outpath/$curModel[prefix]fliphis.kin");
    }
}

setProgress($tasks, null);

############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
