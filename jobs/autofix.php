<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file runs the autoFix script on an existing model in this session 
    and creates a new model entry for the fixed file.
    
INPUTS (via $_SESSION['bgjob']):
    modelID         ID code for model to process
    mapID           ID for ccp4 style map to process
    mtzID           ID for ccp4 style mtz with phase information to process
    makeFixkin     true if the user wants a Flipkin made

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

$map    = $_SESSION['dataDir'].'/'.MP_DIR_EDMAPS.'/'.$_SESSION['bgjob']['edmap'];
$mp_base_dir = MP_BASE_DIR; 
$rawDir     = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA;
    if(!file_exists($rawDir)) mkdir($rawDir, 0777);
$temp_dir = $rawDir; 

// Set up progress message
$tasks['autoFix'] = "Flip Leu residues with <code>autoFix</code> may take a few minutes";
$tasks['fixkin'] = "Create Leucine  <code>fixkin</code> kinemages";
$tasks['improvement'] = "Calculate improvement in quality statistics"; 

setProgress($tasks, 'autoFix');

$newModel = createModel($modelID."_mod");
$pdboutname = $newModel['pdb'];
$modelOutpath = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
$modelOutpath .= '/'.$pdboutname;

$outname = $modelID."-autofix.log";
$outpath    = $_SESSION['dataDir'].'/'.MP_DIR_SYSTEM;
if(!file_exists($outpath)) mkdir($outpath, 0777); // shouldn't ever happen, but might...
$outpath .= '/'.$outname;

//echo "autoFix($pdb, $map, $temp_dir, $mp_base_dir, $outpath, $modelOutpath)";
//exit(0); 
autoFix($pdb, $map, $temp_dir, $mp_base_dir, $outpath, $modelOutpath);

$newModel['stats']          = pdbstat($modelOutpath);
$newModel['parent']         = $modelID;
$newModel['history']        = "Derived from $model[pdb] by AutoFix";
$newModel['isUserSupplied'] = $model['isUserSupplied'];
$newModel['isFixed']        = true;
$_SESSION['models'][ $newModel['id'] ] = $newModel;
$_SESSION['bgjob']['modelID'] = $newModel['id'];
$_SESSION['lastUsedModelID'] = $newModel['id']; // this is now the current model

//if($_SESSION['bgjob']['makeFlipkin'])
//{
    setProgress($tasks, 'fixkin');
    $outpath    = $_SESSION['dataDir'].'/'.MP_DIR_KINS;
    if(!file_exists($outpath)) mkdir($outpath, 0777);
//    makeFlipkin($_SESSION['dataDir'].'/'.MP_DIR_MODELS."/$newModel[pdb]",
//        "$outpath/$newModel[prefix]-autoFlip.kin");
//}


setProgress($tasks, 'improvement');

$improveText = "";
$improvementList = array(); 

   $mainModel = mpTempfile("tmp_newModelH_pdb_");
   reduceBuild($modelOutpath, $mainModel);
// Changes in Quality stats (except Clashes)  are calulated from the autofix stats in lib/model.php now
// Maybe it is better here?
//// Ramachandran
//   $outfile = mpTempfile("tmp_ramachandran_");
//   runRamachandran($mainModel, $outfile);
//   $rama = loadRamachandran($outfile); 
//   $mainRamaCount = count(findRamaOutliers($rota));
//   unlink($outfile); 
//// Rotamers
//   $outfile = mpTempfile("tmp_rotamer_");
//   runRotamer($mainModel, $outfile);
//   $rota = loadRotamer($outfile);
//   $mainRotaCount = count(findRotaOutliers($rota));
//   unlink($outfile);
//// Cbeta
//   $outfile = mpTempfile("tmp_Cbeta_");
//   runCbetaDev($mainModel, $outfile);
//   $cbdev = loadCbetaDev($outfile);
//   $mainCbetaCount = count(findCbetaOutliers($cbdev));
//   unlink($outfile);
// Clashes
   $outfile = mpTempfile("tmp_clashlist_");
   runClashlist($mainModel, $outfile);
   $clash = loadClashlist($outfile);
   $mainClashscore = $clash['scoreAll'];
   unlink($outfile);
   unlink($mainModel);

   $altpdb = mpTempfile("tmp_altH_pdb_");
   $altInpath = $_SESSION['dataDir'].'/'.MP_DIR_MODELS."/$newModel[parent].pdb";
   reduceBuild($altInpath, $altpdb);
//   // Ramachandran
//       $outfile = mpTempfile("tmp_ramachandran_");
//       runRamachandran($altpdb, $outfile);
//       $altrama = loadRamachandran($outfile);
//       $altRamaCount = count(findRamaOutliers($altrota));
//       if($altRamaCount > $mainRamaCount)
//           $improvementList[] = "fixed ".($altRamaCount - $mainRamaCount)." ramachandran outliers";
//       unlink($outfile);
//   // Rotamers
//       $outfile = mpTempfile("tmp_rotamer_");
//       runRotamer($altpdb, $outfile);
//       $altrota = loadRotamer($outfile);
//       $altRotaCount = count(findRotaOutliers($altrota));
//       if($altRotaCount > $mainRotaCount)
//           $improvementList[] = "fixed ".($altRotaCount - $mainRotaCount)." rotamer outliers";
//       unlink($outfile);
//   // Cbeta
//      $outfile = mpTempfile("tmp_Cbeta_");
//      runCbetaDev($altpdb, $outfile);
//      $altcbdev = loadCbetaDev($outfile);
//      $altCbetaCount = count(findCbetaOutliers($altcbdev));
//       if($altCbetaCount > $mainCbetaCount)
//           $improvementList[] = "fixed ".($altCbetaCount - $mainCbetaCount)." C(beta) outliers";
//       unlink($outfile);
//   // Clashes
       $outfile = mpTempfile("tmp_clashlist_");
       runClashlist($altpdb, $outfile);
       $altclash = loadClashlist($outfile);
       if($altclash['scoreAll'] > $mainClashscore)
           $improvementList[] = "improve your clashscore by ".($altclash['scoreAll'] - $mainClashscore)." points";
       unlink($outfile);
       unlink($altpdb);

       if(count($improvementList) > 0)  { 
          $improveText .= implode(" and ", $improvementList);
          $autoFixImprovementSummary = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA."/$newModel[parent]_autofix_improvement.html";
          $out = fopen($autoFixImprovementSummary, 'a');
          fwrite($out, $improveText);
          fclose($out);
       }

setProgress($tasks, null);

############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
