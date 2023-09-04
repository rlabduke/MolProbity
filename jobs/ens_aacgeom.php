<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Runs all the all-atom contact and geometric analysis
    tasks -- CB deviation, clashes, Ramachandran, etc.

INPUTS (via $_SESSION['bgjob']):
    ensID           ID code for ensemble to process
    (for other options, see lib/analyze.php::runAnalysis())

OUTPUTS (via $_SESSION['bgjob']):

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/analyze.php');
    require_once(MP_BASE_DIR.'/lib/analyze_nmr.php');
    require_once(MP_BASE_DIR.'/lib/visualize_nmr.php');
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
$ensID = $_SESSION['bgjob']['ensID'];
$ensemble = $_SESSION['ensembles'][$ensID];

// TODO: This should be moved to analyze.php::runEnsembleAnalysis()
//-----------------------------------------------------------------
$opts = $_SESSION['bgjob'];

$modelDir   = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
$modelURL   = $_SESSION['dataURL'].'/'.MP_DIR_MODELS;
$kinDir     = $_SESSION['dataDir'].'/'.MP_DIR_KINS;
$kinURL     = $_SESSION['dataURL'].'/'.MP_DIR_KINS;
if(!file_exists($kinDir)) mkdir($kinDir, 0777);
$chartDir   = $_SESSION['dataDir'].'/'.MP_DIR_CHARTS;
$chartURL   = $_SESSION['dataURL'].'/'.MP_DIR_CHARTS;
if(!file_exists($chartDir)) mkdir($chartDir, 0777);

$infile     = "$modelDir/$ensemble[pdb]";
$infiles    = array();
foreach($ensemble['models'] as $modelID)
    $infiles[] = $modelDir.'/'.$_SESSION['models'][$modelID]['pdb'];
    $use_cdl = $_SESSION['models'][$modelID]['stats']['use_cdl'];

$tasks = array();
if($opts['doCharts'])            $tasks['charts'] = "Create ensemble multi-criterion charts";
if($opts['doEnsembleKinemage'])  $tasks['multikin'] = "Create ensemble multi-criterion kinemage";
if($opts['doRamaPDF'])          $tasks['ramapdf'] = "Create multi-model Ramachandran plot (PDF)";
if($opts['doRamaKin'])          $tasks['ramakin'] = "Create multi-model Ramachandran plot (kinemage)";
if($opts['doMultiGraph'])       $tasks['multigraph'] = "Create multi-criterion graph";
if($opts['doMultiModelChart'])  $tasks['multichart'] = "Create multi-criterion chart";
$opts['doEnsemble'] = $ensemble;
echo $ensemble;

if($opts['doCharts']) {

  setProgress($tasks, 'charts');
  $labbookEntry .= "<h3>Model-by-model validation results</h3>\n";
  $labbookEntry .= "<p>\n";
  $labbookEntry .= runEnsembleAnalysis($ensemble, $opts);

}

if($opts['doEnsembleKinemage'])
{
    setProgress($tasks, 'multikin'); // updates the progress display if running as a background job
    $mcKinOpts = array(
        'ribbons'   =>  $opts['kinRibbons'],
        'Bscale'    =>  $opts['kinBfactor'],
        'Qscale'    =>  $opts['kinOccupancy'],
        'altconf'   =>  $opts['kinAltConfs'],
        'rama'      =>  $opts['kinRama'],
        'rota'      =>  $opts['kinRota'],
        'geom'      =>  $opts['kinGeom'],
        'cbdev'     =>  $opts['kinCBdev'],
        'omega'     =>  $opts['kinOmega'],
        'cablam'    =>  $opts['kinCablamLow'],
        'pperp'     =>  $opts['kinBaseP'],
        'suites'    =>  $opts['kinSuite'],
        'clashdots' =>  $opts['kinClashes'],
        'hbdots'    =>  $opts['kinHbonds'],
        'vdwdots'   =>  $opts['kinContacts']
    );
    $outfile = "$kinDir/$ensemble[prefix]multi.kin";
    makeMulticritKin2($infiles, $outfile, $mcKinOpts, $use_cdl);

    // EXPERIMENTAL: gzip compress large multikins
    if(filesize($outfile) > MP_KIN_GZIP_THRESHOLD)
    {
        destructiveGZipFile($outfile);
    }

    $labbookEntry .= "<h3>Multi-model multi-criterion kinemage</h3>\n";
    $labbookEntry .= "<p>\n";
    $labbookEntry .= "<i>Note: these kins are often too big to view in the browser. You may need to download it and view it off line.</i>\n";
    $labbookEntry .= "<br>".linkKinemage("$ensemble[prefix]multi.kin", "Multi-criterion kinemage");
    $labbookEntry .= "</p>\n";
}
if($opts['doRamaPDF'])
{
    setProgress($tasks, 'ramapdf'); // updates the progress display if running as a background job
    $outfile = "$chartDir/$ensemble[prefix]rama.pdf";
    makeRamachandranPDF($infile, $outfile);

    $labbookEntry .= "<h3>Multi-model Ramachandran plot</h3>\n";
    $labbookEntry .= "<p>".linkAnyFile("$ensemble[prefix]rama.pdf", "Ramachandran plot PDF")."</p>\n";
}
if($opts['doRamaKin'])
{
    setProgress($tasks, 'ramakin'); // updates the progress display if running as a background job
    $outfile = "$chartDir/$ensemble[prefix]rama.kin";
    makeRamachandranKin($infile, $outfile);

    $labbookEntry .= "<h3>Multi-model Ramachandran plot kinemage</h3>\n";
    $labbookEntry .= "<p>".linkAnyFile("$ensemble[prefix]rama.kin", "Ramachandran plot kinemage")."</p>\n";

}
if($opts['doMultiGraph'])
{
    setProgress($tasks, 'multigraph'); // updates the progress display if running as a background job
    $outfile = "$kinDir/$ensemble[prefix]multigraph.kin";
    makeChartKin($infiles, $outfile);
    $labbookEntry .= "<div class='alert'>\n<center><h3>ALPHA TEST</h3></center>\n";
    $labbookEntry .= "Not suitable for use by the general public: ".linkKinemage("$ensemble[prefix]multigraph.kin", "Multi-criterion graph");
    $labbookEntry .= "</div>\n";
}
if($opts['doMultiModelChart'])
{
    setProgress($tasks, 'multichart'); // updates the progress display if running as a background job
    $outfile = "$kinDir/$ensemble[prefix]mmmcc.kin"; // Multi-Model Multi-Criterion Chart
    writeMultimodelChart($infiles, $outfile);
    $labbookEntry .= "<div class='alert'>\n<center><h3>ALPHA TEST</h3></center>\n";
    $labbookEntry .= "Not suitable for use by the general public: ".linkKinemage("$ensemble[prefix]mmmcc.kin", "Multi-chart mockup");
    $labbookEntry .= "</div>\n";
}

setProgress($tasks, null);
//-----------------------------------------------------------------

$_SESSION['bgjob']['labbookEntry'] = addLabbookEntry(
    "Analysis output: all-atom contacts and geometry for $ensemble[pdb]",
    $labbookEntry,
    $ensID,
    "auto",
    "clash_rama.png"
);
############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
mpSaveSession();
?>
