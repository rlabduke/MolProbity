<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Processes a directory full of PDB files non-recursively and outputs
    a one-line validation summary for each of them.
    
 -> We assume all files already have H's added! <-

INPUTS (via $_SERVER['argv']):
    the path to a directory; *.pdb will be processed

OUTPUTS:

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// PHP's working dir is set by the script that is begins execution with.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(getcwd().'/..'));
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
    if(!isset($pdbFolder))
        $pdbFolder = $arg;
    else
        die("Too many or unrecognized arguments: '$arg'\n");
}

if(! isset($pdbFolder))
    die("No input directory specified.\n");
elseif(! is_dir($pdbFolder))
    die("Input directory '$pdbFolder' does not exist or is not a directory.\n");
    
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
// This way, we don't create a session unless our input is semi-valid.
mpStartSession(true); // create a new session

// Describe the output of this script
echo "#pdbFileName:chains:residues:nucacids:resolution:rvalue:rfree:clashscore:clashscoreB<40";
echo ":cbeta>0.25:numCbeta:minCbeta:maxCbeta:medianCbeta:meanCbeta:stddevCbeta:rota<1%:numRota:ramaOutlier:ramaAllowed:ramaFavored\n";

// Loop through all PDBs in the provided directory
$h = opendir($pdbFolder);
while(($infile = readdir($h)) !== false)
{
    $infile = "$pdbFolder/$infile";
    if(is_file($infile) && endsWith($infile, ".pdb"))
    {
        // Add model
        $filename = basename($infile);
        $modelID = addModel($infile, $filename);
        $model =& $_SESSION['models'][$modelID];
        $pdbstats = $model['stats'];
        echo $filename;
        echo ":$pdbstats[chains]:$pdbstats[residues]:$pdbstats[nucacids]:$pdbstats[resolution]:$pdbstats[rvalue]:$pdbstats[rfree]";
        
        // Run analysis; load data
        //runAnalysis($modelID, array('doAll' => true)); // easy but wasteful!
        $pdbfile = "$model[dir]/$model[pdb]";
        runClashlist($pdbfile, "$model[dir]/$model[prefix]clash.data");
        $clash = loadClashlist("$model[dir]/$model[prefix]clash.data");
        runCbetaDev($pdbfile, "$model[dir]/$model[prefix]cbdev.data");
        $cbdev = loadCbetaDev("$model[dir]/$model[prefix]cbdev.data");
        $badCbeta = findCbetaOutliers($cbdev);
        $cbStats = calcCbetaStats($cbdev);
        runRotamer($pdbfile, "$model[dir]/$model[prefix]rota.data");
        $rota = loadRotamer("$model[dir]/$model[prefix]rota.data");
        $badRota = findRotaOutliers($rota);
        runRamachandran($pdbfile, "$model[dir]/$model[prefix]rama.data");
        $rama = loadRamachandran("$model[dir]/$model[prefix]rama.data");
        
        // Clash scores
        echo ":" . $clash['scoreAll'] . ":" . $clash['scoreBlt40'];
        
        // Cbetas
        echo ":" . count($badCbeta) . ":" . count($cbdev);
        echo ":$cbStats[min]:$cbStats[max]:$cbStats[median]:$cbStats[mean]:$cbStats[stddev]";
        
        // Rotamers
        echo ":" . count($badRota) . ":" . count($rota);
        
        // Rama outliers - count each type
        unset($ramaScore); // or else we will accumulate the counts for each model!
        foreach($rama as $r)
            $ramaScore[ $r['eval'] ] += 1;
        echo ":" . ($ramaScore['OUTLIER']+0) . ":" . ($ramaScore['Allowed']+0) . ":" . ($ramaScore['Favored']+0);
        
        echo "\n"; // end of this line
        removeModel($modelID);
    }
}
closedir($h);

############################################################################
// Clean up and go home
mpDestroySession();
?>
