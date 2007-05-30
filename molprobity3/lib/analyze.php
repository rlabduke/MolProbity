<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Provides functions for producing analysis data from outside programs
    and for loading and interpretting that data.
    
    Many functions work with a column-formatted residue name
    stored in exactly 9 characters, like this: 'cnnnnittt'
        c: Chain ID, space for none
        n: sequence number, right justified, space padded
        i: insertion code, space for none
        t: residue type (ALA, LYS, etc.), all caps, left justified, space padded
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/strings.php');
require_once(MP_BASE_DIR.'/lib/visualize.php'); // for making kinemages
require_once(MP_BASE_DIR.'/lib/model.php'); // for making kinemages

#{{{ runAnalysis - generate (a subset of) all the validation criteria
############################################################################
/**
* This is the uber-validation function that calls everything below.
* It is suited for use from either the web or command line interface.
* This only makes sense in terms of an active session.
*   modelID             ID code for model to process
*   opts                has the following keys mapped to boolean flags:
*     doKinemage        make the multi-criterion kinemage at all?
*       kinClashes      show clash dots?
*       kinHbonds       show H-bond dots?
*       kinContacts     show contact dots?
*       kinRama         show Rama outliers?
*       kinRota         show rotamer outliers?
*       kinCBdev        show C-beta deviations?
*       kinBaseP        show base-phosphate perpendiculars?
*       kinSuite        show RNA backbone conformational outliers?
*       kinAltConfs     show alternate conformations?
*       kinBfactor      show B-factor color model?
*       kinOccupancy    show occupancy color model?
*       kinRibbons      show ribbons?
*       kinForceViews   force running clashlist, etc to provide @views of bad spots?
*     doCharts          make the multi-criterion chart and other plots/tables/lists?
*       chartClashlist  run clashlistcluster?
*       chartRama       do Rama plots and analysis?
*       chartRota       do rotamer analysis?
*       chartCBdev      do CB dev plots and analysis?
*       chartBaseP      check base-phosphate perpendiculars?
*       chartSuite      check RNA backbone conformations?
*       chartNotJustOut include residues that have no problems in the list?
*       chartImprove    compare to reduce -(no)build results to show improvement?
*       
* This function returns some HTML suitable for using in a lab notebook entry.
*/
function runAnalysis($modelID, $opts)
{
    //{{{ Set up file/directory vars and the task list
    // If doKinemage or doCharts is off, turn off all their subordinates
    if(!$opts['doKinemage']) foreach($opts as $k => $v) if(startsWith($k, 'kin')) $opts[$k] = false;
    if(!$opts['doCharts']) foreach($opts as $k => $v) if(startsWith($k, 'chart')) $opts[$k] = false;
    if($opts['kinForceViews']) foreach($opts as $k => $v) if(startsWith($k, 'chart')) $opts[$k] = true;
    
    $model      = $_SESSION['models'][$modelID];
    $modelDir   = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
    $modelURL   = $_SESSION['dataURL'].'/'.MP_DIR_MODELS;
    $kinDir     = $_SESSION['dataDir'].'/'.MP_DIR_KINS;
    $kinURL     = $_SESSION['dataURL'].'/'.MP_DIR_KINS;
        if(!file_exists($kinDir)) mkdir($kinDir, 0777);
    $rawDir     = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA;
        if(!file_exists($rawDir)) mkdir($rawDir, 0777);
    $chartDir   = $_SESSION['dataDir'].'/'.MP_DIR_CHARTS;
    $chartURL   = $_SESSION['dataURL'].'/'.MP_DIR_CHARTS;
        if(!file_exists($chartDir)) mkdir($chartDir, 0777);
    $infile     = "$modelDir/$model[pdb]";
    
    if($opts['chartRama'])      $tasks['rama'] = "Do Ramachandran analysis and make plots";
    if($opts['chartRota'])      $tasks['rota'] = "Do rotamer analysis";
    if($opts['chartCBdev'])     $tasks['cbeta'] = "Do C&beta; deviation analysis and make kins";
    if($opts['chartBaseP'])     $tasks['base-phos'] = "Do base-phosphate perpendicular analysis";
    if($opts['chartSuite'])     $tasks['suitename'] = "Do RNA backbone conformations analysis";
    
    if($opts['chartClashlist']) $tasks['clashlist'] = "Run <code>clashlist</code> to find bad clashes and clashscore";
    if($opts['chartImprove'])   $tasks['improve'] = "Suggest / report on fixes";
    if($opts['doCharts'])       $tasks['multichart'] = "Create multi-criterion chart";
    if($opts['doKinemage'])     $tasks['multikin'] = "Create multi-criterion kinemage";
    
    $doRem42 = $opts['chartClashlist'] || $opts['chartRama'] || $opts['chartRota'];
    if($doRem42)                $tasks['remark42'] = "Create REMARK 42 record for the PDB file";
    //}}} Set up file/directory vars and the task list
    
    //{{{ Run protein geometry programs and offer kins to user
    // Ramachandran
    if($opts['chartRama'])
    {
        setProgress($tasks, 'rama'); // updates the progress display if running as a background job
        $outfile = "$rawDir/$model[prefix]rama.data";
        runRamachandran($infile, $outfile);
        $rama = loadRamachandran($outfile);
        
        makeRamachandranKin($infile, "$kinDir/$model[prefix]rama.kin");
        $tasks['rama'] .= " - preview <a href='viewking.php?$_SESSION[sessTag]&url=$kinURL/$model[prefix]rama.kin' target='_blank'>kinemage</a>";
        setProgress($tasks, 'rama'); // so the preview link is visible
        makeRamachandranPDF($infile, "$chartDir/$model[prefix]rama.pdf");
        $tasks['rama'] .= " | <a href='$chartURL/$model[prefix]rama.pdf' target='_blank'>PDF</a>\n";
        setProgress($tasks, 'rama'); // so the preview link is visible
    }
    
    // Rotamers
    if($opts['chartRota'])
    {
        setProgress($tasks, 'rota'); // updates the progress display if running as a background job
        $outfile = "$rawDir/$model[prefix]rota.data";
        runRotamer($infile, $outfile);
        $rota = loadRotamer($outfile);
    }
    
    // C-beta deviations
    if($opts['chartCBdev'])
    {
        setProgress($tasks, 'cbeta'); // updates the progress display if running as a background job
        $outfile = "$rawDir/$model[prefix]cbdev.data";
        runCbetaDev($infile, $outfile);
        $cbdev = loadCbetaDev($outfile);
        
        makeCbetaDevPlot($infile, "$kinDir/$model[prefix]cbetadev.kin");
        $tasks['cbeta'] .= " - <a href='viewking.php?$_SESSION[sessTag]&url=$kinURL/$model[prefix]cbetadev.kin' target='_blank'>preview</a>";
        setProgress($tasks, 'cbeta'); // so the preview link is visible
    }
    //}}} Run programs and offer kins to user
    
    //{{{ Run nucleic acid geometry programs and offer kins to user
    // Base-phosphate perpendiculars
    if($opts['chartBaseP'])
    {
        setProgress($tasks, 'base-phos'); // updates the progress display if running as a background job
        $outfile = "$rawDir/$model[prefix]pperp.data";
        runBasePhosPerp($infile, $outfile);
        $pperp = loadBasePhosPerp($outfile);
    }
    if($opts['chartSuite'])
    {
        setProgress($tasks, 'suitename'); // updates the progress display if running as a background job
        $outfile = "$chartDir/$model[prefix]suitename.txt";
        runSuitenameReport($infile, $outfile);
        $suites = loadSuitenameReport($outfile);
        $tasks['suitename'] .= " - <a href='viewtext.php?$_SESSION[sessTag]&file=$outfile&mode=plain' target='_blank'>preview</a>\n";
        setProgress($tasks, 'suitename'); // so the preview link is visible
        
        $outfile = "$chartDir/$model[prefix]suitestring.txt";
        runSuitenameString($infile, $outfile);
    }
    //}}} Run nucleic acid geometry programs and offer kins to user
    
    //{{{ Run all-atom contact programs and offer kins to user
    // Clashes
    if($opts['chartClashlist'])
    {
        setProgress($tasks, 'clashlist'); // updates the progress display if running as a background job
        $outfile = "$chartDir/$model[prefix]clashlist.txt";
        runClashlist($infile, $outfile);
        $clash = loadClashlist($outfile);
        //$clashPct = runClashStats($model['stats']['resolution'], $clash['scoreAll'], $clash['scoreBlt40']);
        $tasks['clashlist'] .= " - <a href='viewtext.php?$_SESSION[sessTag]&file=$outfile&mode=plain' target='_blank'>preview</a>\n";
        setProgress($tasks, 'clashlist'); // so the preview link is visible
    }
    //}}} Run all-atom contact programs and offer kins to user
    
    //{{{ Report on improvements (that could be) made by MolProbity
    $improveText = "";
    if($opts['chartImprove'] && ($clash || $rota))
    {
        setProgress($tasks, 'improve'); // updates the progress display if running as a background job
        $altpdb = mpTempfile("tmp_altH_pdb_");
        $mainClashscore = ($clash ? $clash['scoreAll'] : 0);
        $mainRotaCount = ($rota ? count(findRotaOutliers($rota)) : 0);
        $improvementList = array();
        
        if($model['isBuilt']) // file has been through reduce -build or reduce -fix
        {
            $altInpath = $modelDir . '/'. $_SESSION['models'][ $model['parent'] ]['pdb'];
            reduceNoBuild($altInpath, $altpdb);
            // Rotamers
                $outfile = mpTempfile("tmp_rotamer_");
                runRotamer($altpdb, $outfile);
                $altrota = loadRotamer($outfile);
                $altRotaCount = count(findRotaOutliers($altrota));
                if($altRotaCount > $mainRotaCount)
                    $improvementList[] = "fixed ".($altRotaCount - $mainRotaCount)." bad rotamers";
                unlink($outfile);
            // Clashes
                $outfile = mpTempfile("tmp_clashlist_");
                runClashlist($altpdb, $outfile);
                $altclash = loadClashlist($outfile);
                if($altclash['scoreAll'] > $mainClashscore)
                    $improvementList[] = "improved your clashscore by ".($altclash['scoreAll'] - $mainClashscore)." points";
                unlink($outfile);
            if(count($improvementList) > 0)
            {
                $improveText .= "<div class='feature'>By adding H to this model and allowing Asn/Gln/His flips, you have already ";
                $improveText .= implode(" and ", $improvementList);
                $improveText .= ".  <b>Make sure you download the modified PDB to take advantage of these improvements!</b></div>\n";
            }
        }
        elseif($mainClashscore > 0 || $mainRotaCount > 0) // if file was run through reduce at all, flips were not allowed
        {
            if($model['parent']) $altInpath = $_SESSION['models'][ $model['parent'] ]['pdb'];
            else $altInpath = $model['pdb'];
            $altInpath = "$modelDir/$altInpath";
            reduceBuild($altInpath, $altpdb);
            if($mainRotaCount > 0)
            {
                $outfile = mpTempfile("tmp_rotamer_");
                runRotamer($altpdb, $outfile);
                $altrota = loadRotamer($outfile);
                $altRotaCount = count(findRotaOutliers($altrota));
                if($altRotaCount < $mainRotaCount)
                    $improvementList[] = "fix ".($mainRotaCount - $altRotaCount)." bad rotamers";
                 unlink($outfile);
           }
            if($mainClashscore > 0)
            {
                $outfile = mpTempfile("tmp_clashlist_");
                runClashlist($altpdb, $outfile);
                $altclash = loadClashlist($outfile);
                if($altclash['scoreAll'] < $mainClashscore)
                    $improvementList[] = "improve your clashscore by ".($mainClashscore - $altclash['scoreAll'])." points";
                unlink($outfile);
            }
            if(count($improvementList) > 0)
            {
                $improveText .= "<div class='feature'>By adding H to this model and allowing Asn/Gln/His flips, we could <i>automatically</i> ";
                $improveText .= implode(" and ", $improvementList);
                $improveText .= ".</div>\n";
            }
        }
        unlink($altpdb);
    }
    //}}} Report on improvements (that could be) made by by MolProbity
    
    //{{{ Build multi-criterion chart, kinemage
    if($opts['doCharts'])
    {
        setProgress($tasks, 'multichart'); // updates the progress display if running as a background job
        $outfile = "$rawDir/$model[prefix]multi.table";
        $snapfile = "$chartDir/$model[prefix]multi.html";
        writeMulticritChart($infile, $outfile, $snapfile, $clash, $rama, $rota, $cbdev, $pperp, $suites, !$opts['chartNotJustOut']);
        $tasks['multichart'] .= " - <a href='viewtable.php?$_SESSION[sessTag]&file=$outfile' target='_blank'>preview</a>\n";
        setProgress($tasks, 'multichart'); // so the preview link is visible
        $outfile = "$chartDir/$model[prefix]multi-coot.scm";
        #makeCootMulticritChart($infile, $outfile, $clash, $rama, $rota, $cbdev, $pperp);
        makeCootClusteredChart($infile, $outfile, $clash, $rama, $rota, $cbdev, $pperp);
    }
    if($opts['doKinemage'])
    {
        setProgress($tasks, 'multikin'); // updates the progress display if running as a background job
        $mcKinOpts = array(
            'ribbons'   =>  $opts['kinRibbons'],
            'Bscale'    =>  $opts['kinBfactor'],
            'Qscale'    =>  $opts['kinOccupancy'],
            'altconf'   =>  $opts['kinAltConfs'],
            'rama'      =>  $opts['kinRama'],
            'rota'      =>  $opts['kinRota'],
            'cbdev'     =>  $opts['kinCBdev'],
            'pperp'     =>  $opts['kinBaseP'],
            'clashdots' =>  $opts['kinClashes'],
            'hbdots'    =>  $opts['kinHbonds'],
            'vdwdots'   =>  $opts['kinContacts']
        );
        $outfile = "$kinDir/$model[prefix]multi.kin";
        makeMulticritKin2(array($infile), $outfile, $mcKinOpts,
        #    array_keys(findAllOutliers($clash, $rama, $rota, $cbdev, $pperp)));
            array_keys(calcLocalBadness($infile, 7, $clash, $rama, $rota, $cbdev, $pperp)));
        
        // EXPERIMENTAL: gzip compress large multikins
        if(filesize($outfile) > MP_KIN_GZIP_THRESHOLD)
        {
            destructiveGZipFile($outfile);
        }
    }
    //}}} Build multi-criterion chart, kinemage
    
    //{{{ Create REMARK 42 and insert into PDB file
    if(is_array($clash) || is_array($rama) || is_array($rota))
    {
        setProgress($tasks, 'remark42'); // updates the progress display if running as a background job
        $remark42 = makeRemark42($clash, $rama, $rota);
        replacePdbRemark($infile, $remark42, 42);
    }
    //}}} Create REMARK 42 and insert into PDB file
    
    //{{{ Create lab notebook entry
    $entry = "";
    if(is_array($clash) || is_array($rama) || is_array($rota) || is_array($cbdev) || is_array($pperp) || is_array($suites))
    {
        $entry .= "<h3>Summary statistics</h3>\n";
        $entry .= makeSummaryStatsTable($model['stats']['resolution'], $clash, $rama, $rota, $cbdev, $pperp, $suites);
    }
    $entry .= $improveText;
    if($opts['doKinemage'] || $opts['doCharts'])
    {
        $entry .= "<h3>Multi-criterion visualizations</h3>\n";
        $entry .= "<div class='indent'>\n";
        $entry .= "<table width='100%' border='0'>\n";
        if($opts['doKinemage'])
            $entry .= "<td>".linkAnyFile("$model[prefix]multi.kin", "Kinemage", "img/multikin.jpg")."</td>\n";
        if($opts['doCharts'])
        {
            $entry .= "<td>".linkAnyFile("$model[prefix]multi.table", "Chart", "img/multichart.jpg")."</td>\n";
            $entry .= "<td>".linkAnyFile("$model[prefix]multi-coot.scm", "To-do list for Coot")."<br><small><i>Open this in Coot 0.1.2 or later using Calculate | Run Script...</i></small></td>\n";
        }
        $entry .= "</tr></table>\n";
        $entry .= "</div>\n";
    }
    
    if($opts['chartClashlist'] || $opts['chartRama'] || $opts['chartCBdev'] || $opts['chartSuite'])
    {
        $entry .= "<h3>Single-criterion visualizations</h3>";
        $entry .= "<ul>\n";
        if($opts['chartClashlist'])
            $entry .= "<li>".linkAnyFile("$model[prefix]clashlist.txt", "Clash list")."</li>\n";
        if($opts['chartRama'])
        {
            $entry .= "<li>".linkAnyFile("$model[prefix]rama.kin", "Ramachandran plot kinemage")."</li>\n";
            $entry .= "<li>".linkAnyFile("$model[prefix]rama.pdf", "Ramachandran plot PDF")."</li>\n";
        }
        if($opts['chartCBdev'])
            $entry .= "<li>".linkAnyFile("$model[prefix]cbetadev.kin", "C&beta; deviation scatter plot")."</li>\n";
        if($opts['chartSuite'])
        {
            $entry .= "<li>".linkAnyFile("$model[prefix]suitename.txt", "RNA backbone report")."</li>\n";
            $entry .= "<li>".linkAnyFile("$model[prefix]suitestring.txt", "RNA backbone conformation \"sequence\"")."</li>\n";
        }
        $entry .= "</ul>\n";
    }
    
    if($remark42)
    {
        $entry .= "<h3>REMARK 42</h3>";
        $url = "$modelURL/$model[pdb]";
        $entry .= "You can <a href='$url'>download your PDB file with REMARK 42</a> inserted, or the same <a href='download_trimmed.php?$_SESSION[sessTag]&file=$infile'> without hydrogens</a>.\n";
        $entry .= "<p><pre>$remark42</pre></p>";
    }
    //}}} Create lab notebook entry
    
    setProgress($tasks, null); // everything is finished
    return $entry;
}
#}}}########################################################################

#{{{ runBasePhosPerp - generate tab file of base-phos perp distances
############################################################################
function runBasePhosPerp($infile, $outfile)
{
    exec("prekin -pperptoline -pperpdump $infile > $outfile");
}
#}}}########################################################################

#{{{ loadBasePhosPerp - load base-phos perp data into an array
############################################################################
/**
* Returns an array of entries, one per residue. Their keys:
*   resName         a formatted name for the residue: 'cnnnnittt'
*                       c: Chain ID, space for none
*                       n: sequence number, right justified, space padded
*                       i: insertion code, space for none
*                       t: residue type (ALA, LYS, etc.), all caps,
*                          left justified, space padded
*   resType         3-letter residue code (e.g. ALA)
*   chainID         1-letter chain ID or ' '
*   resNum          residue number
*   insCode         insertion code or ' '
*   5Pdist          distance from the base to the 5' phosphate (?)
*   3Pdist          distance from the base to the 3' phosphate (?)
*   delta           delta angle of the sugar ring
*   deltaOut        true if the sugar pucker (delta) doesn't match dist to 3' P
*   epsilon         epsilon angle of the backbone
*   epsilonOut      true if the epsilon angle is out of the allowed range
*   outlier         (deltaOut || epsilonOut)
*/
function loadBasePhosPerp($datafile)
{
    $data = file($datafile);
    foreach($data as $line)
    {
        $line = trim($line);
        if($line != "" && !startsWith($line, ':pdb:res:'))
        {
            $line = explode(':', $line);
            $deltaOut = (trim($line[8]) ? true : false);
            $epsilonOut = (trim($line[10]) ? true : false);
            $entry = array(
                'resType'   => strtoupper(substr($line[2],1,-1)),
                'chainID'   => strtoupper(substr($line[3],1,-1)),
                'resNum'    => trim(substr($line[4], 0, -1)) + 0,
                'insCode'   => substr($line[4], -1),
                '5Pdist'    => $line[5] + 0,
                '3Pdist'    => $line[6] + 0,
                'delta'     => $line[7] + 0,
                'deltaOut'  => $deltaOut,
                'epsilon'   => $line[9] + 0,
                'epsilonOut'=> $epsilonOut,
                'outlier'   => ($deltaOut || $epsilonOut)
            );
            $entry['resName']   = $entry['chainID']
                                . str_pad($entry['resNum'], 4, ' ', STR_PAD_LEFT)
                                . $entry['insCode']
                                . str_pad($entry['resType'], 3, ' ', STR_PAD_RIGHT);
            $ret[] = $entry;
        }
    }
    return $ret;
}
#}}}########################################################################

#{{{ findBasePhosPerpOutliers - evaluates residues for bad score
############################################################################
/**
* Returns an array of 9-char residue names for residues that
* fall outside the allowed boundaries for this criteria.
* Inputs are from appropriate loadXXX() function above.
*/
function findBasePhosPerpOutliers($input)
{
    $worst = array();
    if(is_array($input)) foreach($input as $res)
    {
        if($res['outlier'])
            $worst[$res['resName']] = $res['resName'];
    }
    ksort($worst); // Put the residues into a sensible order
    return $worst;
}
#}}}########################################################################

#{{{ runCbetaDev - generates numeric info about CB deviations
############################################################################
function runCbetaDev($infile, $outfile)
{
    exec("prekin -cbdevdump $infile > $outfile");
}
#}}}########################################################################

#{{{ loadCbetaDev - loads Prekin cbdevdump output into an array
############################################################################
/**
* Returns an array of entries, one per residue. Their keys:
*   altConf         alternate conformer flag, or ' ' for none
*   resName         a formatted name for the residue: 'cnnnnittt'
*                       c: Chain ID, space for none
*                       n: sequence number, right justified, space padded
*                       i: insertion code, space for none
*                       t: residue type (ALA, LYS, etc.), all caps,
*                          left justified, space padded
*   resType         3-letter residue code (e.g. ALA)
*   chainID         1-letter chain ID or ' '
*   resNum          residue number
*   insCode         insertion code or ' '
*   dev             deviation distance, in Angstroms
*   dihedral        N-CA-idealCB-actualCB angle, in degrees
*   occ             occupancy, between 0 and 1
*/
function loadCbetaDev($datafile)
{
    $data = file($datafile);
    foreach($data as $line)
    {
        $line = trim($line);
        if($line != "" && !startsWith($line, 'pdb:alt:res:'))
        {
            $line = explode(':', $line);
            $entry = array(
                'altConf'   => strtoupper($line[1]),
                'resType'   => strtoupper($line[2]),
                'chainID'   => strtoupper($line[3]),
                'resNum'    => trim(substr($line[4], 0, -1)) + 0,
                'insCode'   => substr($line[4], -1),
                'dev'       => $line[5] + 0,
                'dihedral'  => $line[6] + 0,
                'occ'       => $line[7] + 0
            );
            $entry['resName']   = $entry['chainID']
                                . str_pad($entry['resNum'], 4, ' ', STR_PAD_LEFT)
                                . $entry['insCode']
                                . str_pad($entry['resType'], 3, ' ', STR_PAD_RIGHT);
            $ret[] = $entry;
        }
    }
    return $ret;
}
#}}}########################################################################

#{{{ findCbetaOutliers - evaluates residues for bad score
############################################################################
/**
* Returns an array of 9-char residue names for residues that
* fall outside the allowed boundaries for this criteria.
* Inputs are from appropriate loadXXX() function above.
*/
function findCbetaOutliers($cbdev)
{
    $worst = array();
    if(is_array($cbdev)) foreach($cbdev as $res)
    {
        if($res['dev'] >= 0.25)
            $worst[$res['resName']] = $res['dev'];
    }
    ksort($worst); // Put the residues into a sensible order
    return $worst;
}
#}}}########################################################################

#{{{ calcCbetaStats - calculates max, mean, and median deviation
############################################################################
/**
* Accepts the data structure created by loadCbetaDev()
* Returns an array with the keys 'max', 'mean', 'median'
*/
function calcCbetaStats($cbdev)
{
    if(!(is_array($cbdev) && count($cbdev) >= 2)) return array();
    
    foreach($cbdev as $cb)
    {
        $dev[] = $cb['dev'];
        $sum += $cb['dev'];
    }
    sort($dev);
    
    $len = count($dev);
    $s['max']   = $dev[ $len-1 ];
    $s['mean']  = $mean = $sum / $len;
    
    $half = intval($len/2);
    if($len % 2 == 0)   $s['median'] = ($dev[$half] + $dev[$half-1]) / 2;
    else                $s['median'] = $dev[$half];
    
    return $s;
}
#}}}########################################################################

#{{{ runClashlist - generates clash data with Clashlist
############################################################################
function runClashlist($infile, $outfile)
{
    exec("clashlist $infile > $outfile");
}
#}}}########################################################################

#{{{ loadClashlist - loads Clashlist output into an array
############################################################################
/**
* Returns an array with the following keys:
*   scoreAll        the overall clashscore
*   scoreBlt40      the score for atoms with B < 40
*   clashes         an array with 'cnnnnittt' residue names as keys
*                   (see loadCbetaDev() for explanation of naming)
*                   and maximum clashes as values (positive Angstroms).
*                   NB: only clashes >= 0.40A are currently listed.
*   clashes-with    same keys as "clashes", values are:
*                       'srcatom' => atom from this residue making bigest clash
*                       'dstatom' => atom it clashes with
*                       'dstcnit' => chain/residue it clashes with
*/
function loadClashlist($datafile)
{
    $data = file($datafile);
    $sum = array_values(array_slice($data, -2)); // last 2 lines with new indexes
    $scores = explode(':', $sum[0]);
    if(startsWith($sum[0], "#sum2")) // in case no clashes, thus no summary
    {
        $ret['scoreAll']    = $scores[2] + 0;
        $ret['scoreBlt40']  = $scores[3] + 0;
    }
    
    // Parse data about individual clashes
    $clashes = array(); // in case there are no clashes
    $clashes_with = array();
    foreach($data as $datum)
    {
        // Ignore blank lines and #sum... lines
        // That leaves lines starting with colons.
        if($datum{0} == ':')
        {
            $line = explode(':', $datum);
            $res1 = substr($line[2], 0, 9);
            $atm1 = substr($line[2], 10, 5);
            $res2 = substr($line[3], 0, 9);
            $atm2 = substr($line[3], 10, 5);
            $dist = abs(trim($line[4])+0);
            if(!isset($clashes[$res1]) || $clashes[$res1] < $dist)
            {
                $clashes[$res1] = $dist;
                $clashes_with[$res1] = array('srcatom' => $atm1, 'dstatom' => $atm2, 'dstcnit' => $res2); 
            }
            if(!isset($clashes[$res2]) || $clashes[$res2] < $dist)
            {
                $clashes[$res2] = $dist;
                $clashes_with[$res2] = array('srcatom' => $atm2, 'dstatom' => $atm1, 'dstcnit' => $res1); 
            }
        }
    }
    $ret['clashes'] = $clashes;
    $ret['clashes-with'] = $clashes_with;
    
    return $ret;
}
#}}}########################################################################

#{{{ findClashOutliers - evaluates residues for bad score
############################################################################
/**
* Returns an array of 9-char residue names for residues that
* fall outside the allowed boundaries for this criteria.
* Inputs are from appropriate loadXXX() function above.
*/
function findClashOutliers($clash)
{
    $worst = array();
    if(is_array($clash)) foreach($clash['clashes'] as $res => $dist)
    {
        if($dist >= 0.4)
            $worst[$res] = $dist;
    }
    ksort($worst); // Put the residues into a sensible order
    return $worst;
}
#}}}########################################################################

#{{{ runClashStats - percentile ranks a clashscore vs a "reprsentative" database
############################################################################
/**
* Returns an array with the following fields:
*   minresol        minimum resolution considered comparable, or 0 for OOB
*   maxresol        maximum resolution considered comparable, or 9999 for OOB
*   n_samples       number of structures in the comparison group (i.e., "N")
*   pct_rank        the percentile rank of this structure, 100 is great, 0 is bad
*   pct_rank40      the percentile rank of this structure for B < 40
*/
function runClashStats($resol, $clashscore, $clashscoreBlt40)
{
    // Determine the percentile rank of this structure
    // fields are minres, maxres, n_samples, pct_rank
    $bin = MP_BASE_DIR.'/bin';
    $lib = MP_BASE_DIR.'/lib';
    $cmd = "gawk -v res=$resol -v cs=$clashscore -v cs40=$clashscoreBlt40 -f $bin/cs-rank.awk $lib/clashscore.db.tab";
    //echo $cmd."\n";
    $fields = explode(":", trim(shell_exec($cmd)));
    //print_r($fields);
    return array(
        'minresol'      => $fields[0],
        'maxresol'      => $fields[1],
        'n_samples'     => $fields[2],
        'pct_rank'      => $fields[3],
        'pct_rank40'    => $fields[4]
    );
}
#}}}########################################################################

#{{{ runRotamer - generates rotamer analysis data
############################################################################
function runRotamer($infile, $outfile)
{
    // Very large files (1htq) need extra memory
    //exec("java -Xmx256m -cp ".MP_BASE_DIR."/lib/hless.jar hless.Rotamer -raw $infile > $outfile");
    exec("java -Xmx256m -cp ".MP_BASE_DIR."/lib/chiropraxis.jar chiropraxis.rotarama.Rotalyze $infile > $outfile");
}
#}}}########################################################################

#{{{ loadRotamer - loads Rotamer output into an array
############################################################################
/**
* Returns an array of entries keyed on CNIT name, one per residue.
* Each entry is an array with these keys:
*   resName         a formatted name for the residue: 'cnnnnittt'
*                       c: Chain ID, space for none
*                       n: sequence number, right justified, space padded
*                       i: insertion code, space for none
*                       t: residue type (ALA, LYS, etc.), all caps,
*                          left justified, space padded
*   resType         3-letter residue code (e.g. ALA)
*   chainID         1-letter chain ID or ' '
*   resNum          residue number
*   insCode         insertion code or ' '
*   scorePct        the percentage score from 0 (bad) to 100 (good)
*   chi1            the chi-1 angle
*   chi2            the chi-2 angle ("" for none)
*   chi3            the chi-3 angle ("" for none)
*   chi4            the chi-4 angle ("" for none)
*   rotamer         the rotamer name from the Penultimate Rotamer Library
*/
function loadRotamer($datafile)
{
    $data = array_slice(file($datafile), 1); // drop first line
    $ret = array();
    foreach($data as $line)
    {
        $line = explode(':', rtrim($line));
        $cnit = $line[0];
        $decomp = decomposeResName($cnit);
        $ret[$cnit] = array(
            'resName'   => $cnit,
            'resType'   => $decomp['resType'],
            'chainID'   => $decomp['chainID'],
            'resNum'    => $decomp['resNum'],
            'insCode'   => $decomp['insCode'],
            'scorePct'  => $line[1] + 0,
            'chi1'      => $line[2],
            'chi2'      => $line[3],
            'chi3'      => $line[4],
            'chi4'      => $line[5],
            'rotamer'   => $line[6]
        );
        // This converts numbers to numbers and leaves "" as it is.
        if($ret[$cnit]['chi1'] !== '') $ret[$cnit]['chi1'] += 0;
        if($ret[$cnit]['chi2'] !== '') $ret[$cnit]['chi2'] += 0;
        if($ret[$cnit]['chi3'] !== '') $ret[$cnit]['chi3'] += 0;
        if($ret[$cnit]['chi4'] !== '') $ret[$cnit]['chi4'] += 0;
    }
    return $ret;
}
#}}}########################################################################

#{{{ findRotaOutliers - evaluates residues for bad score
############################################################################
/**
* Returns an array of 9-char residue names for residues that
* fall outside the allowed boundaries for this criteria.
* Inputs are from appropriate loadXXX() function above.
*/
function findRotaOutliers($rota)
{
    $worst = array();
    if(is_array($rota)) foreach($rota as $res)
    {
        if($res['scorePct'] <= 1.0)
            $worst[$res['resName']] = $res['scorePct'];
    }
    ksort($worst); // Put the residues into a sensible order
    return $worst;
}
#}}}########################################################################

#{{{ runRamachandran - generates rotamer analysis data
############################################################################
function runRamachandran($infile, $outfile)
{
    exec("java -Xmx256m -cp ".MP_BASE_DIR."/lib/hless.jar hless.Ramachandran -nokin -raw $infile > $outfile");
}
#}}}########################################################################

#{{{ loadRamachandran - loads Ramachandran output into an array
############################################################################
/**
* Returns an array of entries, one per residue. Their keys:
*   resName         a formatted name for the residue: 'cnnnnittt'
*                       c: Chain ID, space for none
*                       n: sequence number, right justified, space padded
*                       i: insertion code, space for none
*                       t: residue type (ALA, LYS, etc.), all caps,
*                          left justified, space padded
*   resType         3-letter residue code (e.g. ALA)
*   chainID         1-letter chain ID or ' '
*   resNum          residue number
*   insCode         insertion code or ' '
*   scorePct        the percentage score from 0 (bad) to 100 (good)
*   phi             the phi angle
*   psi             the psi angle
*   eval            "Favored", "Allowed", or "OUTLIER"
*   type            "General case", "Glycine", "Proline", or "Pre-proline"
*/
function loadRamachandran($datafile)
{
    $data = array_slice(file($datafile), 1); // drop first line
    $ret = array();
    foreach($data as $line)
    {
        $line = explode(':', rtrim($line));
        $cnit = $line[0];
        $decomp = decomposeResName($cnit);
        $ret[$cnit] = array(
            'resName'   => $cnit,
            'resType'   => $decomp['resType'],
            'chainID'   => $decomp['chainID'],
            'resNum'    => $decomp['resNum'],
            'insCode'   => $decomp['insCode'],
            'scorePct'  => $line[1] + 0,
            'phi'       => $line[2] + 0,
            'psi'       => $line[3] + 0,
            'eval'      => $line[4],
            'type'      => $line[5]
        );
    }
    return $ret;
}
#}}}########################################################################

#{{{ findRamaOutliers - evaluates residues for bad score
############################################################################
/**
* Returns an array of 9-char residue names for residues that
* fall outside the allowed boundaries for this criteria.
* Inputs are from appropriate loadXXX() function above.
*/
function findRamaOutliers($rama)
{
    $worst = array();
    if(is_array($rama)) foreach($rama as $res)
    {
        if($res['eval'] == 'OUTLIER')
            $worst[$res['resName']] = $res['eval'];
    }
    ksort($worst); // Put the residues into a sensible order
    return $worst;
}
#}}}########################################################################

#{{{ runSuitenameReport - finds conformer and suiteness for every RNA suite
############################################################################
function runSuitenameReport($infile, $outfile)
{
    exec("java -Xmx256m -cp ".MP_BASE_DIR."/lib/chiropraxis.jar chiropraxis.dangle.Dangle rnabb $infile | suitename -report > $outfile");
}
#}}}########################################################################

#{{{ loadSuitenameReport - loads Suitename's -report output into an array
############################################################################
/**
* Returns an array of entries keyed on CNIT name, one per residue.
* Each entry is an array with these keys:
*   resName         a formatted name for the residue: 'cnnnnittt'
*                       c: Chain ID, space for none
*                       n: sequence number, right justified, space padded
*                       i: insertion code, space for none
*                       t: residue type (ALA, LYS, etc.), all caps,
*                          left justified, space padded
*   conformer       two letter code (mixed case -- '1L' is legal) or "__"
*   suiteness       real number, 0 - 1
*   bin             "inc " (incomplete), "trig" (triaged), or something like "23 p"
*   isOutlier       true if conformer = !!
*/
function loadSuitenameReport($datafile)
{
#tr0001.pdb:1:A:   1: :  G inc  __ 0.000
#tr0001.pdb:1:A:   2: :  C 33 p 1a 0.935
#tr0001.pdb:1:A:  10: :2MG 23 p !! 0.000
#tr0001.pdb:1:A:  13: :  C 33 t 1c 0.824
#tr0001.pdb:1:A:  14: :  A trig !! 0.000
    $data = file($datafile);
    $ret = array();
    foreach($data as $line)
    {
        if(startsWith($line, " all general case widths")) break;
        $line = explode(':', rtrim($line));
        $cnit = $line[2].$line[3].$line[4].substr($line[5],0,3);
        //$decomp = decomposeResName($cnit);
        $conf = substr($line[5],9,2);
        $ret[$cnit] = array(
            'resName'   => $cnit,
            //'resType'   => $decomp['resType'],
            //'chainID'   => $decomp['chainID'],
            //'resNum'    => $decomp['resNum'],
            //'insCode'   => $decomp['insCode'],
            'conformer' => $conf,
            'suiteness' => substr($line[5],12) + 0,
            'bin'       => substr($line[5],4,4),
            'isOutlier' => ($conf == '!!')
        );
    }
    return $ret;
}
#}}}########################################################################

#{{{ findSuitenameOutliers - evaluates residues for bad score
############################################################################
/**
* Returns an array of 9-char residue names for residues that
* fall outside the allowed boundaries for this criteria.
* Inputs are from appropriate loadXXX() function above.
*/
function findSuitenameOutliers($suites)
{
    $worst = array();
    if(is_array($suites)) foreach($suites as $res)
    {
        if($res['isOutlier'])
            $worst[$res['resName']] = $res['suiteness'];
    }
    ksort($worst); // Put the residues into a sensible order
    return $worst;
}
#}}}########################################################################

#{{{ runSuitenameString - writes the sequence+structure string for RNA
############################################################################
function runSuitenameString($infile, $outfile)
{
    // Unix "fold" is used to wrap long lines to reasonable lengths,
    // so they display OK in the <PRE> region of the HTML page.
    // 60 was selected because it makes counting to specific positions easier (20 suites/line)
    exec("java -Xmx256m -cp ".MP_BASE_DIR."/lib/chiropraxis.jar chiropraxis.dangle.Dangle rnabb $infile | suitename -string -oneline | fold -w 60 > $outfile");
}
#}}}########################################################################

#{{{ findAllOutliers - clash, Rama, rota, Cb, P-perp
############################################################################
/**
* Composites the results of the find___Outliers() functions into a simple,
* non-redundant list of 9-char residues names (keys)
* and counts of outliers (values).
*/
function findAllOutliers($clash, $rama, $rota, $cbdev, $pperp)
{
    $worst = array();
    #$worst = array_merge($worst, findClashOutliers($clash));
    #$worst = array_merge($worst, findRamaOutliers($rama));
    #$worst = array_merge($worst, findRotaOutliers($rota));
    #$worst = array_merge($worst, findCbetaOutliers($cbdev));
    #$worst = array_merge($worst, findBasePhosPerpOutliers($pperp));
    foreach(array(
        findClashOutliers($clash),
        findRamaOutliers($rama),
        findRotaOutliers($rota),
        findCbetaOutliers($cbdev),
        findBasePhosPerpOutliers($pperp),
        ) as $criterion)
    {
        if(is_array($criterion)) foreach($criterion as $cnit => $dummy)
            $worst[$cnit] += 1;
    }
    ksort($worst); // Put the residues into a sensible order
    #return array_keys($worst);
    return $worst;
}
#}}}########################################################################

#{{{ calcLocalBadness - number of outliers "near" each residue
############################################################################
/**
* Count the number of outliers that occur for this residue and other
* residues whose centroid is within some number of Anstroms of this one.
*
* $range    is the max distance between two residue centroids
* $clash    is the data structure from loadClashlist()
* $rama     is the data structure from loadRamachandran()
* $rota     is the data structure from loadRotamer()
* $cbdev    is the data structure from loadCbetaDev()
* $pperp    is the data structure from loadBasePhosPerp()
* Any of them can be set to null if the data is unavailable.
*/
function calcLocalBadness($infile, $range, $clash, $rama, $rota, $cbdev, $pperp)
{
    $res_xyz = computeResCenters($infile);
    $self_bads = findAllOutliers($clash, $rama, $rota, $cbdev, $pperp);
    #var_export($self_bads); echo "\n==========\n";
    $range2 = $range * $range;
    $worst_res = array();
    while(true)
    {
        $local_bads = array();
        foreach($res_xyz as $cnit => $xyz)
        {
            #$local_bads[$cnit] = 0;
            foreach($self_bads as $cnit2 => $bads)
            {
                $xyz2 = $res_xyz[$cnit2];
                $dx = $xyz['x'] - $xyz2['x'];
                $dy = $xyz['y'] - $xyz2['y'];
                $dz = $xyz['z'] - $xyz2['z'];
                if($dx*$dx + $dy*$dy + $dz*$dz <= $range2)
                    $local_bads[$cnit] += $bads;
            }
        }
        // Get worst residue from list and its count of bads
        asort($local_bads); // put worst residue last
        #var_export($local_bads); echo "\n==========\n";
        end($local_bads); // go to last element
        list($worst_cnit, $bad_count) = each($local_bads); // get last element
        // Only singletons left (for efficiency)
        // Also ensures that singletons are listed under their "owner"
        if($bad_count <= 1)
        {
            foreach($self_bads as $cnit => $bads)
                if($bads > 0) $worst_res[$cnit] = $bads;
            break;
        }
        // else ...
        #var_export($local_bads);
        #echo "\nRemoving $worst_cnit with $bad_count bads...\n==========\n";
        $worst_res[$worst_cnit] = $bad_count; // record it as the worst one this pass
        // Discard all bads that went to making the worst, the worst;
        // then re-run the algorithm to find the next worst, until no bads left.
        $cnit = $worst_cnit;
        $xyz = $res_xyz[$cnit];
        $leftover_bads = 0;
        foreach($self_bads as $cnit2 => $bads)
        {
            $xyz2 = $res_xyz[$cnit2];
            $dx = $xyz['x'] - $xyz2['x'];
            $dy = $xyz['y'] - $xyz2['y'];
            $dz = $xyz['z'] - $xyz2['z'];
            if($dx*$dx + $dy*$dy + $dz*$dz <= $range2)
                #$self_bads[$cnit2] = 0;
                unset($self_bads[$cnit2]); // faster than 0 -- won't traverse again
            else
                $leftover_bads += $bads;
        }
        if($leftover_bads == 0) break;
        #$cycles++;
        #if($cycles > 100) break;
    }
    #var_export($worst_res); echo "\n==========\n";
    return $worst_res;
}
#}}}########################################################################

#{{{ decomposeResName - breaks a 9-character packed name into pieces
############################################################################
/**
* Decomposes this:
*   resName         a formatted name for the residue: 'cnnnnittt'
*                       c: Chain ID, space for none
*                       n: sequence number, right justified, space padded
*                       i: insertion code, space for none
*                       t: residue type (ALA, LYS, etc.), all caps,
*                          left justified, space padded
*
* Into this (as an array):
*   resType         3-letter residue code (e.g. ALA)
*   chainID         1-letter chain ID or ' '
*   resNum          residue number
*   insCode         insertion code or ' '
*/
function decomposeResName($name)
{
    return array(
        'resType'   => substr($name, 6, 3),
        'chainID'   => substr($name, 0, 1),
        'resNum'    => trim(substr($name, 1, 4))+0,
        'insCode'   => substr($name, 5, 1)
    );
}
#}}}########################################################################

#{{{ pdbComposeResName - makes a 9-char res ID from a PDB ATOM line
############################################################################
function pdbComposeResName($pdbline)
{
    return substr($pdbline, 21, 6) . substr($pdbline, 17, 3);
}
#}}}########################################################################

#{{{ findAltConfs - parses a PDB file for residues with mc and/or sc alts
############################################################################
/**
* Returns NULL if the file could not be read.
* Otherwise, returns an array of arrays of booleans.
* First key is 'mc', 'sc', or 'all';
* second key is the 9-char residue ID.
*/
function findAltConfs($infile)
{
    $mcAtoms = array(" N  " => true, " CA " => true, " C  " => true, " O  " => true,
        " H  " => true, " HA " => true, "1HA " => true, "2HA " => true);
    
    $out = array('all' => array(), 'mc' => array(), 'sc' => array());
    $in = fopen($infile, "r");
    if(!$in) return NULL;
    while(!feof($in))
    {
        $s = fgets($in, 1024);
        $alt = $s{16};
        if($alt != ' ' && (startsWith($s, "ATOM") || startsWith($s, "HETATM")))
        {
            $res    = pdbComposeResName($s);
            $atom   = substr($s, 12, 4);
            $out['all'][$res] = true;
            
            if($mcAtoms[$atom])
                $out['mc'][$res] = true;
            else
                $out['sc'][$res] = true;
        }
    }
    fclose($in);
    
    return $out;
}
#}}}########################################################################

#{{{ listResidues - lists CNIT codes for all residues in a PDB file
############################################################################
/**
* Returns NULL if the file could not be read.
* Otherwise, an array of CNIT 9-char residue codes (in keys and values).
* Does not account for the possibility of multiple MODELs
*/
function listResidues($infile)
{
    $out = array();
    
    $in = fopen($infile, "r");
    if(!$in) return NULL;
    while(!feof($in))
    {
        $s = fgets($in, 1024);
        if(startsWith($s, "ATOM") || startsWith($s, "HETATM"))
        {
            $res = pdbComposeResName($s);
            $out[$res] = $res;
        }
    }
    fclose($in);

    return $out;
}
#}}}########################################################################

#{{{ listProteinResidues - lists CNIT codes for amino acid residues in a PDB
############################################################################
/**
* Returns NULL if the file could not be read.
* Otherwise, an array of CNIT 9-char residue codes.
* Does not account for the possibility of multiple MODELs
*/
function listProteinResidues($infile)
{
    $protein = array('GLY'=>1, 'ALA'=>1, 'VAL'=>1, 'LEU'=>1, 'ILE'=>1, 'PRO'=>1,
        'PHE'=>1, 'TYR'=>1, 'TRP'=>1, 'SER'=>1, 'THR'=>1, 'CYS'=>1, 'MET'=>1,
        'MSE'=>1, 'LYS'=>1, 'HIS'=>1, 'ARG'=>1, 'ASP'=>1, 'ASN'=>1, 'GLN'=>1,
        'GLU'=>1);
    $out = array();
    
    $in = fopen($infile, "r");
    if(!$in) return NULL;
    while(!feof($in))
    {
        $s = fgets($in, 1024);
        if(startsWith($s, "ATOM") || startsWith($s, "HETATM"))
        {
            $res = pdbComposeResName($s);
            if(isset($protein[substr($res,6,3)]))
                $out[$res] = $res;
        }
    }
    fclose($in);

    return $out;
}
#}}}########################################################################

#{{{ listResidueBfactors - lists highest B-factor for each residue in a PDB file
############################################################################
/**
* Returns NULL if the file could not be read.
* Otherwise, an array with one key ('res') which points to another array
* mapping CNIT codes to highest B-factor in any atom of the residue.
* Another top-level key ('mc') has been added, with the highest B for
* (protein) mainchain atoms.
* In the future, other top-level keys may be added, like 'sc'.
* Does not account for the possibility of multiple MODELs.
*/
function listResidueBfactors($infile)
{
    $res = array();
    $mc = array();
    
    $in = fopen($infile, "r");
    if(!$in) return NULL;
    while(!feof($in))
    {
        $s = fgets($in, 1024);
        if(startsWith($s, "ATOM") || startsWith($s, "HETATM"))
        {
            $cnit = pdbComposeResName($s);
            $Bfact = substr($s, 60, 6) + 0;
            $res[$cnit] = max($Bfact, $res[$cnit]);
            $atom = substr($s, 12, 4);
            if(preg_match('/ N  | CA | C  | O  /', $atom))
                $mc[$cnit] = max($Bfact, $mc[$cnit]);
        }
    }
    fclose($in);

    return array('res' => $res, 'mc' => $mc);
}
#}}}########################################################################

#{{{ computeResCenters - finds (x,y,z) for residue (pseudo) center-of-mass from PDB
############################################################################
/**
* Returns NULL if the file could not be read.
* Otherwise, an array of arrays
* where the first key is the 9-char residue code
* and the second key is 'x', 'y', or 'z'.
* Does not account for the possibility of multiple MODELs
*/
function computeResCenters($infile)
{
    $out = array(); // x, y, z
    $cnt = array(); // how many atoms have been tallied
    
    $in = fopen($infile, "r");
    if(!$in) return NULL;
    while(!feof($in))
    {
        $s = fgets($in, 1024);
        if(startsWith($s, "ATOM") || startsWith($s, "HETATM"))
        {
            $res = pdbComposeResName($s);
            $out[$res]['x'] += substr($s, 30, 8) + 0.0;
            $out[$res]['y'] += substr($s, 38, 8) + 0.0;
            $out[$res]['z'] += substr($s, 46, 8) + 0.0;
            $cnt[$res]      += 1;
        }
    }
    fclose($in);
    
    foreach($cnt as $res => $num)
    {
        $out[$res]['x'] /= $num;
        $out[$res]['y'] /= $num;
        $out[$res]['z'] /= $num;
    }
    
    return $out;
}
#}}}########################################################################

#{{{ groupAdjacentRes - structures a list of residues into chains and "runs"
############################################################################
/**
* Given a list of 9-char residue codes as the values (not keys) of an array,
* a new data structure is created where
* the first index is a one-char chain ID,
* the second index is an arbitrary run number,
* the third index is arbitrary, and
* the value is the 9-char residue code.
* The so-called "runs" are just residues that were adjacent in the input list
* and had sequence numbers that differed by 1 (or 0).
*/
function groupAdjacentRes($resList)
{
    $out = array();
    if(is_array($resList)) foreach($resList as $res)
    {
        $num = substr($res, 1, 4) + 0;
        // If old run is ending, append it and start fresh:
        if(isset($run) && !($num - $prevNum <= 1 && $chainID == $res{0}))
        {
            $out[$chainID][] = $run;
            unset($run);
        }
        // Append this residue to the current run (which is potentially empty)
        $prevNum    = $num;
        $chainID    = $res{0};
        $run[]      = $res;
    }
    
    // Append the last run, if any
    if(isset($run)) $out[$chainID][] = $run;
    
    return $out;
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################
?>
