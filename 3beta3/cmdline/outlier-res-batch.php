<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Processes a directory full of PDB files non-recursively and outputs
    a list of all the Ramachanadran/rotamer/C-beta dev./clash outliers
    as well as generating a multi-criterion kinemage for each of them.
    
 -> We assume all files already have H's added! <-

INPUTS (via $_SERVER['argv']):
    the path to a directory; *.pdb will be processed
    the path to a directory where multicrit kins will be created

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
    require_once(MP_BASE_DIR.'/lib/visualize.php');
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
    elseif(!isset($kinFolder))
        $kinFolder = $arg;
    else
        die("Too many or unrecognized arguments: '$arg'\n");
}

if(! isset($pdbFolder))
    die("No input directory specified.\n");
elseif(! is_dir($pdbFolder))
    die("Input directory '$pdbFolder' does not exist or is not a directory.\n");
elseif(! isset($kinFolder))
    die("No output directory specified.\n");
elseif(! is_dir($kinFolder))
    die("Output directory '$kinFolder' does not exist or is not a directory.\n");
    
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
// This way, we don't create a session unless our input is semi-valid.
mpStartSession(true); // create a new session

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
        
        // Run analysis; load data
        $pdbfile = "$model[dir]/$model[pdb]";
        runClashlist($pdbfile, "$model[dir]/$model[prefix]clash.data");
        $clash = loadClashlist("$model[dir]/$model[prefix]clash.data");
        runCbetaDev($pdbfile, "$model[dir]/$model[prefix]cbdev.data");
        $cbdev = loadCbetaDev("$model[dir]/$model[prefix]cbdev.data");
        $badCbeta = findCbetaOutliers($cbdev);
        runRotamer($pdbfile, "$model[dir]/$model[prefix]rota.data");
        $rota = loadRotamer("$model[dir]/$model[prefix]rota.data");
        $badRota = findRotaOutliers($rota);
        runRamachandran($pdbfile, "$model[dir]/$model[prefix]rama.data");
        $rama = loadRamachandran("$model[dir]/$model[prefix]rama.data");
        
        // Clash scores
        echo "$filename:clashscore:$clash[scoreAll]\n";
        echo "$filename:clashscoreB<40:$clash[scoreBlt40]\n";
        foreach($clash['clashes'] as $res => $overlap)
            echo "$filename:clash:$res:$overlap\n";
        
        // Cbetas
        foreach($badCbeta as $res => $dist)
            echo "$filename:cbdev:$res:$dist\n";
        
        // Rotamers
        foreach($badRota as $res => $score)
            echo "$filename:rotamer:$res:$score\n";
        
        // Rama outliers - count each type
        foreach($rama as $r)
            if($r['eval'] == "OUTLIER") echo "$filename:ramachandran:$r[resName]:$r[type]\n";
        
        // Make multicrit kin
        makeMulticritKin($infile, "$kinFolder/$model[prefix]multi.kin", $rama, $rota);
        
        // Clean out the data
        removeModel($modelID);
    }
}
closedir($h);

############################################################################
// Clean up and go home
mpDestroySession();
?>
