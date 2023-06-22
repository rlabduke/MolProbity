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
*       kinGeom         show bond length and angle outliers?
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
*       chartGeom       do bond length and angle outliers?
*       chartCBdev      do CB dev plots and analysis?
*       chartBaseP      check base-phosphate perpendiculars?
*       chartSuite      check RNA backbone conformations?
*       chartHoriz      do horizontal chart?
*       chartCoot       do coot chart?
*       chartMulti      do html multi chart?
*       chartNotJustOut include residues that have no problems in the list?
*       chartAltloc     remove redundant residue rows when altlocs present?
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
    $xrayDir    = $_SESSION['dataDir'].'/'.MP_DIR_XRAYDATA;
    $infile     = "$modelDir/$model[pdb]";
    $reduce_blength = $_SESSION['reduce_blength'];
    if(isset($model['mtz_file']))
        $mtz_file = $model['mtz_file'];
    else $mtz_file = $_SESSION['models'][$model['parent']]['mtz_file'];

    if(is_array($opts['doEnsemble'])) {
      $ensemble = $opts['doEnsemble'];
      echo "Ensemble mode set for runAnalysis for ".$model[pdb]."\n";
      //var_dump($ensemble);
      $tasks['currentModel'] = "Analyzing $model[pdb] out of ".count($ensemble["models"]);
      setProgress($tasks, 'currentModel');
    }

    if($model['stats']['use_cdl'])  $geomsg = "Using CDL";
    else $geomsg = "";
    if($opts['chartGeom'])      $tasks['geomValidation'] = "Do bond length and angle geometry analysis (<code>mp_geo</code>) $geomsg";
    if($opts['chartRama'])      $tasks['rama'] = "Do Ramachandran analysis and make plots (<code>ramalyze</code>)";
    if($opts['chartRota'])      $tasks['rota'] = "Do rotamer analysis (<code>rotalyze</code>)";
    if($opts['chartCBdev'])     $tasks['cbeta'] = "Do C&beta; deviation analysis and make kins (<code>cbetadev</code>)";
    if($opts['chartOmega'])     $tasks['omega'] = "Do cis-peptide analysis (<code>omegalyze</code>)";
    if($opts['chartCablamLow']) $tasks['cablam'] = "Do CaBLAM analysis (<code>cablam_validate</code>)";
    if($opts['chartBaseP'])     $tasks['base-phos'] = "Do RNA sugar pucker analysis";
    if($opts['chartSuite'])     $tasks['suitename'] = "Do RNA backbone conformations analysis";

    if($opts['chartClashlist']) $tasks['clashlist'] = "Run <code>clashscore</code> to find bad clashes and clashscore";
    if($opts['chartImprove'])   $tasks['improve'] = "Suggest / report on fixes";
    if($opts['doCharts']&&!$opts['chartMulti'])       $tasks['chartsummary'] = "Create summary chart";
    if($opts['chartMulti'])     $tasks['multichart'] = "Create multi-criterion chart";
    if($opts['chartHoriz'])
    {
        $tasks['runRSCC'] = "Run real-space correlation";
        $tasks['charthoriz'] = "Create horizontal RSCC chart";
    }
    if($opts['chartCoot'])      $tasks['cootchart'] = "Create chart for use in Coot";
    if($opts['doKinemage'])     $tasks['multikin'] = "Create multi-criterion kinemage";
    //if($opts['doLowRes'])       $tasks['lowResKin'] = "Create low-resolution multi-criterion kinemage";

    //$doRem40 = $opts['chartClashlist'] || $opts['chartRama'] || $opts['chartRota'];
    //if($doRem40)                $tasks['remark40'] = "Create REMARK  40 record for the PDB file";
    //}}} Set up file/directory vars and the task list

    //Curate presentation of validation feedback
    $curation = array(
      'showAllOmegaStats' => $opts['chartOmegaForceStats']);

    //{{{ Run geometry programs and offer kins to user

    $summaries = array();
    //This will be used to hold summary counts and statistics as summaries
    //  become more complex than MolProbity can calculate on the fly.
    //$summaries should be loaded with data in loadValidationSummary() functions
    //  should be keyed with the same short strings used elsewhere 'rama', 'rota', etc

    //{{{ Bonds and Angles
    if($opts['chartGeom'])
    {
        setProgress($tasks, 'geomValidation'); // updates the progress display if running as a background job
        $geomfile = "$rawDir/$model[prefix]geomvalidation.data";
        $mpgeo_return_code = runValidationReport($infile, $geomfile, $model['stats']['use_cdl']);
        mpgeo_error_catch($mpgeo_return_code);
        //$protfile = "$rawDir/$model[prefix]protvalidation.data";
        //runValidationReport($infile, $protfile, "protein");
        //$rnafile = "$rawDir/$model[prefix]rnavalidation.data";
        //runValidationReport($infile, $rnafile, "rna");
        //$validate_bond  = loadValidationBondReport($protfile,"protein");
        //if (is_array($validate_bond))
        $validate_bond  = array_merge(loadValidationBondReport($geomfile,"protein"), loadValidationBondReport($geomfile, "rna"));
        if (count($validate_bond) == 0) $validate_bond = null;
        $validate_angle = array_merge(loadValidationAngleReport($geomfile, "protein"), loadValidationAngleReport($geomfile, "rna"));
        if (count($validate_angle) == 0) $validate_angle = null;
        $chiral_outfile = "$chartDir/$model[prefix]chirals.txt";
        makeChiralOutlierTable($geomfile,$chiral_outfile);
        $chiral_summary = load_chiral_summary($chiral_outfile);

        
    }//}}}

    //{{{ Ramachandran
    if($opts['chartRama'])
    {
        $startTime = time();
        setProgress($tasks, 'rama'); // updates the progress display if running as a background job
        $outfile = "$rawDir/$model[prefix]rama.data";
        runRamachandran($infile, $outfile);
        $rama = loadRamachandran($outfile);
        $summaries['rama'] = loadRamachandranSummary($outfile);

        $ramaZfile = "$chartDir/$model[prefix]ramaZ.txt";
        runRamaZ($infile, $ramaZfile);
        $summaries['ramaZ'] = loadRamaZ($ramaZfile);

        makeRamachandranKin($infile, "$kinDir/$model[prefix]rama.kin");
        $tasks['rama'] .= " - preview <a href='viewking.php?$_SESSION[sessTag]&url=$kinURL/$model[prefix]rama.kin' target='_blank'>kinemage</a>";
        setProgress($tasks, 'rama'); // so the preview link is visible
        makeRamachandranPDF($infile, "$chartDir/$model[prefix]rama.pdf");
        $tasks['rama'] .= " | <a href='$chartURL/$model[prefix]rama.pdf' target='_blank'>PDF</a>\n";
        setProgress($tasks, 'rama'); // so the preview link is visible
        echo "Ramachandran ran for ".(time() - $startTime)." seconds\n";
    }//}}}

    //{{{ Rotamers
    if($opts['chartRota'])
    {
        $startTime = time();
        setProgress($tasks, 'rota'); // updates the progress display if running as a background job
        $outfile = "$rawDir/$model[prefix]rota.data";
        runRotamer($infile, $outfile);
        $rota = loadRotamer($outfile);
        echo "Rotamers ran for ".(time() - $startTime)." seconds\n";
    }//}}}

    //{{{ C-beta deviations
    if($opts['chartCBdev'])
    {
        $startTime = time();
        setProgress($tasks, 'cbeta'); // updates the progress display if running as a background job
        $outfile = "$rawDir/$model[prefix]cbdev.data";
        runCbetaDev($infile, $outfile);
        $cbdev = loadCbetaDev($outfile);

        makeCbetaDevPlot($infile, "$kinDir/$model[prefix]cbetadev.kin");
        $tasks['cbeta'] .= " - <a href='viewking.php?$_SESSION[sessTag]&url=$kinURL/$model[prefix]cbetadev.kin' target='_blank'>preview</a>";
        setProgress($tasks, 'cbeta'); // so the preview link is visible
        echo "C-beta ran for ".(time() - $startTime)." seconds\n";
    }//}}}

    //{{{ Omega peptides
    if($opts['chartOmega'])
    {
        $startTime = time();
        setProgress($tasks, 'omega');
        $outfile = "$rawDir/$model[prefix]omega.data";
        //$outfile = "$rawDir/$model[prefix]omega-clashlist.txt";
        runOmegalyze($infile, $outfile);
        $omega = loadOmegalyze($outfile);
        $summaries['omega'] = loadOmegaSummary($omega);
        echo "Omegalyze ran for ".(time() - $startTime)." seconds\n";
    }//}}}

    //{{{ CaBLAM
    if($opts['chartCablamLow'])
    {
        setProgress($tasks, 'cablam');
        $outfile = "$rawDir/$model[prefix]cablam.data";
        runCablam($infile, $outfile);
        $cablam = loadCablam($outfile);
        $summaries['cablam'] = loadCablamSummary($outfile);
    }//}}}

    //{{{ Run nucleic acid geometry programs and offer kins to user
    //{{{ Base-phosphate perpendiculars
    if($opts['chartBaseP'])
    {
        setProgress($tasks, 'base-phos'); // updates the progress display if running as a background job
        $outfile = "$rawDir/$model[prefix]pperp.data";
        runBasePhosPerp($infile, $outfile);
        $pperp = loadBasePhosPerp($outfile);
    }//}}}

    //{{{ Suitename
    if($opts['chartSuite'])
    {
        setProgress($tasks, 'suitename'); // updates the progress display if running as a background job
        //$midfile = "$chartDir/$model[prefix]suitedata.txt"; //obsoleted by direct reading of structure files by suitename
        $outfile = "$chartDir/$model[prefix]suitename.txt";
        //$mpgeo_return_code = runSuitenameReport($infile, $midfile, $outfile);
        runSuitenameReport($infile, $outfile);
        //mpgeo_error_catch($mpgeo_return_code);
        $suites = loadSuitenameReport($outfile);
        $tasks['suitename'] .= " - <a href='viewtext.php?$_SESSION[sessTag]&file=$outfile&mode=plain' target='_blank'>preview</a>\n";
        setProgress($tasks, 'suitename'); // so the preview link is visible

        $outfile = "$chartDir/$model[prefix]suitestring.txt";
        runSuitenameString($infile, $outfile);

        makeSuitenameKin($infile, "$kinDir/$model[prefix]suitename.kin");
    }//}}}
    //}}} Run nucleic acid geometry programs and offer kins to user

    //}}} Run programs and offer kins to user

    //{{{ Run all-atom contact programs and offer kins to user
    // Clashes
    if($opts['chartClashlist'])
    {
        $startTime = time();
        setProgress($tasks, 'clashlist'); // updates the progress display if running as a background job
        $outfile = "$chartDir/$model[prefix]clashlist.txt";
        #runClashlist($infile, $outfile, $reduce_blength);
        runClashscore($infile, $outfile, $reduce_blength);
        #$clash = loadClashlist($outfile);
        $clash = loadClashscore($outfile);
        //$clashPct = runClashStats($model['stats']['resolution'], $clash['scoreAll'], $clash['scoreBlt40']);
        $tasks['clashlist'] .= " - <a href='viewtext.php?$_SESSION[sessTag]&file=$outfile&mode=plain' target='_blank'>preview</a>\n";
        setProgress($tasks, 'clashlist'); // so the preview link is visible
        echo "chartClashlist ran for ".(time() - $startTime)." seconds\n";

        $outfile = "$chartDir/$model[prefix]undowser.html";
        runUnDowser($infile, $outfile);
        $undowser = loadUndowserSummary($outfile);
    }
    //}}} Run all-atom contact programs and offer kins to user

    //{{{ Run real-space correlation
    $model['raw_rscc_name'] = "$model[parent]_raw.rscc";
    $model['rscc_name']     = "$model[parent].rscc";
    $rscc_out = "$xrayDir/$model[parent].rscc";
    $rscc_prequel_out = "$xrayDir/$model[parent]_prequel.rscc";
    if($opts['chartHoriz'])
    {
        $startTime = time();
        setProgress($tasks, 'runRSCC');
        runRscc($infile, $mtz_file, $rscc_out, $rscc_prequel_out);
        echo "runRscc ran for ".(time() - $startTime)." seconds\n";
        echo $mtz_file;
        echo isset($mtz_file);
    }
    //}}}

    //{{{ Report on improvements (that could be) made by MolProbity
    $improveText = "";
    if($opts['chartImprove'] && ($clash || $rota))
    {
        $startTime = time();
        setProgress($tasks, 'improve'); // updates the progress display if running as a background job
        $altpdb = mpTempfile("tmp_altH_pdb_");
        $mainClashscore = ($clash ? $clash['scoreAll'] : 0);
        $mainRotaCount = ($rota ? count(findRotaOutliers($rota)) : 0);
        $improvementList = array();

        if($model['isBuilt']) // file has been through reduce -build or reduce -fix
        {
            $altInpath = $modelDir . '/'. $_SESSION['models'][ $model['parent'] ]['pdb'];
            reduceNoBuild($altInpath, $altpdb, $reduce_blength);
            // Rotamers
                $outfile = mpTempfile("tmp_rotamer_");
                runRotamer($altpdb, $outfile);
                $altrota = loadRotamer($outfile);
                $altRotaCount = count(findRotaOutliers($altrota));
                if($altRotaCount > $mainRotaCount)
                {
                    if ($altRotaCount - $mainRotaCount > 1)
                    {
                      $improvementList[] = "fixed ".($altRotaCount - $mainRotaCount)." bad rotamers";
                    }
                    else
                    {
                      $improvementList[] = "fixed ".($altRotaCount - $mainRotaCount)." bad rotamer";
                    }
                }
                unlink($outfile);
            // Clashes
                $outfile = mpTempfile("tmp_clashlist_");
                #runClashlist($altpdb, $outfile, $reduce_blength);
                runClashscore($altpdb, $outfile, $reduce_blength);
                #$altclash = loadClashlist($outfile);
                $altclash = loadClashscore($outfile);
                if(($altclash['scoreAll'] - $mainClashscore)>=0.005) //0.005 is the smallest change that will still be reported by the sprintf("%.2f") below
                    $improvementList[] = "improved your clashscore by ".sprintf("%.2f",($altclash['scoreAll'] - $mainClashscore))." points";
                unlink($outfile);
            if(count($improvementList) > 0)
            {
                $improveText .= "<div class='feature'>By adding H to this model and allowing Asn/Gln/His flips, you have already ";
                $improveText .= implode(" and ", $improvementList);
                $improveText .= ".  <br /><b>Make sure you download the modified PDB to take advantage of these improvements! <br />NOTE: Atom positions have changed, so refinement to idealize geometry is necessary.</b></div>\n";
            }
        }
        elseif($mainClashscore > 0 || $mainRotaCount > 0) // if file was run through reduce at all, flips were not allowed
        {
            if($model['parent']) $altInpath = $_SESSION['models'][ $model['parent'] ]['pdb'];
            else $altInpath = $model['pdb'];
            $altInpath = "$modelDir/$altInpath";
            reduceBuild($altInpath, $altpdb, $reduce_blength);
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
                #runClashlist($altpdb, $outfile, $reduce_blength);
                runClashscore($altpdb, $outfile, $reduce_blength);
                #$altclash = loadClashlist($outfile);
                $altclash = loadClashscore($outfile);
                if(($mainClashscore - $altclash['scoreAll'])>=0.005) //0.005 is the smallest change that will still be reported by the sprintf("%.2f") below
                    $improvementList[] = "improve your clashscore by ".sprintf("%.2f",($mainClashscore - $altclash['scoreAll']))." points";
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
        echo "chart Improve ran for ".(time() - $startTime)." seconds\n";
    }
    //}}} Report on improvements (that could be) made by by MolProbity

    //{{{ Build multi-criterion chart, kinemage, horizontal, chart
    if($opts['doCharts'])
    {
        $startTime = time();
        if($opts['chartMulti']) {
          setProgress($tasks, 'multichart'); // updates the progress display if running as a background job
        } else {
          setProgress($tasks, 'chartsummary');
        }
        $outfile = "$rawDir/$model[prefix]multi.table";
        $snapfile = "$chartDir/$model[prefix]multi.html";
        $resout = "$rawDir/$model[prefix]multi_res.table";
        writeMulticritChart($infile, $outfile, $snapfile, $resout, $clash, $rama, $rota, $cbdev, $pperp, $suites, $validate_bond, $validate_angle, $chiral_summary, $undowser, $cablam, $omega, $summaries, $curation, !$opts['chartNotJustOut'], $opts['chartMulti'], $opts['chartAltloc']);
        if($opts['chartMulti']) {
          $tasks['multichart'] .= " - <a href='viewtable.php?$_SESSION[sessTag]&file=$outfile' target='_blank'>preview</a>\n";
          setProgress($tasks, 'multichart'); // so the preview link is visible
        } else {
          $tasks['chartsummary'] .= " - <a href='viewtable.php?$_SESSION[sessTag]&file=$outfile' target='_blank'>preview</a>\n";
          setProgress($tasks, 'chartsummary'); // so the preview link is visible
        }
        if($opts['chartHoriz']) {
          setProgress($tasks, 'charthoriz');
          $horiz_table_file = "$rawDir/$model[prefix]horiz.table";
          writeHorizontalChart($resout, $rscc_out, $outfile, $horiz_table_file, $rscc_prequel_out);
        }
        if($opts['chartCoot']) {
          setProgress($tasks, 'cootchart');
          $outfile = "$chartDir/$model[prefix]multi-coot.scm";
          $outfile_py = "$chartDir/$model[prefix]multi-coot.py";
          #makeCootMulticritChart($infile, $outfile, $clash, $rama, $rota, $cbdev, $pperp);
          makeCootClusteredChart($infile, $outfile, $outfile_py, $clash, $rama, $rota, $cbdev, $pperp);
        }
        echo "do Charts ran for ".(time() - $startTime)." seconds\n";
    }
    if($opts['doKinemage'])
    {
        $startTime = time();
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
            'clashdots' =>  $opts['kinClashes'],
            'hbdots'    =>  $opts['kinHbonds'],
            'vdwdots'   =>  $opts['kinContacts']
        );
        $outfile = "$kinDir/$model[prefix]multi.kin";
        $viewRes = array();
        //echo "kinForceViews = ".$opts['kinForceViews']."\n";
        if($opts['kinForceViews']){
            //echo "Ran calcLocalBadness\n";
            $viewRes = array_keys(calcLocalBadness($infile, 10, $clash, $rama, $rota, $cbdev, $pperp));
        }
        makeMulticritKin2(array($infile), $outfile, $mcKinOpts, $model['stats']['use_cdl'],
        #    array_keys(findAllOutliers($clash, $rama, $rota, $cbdev, $pperp)));
            $viewRes);

        // EXPERIMENTAL: gzip compress large multikins
        if(filesize($outfile) > MP_KIN_GZIP_THRESHOLD)
        {
            destructiveGZipFile($outfile);
        }
        echo "do Kinemage ran for ".(time() - $startTime)." seconds\n";
    }
    //}}} Build multi-criterion chart, kinemage

    //{{{ Low-resolution-specific analyses
    //Low-res kinemage is being simplified and merged into main kinemage
    //if($opts['doLowRes'])
    //{
    //  if($opts['kinCablamLow'] || $opts['other'])
    //  {
    //      $startTime = time();
    //      setProgress($tasks, 'lowResKin');
    //      $lowResKinOpts = array(//first column is opts, second column sets true-false
    //          'ribbons'    =>  $opts['kinRibbons'],//pass pdb w/HELIX+SHEET for this
    //          'rama'       =>  $opts['kinRama'],
    //          'geom'       =>  $opts['kinGeom'],
    //          'cbdev'      =>  $opts['kinCBdev'],
    //          'omega'      =>  $opts['kinOmega'],
    //          'cablam'     =>  $opts['kinCablamLow'],
    //          'clashdots'  =>  $opts['kinClashes']
    //      );
    //      $outfile = "$kinDir/$model[prefix]low_multi.kin";
    //      $cablamSecStrucFile = "$modelDir/$model[prefix]cablam_sec_struc_records.pdb";
    //      //$viewRes = array(); //Used with opts[kinForceViews], not necessary argument for makeMulticritKin2
    //      makeMulticritKinLowRes(array($infile), $outfile, $cablamSecStrucFile, $lowResKinOpts);
    //      if(filesize($outfile) > MP_KIN_GZIP_THRESHOLD)  destructiveGZipFile($outfile);
    //      echo "do low-res Kinemage ran for ".(time() - $startTime)." seconds\n";
    //  }
    //}
    //}}}

    //{{{ Create REMARK  40 and insert into PDB file
    //if(is_array($clash) || is_array($rama) || is_array($rota))
    //{
    //    setProgress($tasks, 'remark40'); // updates the progress display if running as a background job
    //    $remark40 = makeRemark40($clash, $rama, $rota);
    //    replacePdbRemark($infile, $remark40,  40);
    //}
    //}}} Create REMARK  40 and insert into PDB file

    //{{{ Create lab notebook entry
    $entry = "";
    if(is_array($clash) || is_array($rama) || is_array($rota) || is_array($cbdev) || is_array($pperp) || is_array($suites))
    {
        if($opts['doEnsemble']) {
          $entry .= "<h3>Summary statistics for $model[pdb]</h3>\n";
        } else {
          $entry .= "<h3>Summary statistics</h3>\n";
        }
        $entry .= makeSummaryStatsTable($model['stats']['resolution'], $clash, $rama, $rota, $cbdev, $pperp, $suites, $validate_bond, $validate_angle, $chiral_summary, $undowser, $cablam, $omega, $summaries, $curation);
    }
    $entry .= $improveText;
    if($opts['doKinemage'] || $opts['doCharts'])
    {
        $entry .= "<h3>Multi-criterion visualizations</h3>\n";
        $entry .= "<div class='indent'>\n";
        $entry .= "<table width='100%' border='0'><tr valign='top'>\n";
        if($opts['doKinemage'])
            $entry .= "<td>".linkAnyFile("$model[prefix]multi.kin", "Kinemage", "img/multikin.jpg")."</td>\n";
        if($opts['doCharts'])
        {
            $entry .= "<td>".linkAnyFile("$model[prefix]multi.table", "Chart", "img/multichart.jpg")."</td>\n";
            $entry .= "<td>".linkAnyFile("$model[prefix]undowser.html", "Chart", "img/UnDowser_icon_120h.jpg")."</td>\n";
            if($opts['chartCoot']) {
              $entry .= "<td>".linkAnyFile("$model[prefix]multi-coot.scm", "To-do list for Coot", "img/multichart-coot.jpg")."<br><small><i>Open this in Coot 0.1.2 or later using Calculate | Run Script...</i></small></td>\n";
              #$entry .= "<td>".linkAnyFile("$model[prefix]multi-coot.py", "To-do list for Coot Python", "img/multichart-coot.jpg")."<br><small><i>Open this in Coot 0.1.2 or later using Calculate | Run Script...</i></small></td>\n";
            }
            if($opts['chartHoriz']) {
              $entry .= "<td>".linkAnyFile("$model[prefix]horiz.table", "Horizontal Chart", "img/multichart_horiz.jpg")."</td>\n";
            }
        }
        //if($opts['doLowRes']) {
        //    if($opts['kinCablamLow'] || $opts['other']) {
        //        $entry .= "<td>".linkAnyFile("$model[prefix]low_multi.kin", "LowRes MultiKin", "img/low_multikin.jpg")."</td>\n";
        //    }
        //}
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
            $entry .= "<li>".linkAnyFile("$model[prefix]ramaZ.txt", "Ramachandran distribution Z-score analysis")."</li>\n";
        }
        if($opts['chartGeom'])
            $entry .= "<li>".linkAnyFile("$model[prefix]chirals.txt", "Chiral volume report")."</li>\n";
        if($opts['chartCBdev'])
            $entry .= "<li>".linkAnyFile("$model[prefix]cbetadev.kin", "C&beta; deviation scatter plot")."</li>\n";
        if($opts['chartSuite'])
        {
            $entry .= "<li>".linkAnyFile("$model[prefix]suitename.txt", "RNA backbone report")."</li>\n";
            $entry .= "<li>".linkAnyFile("$model[prefix]suitestring.txt", "RNA backbone conformation \"sequence\"")."</li>\n";
            $entry .= "<li>".linkAnyFile("$model[prefix]suitename.kin", "RNA backbone multi-D plot of conformations")."</li>\n";
        }
        $entry .= "</ul>\n";
    }

    if($remark40)
    {
        $entry .= "<h3>REMARK  40</h3>";
        $url = "$modelURL/$model[pdb]";
        $entry .= "You can <a href='$url'>download your PDB file with REMARK  40</a> inserted, or the same <a href='download_trimmed.php?$_SESSION[sessTag]&file=$infile'> without hydrogens</a>.\n";
        $entry .= "<p><pre>$remark40</pre></p>";
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
    if(!$_SESSION['useSEGID'])
    {
      $opt = "-pperptoline -pperpdump";
    }
    else
    {
      $opt = "-pperptoline -pperpdump -segid";
    }
    exec("prekin $opt $infile > $outfile");
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
*   Pdist5          distance from the base to the 5' phosphate (?)
*   Pdist3          distance from the base to the 3' phosphate (?)
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
            $perpdist = $line[6] + 0;
            if($perpdist < 2.9) //2.9A is dist cutoff for C2' vs C3' endo pucker
              $probpucker = "C2'-endo";
            else
              $probpucker = "C3'-endo";
            //echo strtoupper(substr($line[3],0,-1))."\n";
            $entry = array(
                'resType'   => strtoupper(substr($line[2],1,-1)),
                //'chainID'   => strtoupper(substr($line[3],1,-1)),
                'chainID'   => substr($line[3],1,-1),
                'resNum'    => trim(substr($line[4], 0, -2)) + 0,
                'insCode'   => strtoupper(substr($line[4], -2, 1)),
                'altloc'    => ' ', //limitation - doesn't currently handle altloc
                'Pdist5'    => $line[5] + 0,
                'Pdist3'    => $line[6] + 0,
                '3Pdist'    => $line[6] + 0, //Some functions use this name instead of 'Pdist3'
                'delta'     => $line[7] + 0,
                'deltaOut'  => $deltaOut,
                'epsilon'   => $line[9] + 0,
                'epsilonOut'=> $epsilonOut,
                'outlier'   => ($deltaOut || $epsilonOut),
                'probpucker'=> $probpucker
            );
            $entry['resName']   = $entry['chainID']
                                . str_pad($entry['resNum'], 4, ' ', STR_PAD_LEFT)
                                . $entry['insCode']
                                . $entry['altloc']
                                . str_pad($entry['resType'], 3, ' ', STR_PAD_RIGHT);
            $ret[$entry['resName']] = $entry;
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
    if(!$_SESSION['useSEGID'])
    {
      //exec("prekin -cbdevdump $infile > $outfile");
      exec("phenix.cbetadev $infile > $outfile");
    }
    else #use segid in place of chainid
    {
      //exec("prekin -cbdevdump -segid $infile > $outfile");
      exec("phenix.cbetadev $infile > $outfile");
    }
}
#}}}########################################################################

#{{{ loadCbetaDev - loads Prekin cbdevdump output into an array
############################################################################
/**
* Returns an array of entries, one per residue. Their keys:
*   altConf         alternate conformer flag, or ' ' for none
*   resName         a formatted name for the residue: 'ccnnnnittt'
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
            if ($line[0]==''){
              continue;
            }
            elseif (preg_match("/^#/",$line[0])){
              continue;
            }
            elseif (preg_match("/^SUMMARY/",$line[0])){
              continue;
            }
            elseif (preg_match("/^filename/",$line[0])){
              continue;
            }
            $entry = array(
                'altConf'   => strtoupper($line[1]),
                'resType'   => strtoupper($line[2]),
                //'chainID'   => strtoupper($line[3]),
                'chainID'   => $line[3],
                'resNum'    => trim(substr($line[4], 0, -1)) + 0,
                'insCode'   => substr($line[4], -1),
                'dev'       => $line[5] + 0,
                'dihedral'  => $line[6] + 0,
                'occ'       => $line[7] + 0
            );
            if($entry['chainID'] == ""){
                $entry['chainID'] = " ";
            }
            if(strlen($entry['chainID'])==1){
                $entry['chainID'] = ' '.$entry['chainID'];
            }
            $entry['resName']   = $entry['chainID']
                                . str_pad($entry['resNum'], 4, ' ', STR_PAD_LEFT)
                                . $entry['insCode'] //not sure where this goes?
                                . $entry['altConf']
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
function runClashlist($infile, $outfile, $blength="ecloud")
{
    $bcutval = 40;
    $ocutval = 10;
    exec("clashlist $infile $bcutval $ocutval $blength > $outfile");
}
#}}}########################################################################

#{{{ runClashscore - generates clash data with phenix.clashscore
############################################################################
function runClashscore($infile, $outfile, $blength="ecloud", $clash_cutoff=-0.4)
{
    #clash_cutoff flag not currently in use in phenix.clashscore, commandlines preserved for posterity
    #$bcutval = 40;
    #$ocutval = 10;
    #exec("clashlist $infile $bcutval $ocutval $blength > $outfile");
    if($blength == "ecloud")
    {
      #exec("phenix.clashscore b_factor_cutoff=40 keep_hydrogens=True clash_cutoff=$clash_cutoff $infile > $outfile");
      exec("phenix.clashscore b_factor_cutoff=40 keep_hydrogens=True $infile > $outfile");
    }
    elseif($blength == "nuclear")
    {
      #exec("phenix.clashscore b_factor_cutoff=40 keep_hydrogens=True clash_cutoff=$clash_cutoff nuclear=True $infile > $outfile");
      exec("phenix.clashscore b_factor_cutoff=40 keep_hydrogens=True nuclear=True $infile > $outfile");
    }
}
#}}}########################################################################

#{{{ runRscc - generates rscc data
############################################################################
function runRscc($pdb_infile, $mtz_infile, $outfile, $rawoutfile)
{
    $cmd = MP_BASE_DIR."/bin/runRSCC.py";
    exec("python $cmd pdb_in=$pdb_infile mtz_in=$mtz_infile prequel=$rawoutfile > $outfile");
    ///exec("phenix.real_space_correlation $pdb_infile $mtz_infile detail=atom > $outfile");
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
            if (!$_SESSION['useSEGID'])
            {
              $res1 = substr($line[2], 0, 10);
              $atm1 = substr($line[2], 11, 5);
              $res2 = substr($line[3], 0, 10);
              $atm2 = substr($line[3], 11, 5);
            }
            else
            {
              $res1 = substr($line[2], 0, 12);
              $atm1 = substr($line[2], 13, 5);
              $res2 = substr($line[3], 0, 12);
              $atm2 = substr($line[3], 13, 5);
            }
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

#{{{ loadClashscore - loads Clashscore output into an array
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
function loadClashscore($datafile)
{
  $data = file($datafile);
  $sum = array_values(array_slice($data, -2)); // last 2 lines with new indexes
  $scoreAll = explode(" ", $sum[0]);
  $scoreBlt40 = explode(" ", $sum[1]);
  $ret['scoreAll']    = round(($scoreAll[2]+0), 2);
  $ret['scoreBlt40']  = round(($scoreBlt40[7]+0), 2);

  // Parse data about individual clashes
  $clashes = array(); // in case there are no clashes
  $clashes_with = array();
  $lines_all = array();
  foreach($data as $datum)
  {
    $lines_all[$datum]=$datum;
    $line = explode(':', $datum);
    if (trim($line[0])==''){
      continue;
    }
    elseif (preg_match("/^#/",$line[0])){
      continue;
    }
    elseif (preg_match("/^Using /",$line[0])){
      continue;
    }
    elseif (preg_match("/^Adding /",$line[0])){
      continue;
    }
    elseif (preg_match("/^No H/",$line[0])){
      continue;
    }
    elseif (preg_match("/^Bad Clashes/",$line[0])){
      continue;
    }
    elseif (preg_match("/^clashscore/",$line[0])){
      continue;
    }
    if (!$_SESSION['useSEGID'])
    {
      $res1 = substr($line[0], 0, 11);
      $atm1 = substr($line[0], 12, 5);
      $res2 = substr($line[0], 17, 11);
      $atm2 = substr($line[0], 28, 5);
    }
    else
    {
      $res1 = substr($line[0], 0, 13);
      $atm1 = substr($line[0], 14, 5);
      $res2 = substr($line[0], 19, 13);
      $atm2 = substr($line[0], 32, 5);
    }
    $dist = abs(trim($line[1])+0);
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
  $ret['lines_all'] = $lines_all;
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

#{{{ runUnDowser
############################################################################
function runUnDowser($infile, $outfile)
{
  exec("python ".MP_BASE_DIR."/cmdline/undowser.py $infile > $outfile");
}
#}}}############################################################################

#{{{ runRotamer - generates rotamer analysis data
############################################################################
function runRotamer($infile, $outfile)
{
    // Very large files (1htq) need extra memory
    //exec("java -Xmx256m -cp ".MP_BASE_DIR."/lib/hless.jar hless.Rotamer -raw $infile > $outfile");
    //java-based
    //exec("java -Xmx512m -cp ".MP_BASE_DIR."/lib/chiropraxis.jar chiropraxis.rotarama.Rotalyze $infile > $outfile");
    //cctbx-based
    //exec("phenix.rotalyze data_version=8000 $infile > $outfile");
    exec("phenix.rotalyze $infile > $outfile"); #data_version flag not currently in use
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
        // echo "In loop\n";
        $line = explode(':', rtrim($line));
        if ($line[0]==''){
          continue;
        }
        elseif (preg_match("/^#/",$line[0])){
          continue;
        }
        elseif (preg_match("/^SUMMARY/",$line[0])){
          continue;
        }
        elseif (preg_match("/^residue/",$line[0])){
          continue;
        }
        $cnit = $line[0];
        /*if(strlen($cnit)==10)
        {
          $cnit = ' '.$cnit;
        }
        $cnit = substr($cnit,0,6).substr($cnit,7,4);*/
        $decomp = decomposeResName($cnit);
        $ret[$cnit] = array(
            'resName'   => $cnit,
            'resType'   => $decomp['resType'],
            'chainID'   => $decomp['chainID'],
            'resNum'    => $decomp['resNum'],
            'insCode'   => $decomp['insCode'],
            'altID'     => $decomp['altID'],
            'occupancy' => $line[1],
            'scorePct'  => $line[2] + 0,
            'chi1'      => $line[3],
            'chi2'      => $line[4],
            'chi3'      => $line[5],
            'chi4'      => $line[6],
            'eval'      => $line[7],
            'rotamer'   => $line[8]
        );
        // This converts numbers to numbers and leaves "" as it is.
        if($ret[$cnit]['chi1'] !== '') $ret[$cnit]['chi1'] += 0;
        if($ret[$cnit]['chi2'] !== '') $ret[$cnit]['chi2'] += 0;
        if($ret[$cnit]['chi3'] !== '') $ret[$cnit]['chi3'] += 0;
        if($ret[$cnit]['chi4'] !== '') $ret[$cnit]['chi4'] += 0;
        // echo "added rota entry\n";
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
        #if($res['scorePct'] <= 1.0)
         if($res['rotamer'] == 'OUTLIER')
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
    //exec("java -Xmx512m -cp ".MP_BASE_DIR."/lib/hless.jar hless.Ramachandran -nokin -raw $infile > $outfile");
    //exec("java -Xmx512m -cp ".MP_BASE_DIR."/lib/chiropraxis.jar chiropraxis.rotarama.Ramalyze -raw $infile > $outfile");
    exec("phenix.ramalyze $infile > $outfile");
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
    //$data = file($datafile);
    $ret = array();
    foreach($data as $line)
    {
        $line = explode(':', rtrim($line));
        if ($line[0]==''){
          continue;
        }
        elseif (preg_match("/^#/",$line[0])){
          continue;
        }
        elseif (preg_match("/^SUMMARY/",$line[0])){
          continue;
        }
        elseif (preg_match("/^residue/",$line[0])){
          continue;
        }
        $cnit = $line[0];
        /*if(strlen($cnit)==10)
        {
          $cnit = ' '.$cnit;
        }
        $cnit = substr($cnit,0,6).substr($cnit,7,4);
        echo $cnit."\n";*/
        $decomp = decomposeResName($cnit);
        $ret[$cnit] = array(
            'resName'   => $cnit,
            'resType'   => $decomp['resType'],
            'chainID'   => $decomp['chainID'],
            'resNum'    => $decomp['resNum'],
            'insCode'   => $decomp['insCode'],
            'altID'     => $decomp['altID'],
            'scorePct'  => $line[1] + 0,
            'phi'       => $line[2] + 0,
            'psi'       => $line[3] + 0,
            'eval'      => $line[4],
            'type'      => $line[5]
        );
        if($ret[$cnit]['type'] == 'Isoleucine or valine')
            $ret[$cnit]['type'] = 'Ile or Val';
        elseif ($ret[$cnit]['type'] == 'Trans-proline')
          $ret[$cnit]['type'] = 'Trans-Pro';
        elseif ($ret[$cnit]['type'] == 'Cis-proline')
          $ret[$cnit]['type'] = 'Cis-Pro';
        elseif ($ret[$cnit]['type'] == 'Pre-proline')
          $ret[$cnit]['type'] = 'Pre-Pro';
    }
    return $ret;
}
#}}}########################################################################

#{{{ loadRamachandranSummary - extract SUMMARY lines from ramalyze output
function loadRamachandranSummary($datafile)
{
  $data = array_slice(file($datafile), -3); //look at last 3 lines, where summaries are
  foreach($data as $line)
  {
    if (preg_match("/^SUMMARY/",$line)){
            //echo "match found\n";
          //SUMMARY: 504 Favored, 12 Allowed, 2 Outlier out of 518 residues (altloc A where applicable)
          if (preg_match("/altloc/",$line)){
            $linebits = explode(' ',$line);
            $ret = array(
              'favored' => $linebits[1],
              'allowed' => $linebits[3],
              'outlier' => $linebits[5],
              'residues'=> $linebits[9]);
          }
          else{
            continue;
          }
    }
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

#{{{ runRamaZ - generate analysis of Ramachandran distribution
############################################################################
/**
* Creates a text file that will be available for direct inspection
* Text file will also be parsed by loadRamaZ() to feed into summary table
*/
function runRamaZ($infile, $outfile)
{
    #can also run as phenix.rama_z
    exec("mmtbx.rama_z $infile > $outfile");
}
############################################################################
#}}}

#{{{ loadRamaZ - load Ramachandran distribution for summary table
############################################################################
/**
* Creates a text file that will be available for direct inspection
* Text file will also be parsed by loadRamaZ() to feed into summary table
*/
function loadRamaZ($datafile)
{
#END OF FILE looks like this:
#Rama-Z (Ramachandran plot Z-score):
#Interpretation: bad |Rama-Z| > 3; suspicious 2 < |Rama-Z| < 3; good |Rama-Z| < 2.
#Scores for whole/helix/sheet/loop are scaled independently;
#therefore, the values are not related in a simple manner.
#  whole: -8.32 (0.16), residues: 546
#  helix: -5.23 (0.11), residues: 223
#  sheet: -3.80 (0.64), residues: 36
#  loop : -6.27 (0.18), residues: 287
#
#===============================================================================
#Job complete
#usr+sys time: 2.12 seconds
#wall clock time: 2.12 seconds
  $data = array_slice(file($datafile), -13); //look at last 13 lines, where summaries are
  foreach($data as $line)
  {
    if (preg_match("/^  whole/",$line))
    {
      //echo "match found\n";
      //  whole: -8.32 (0.16), residues: 546
      //  whole:  0.25 (0.89), residues: 74
      //may have different # of spaces, makes explode behave differently
      $linebits = explode(':',$line);
      $smallerbits = explode('(',$linebits[1]);
      #$z = $linebits[1];
      #$stddev = $linebits[2][1:-1];
    
      $ret = array(
        'z' => trim($smallerbits[0]),
        'stddev' => explode(')',$smallerbits[1])[0],
        'residues'=> trim($linebits[2]));
    }
    else continue;
  }
  return $ret;
}
############################################################################
#}}}

#{{{ runOmegalyze - generates rotamer analysis data
############################################################################
function runOmegalyze($infile, $outfile)
{
    exec("phenix.omegalyze nontrans_only=False $infile > $outfile");
}
#}}}########################################################################

#{{{ loadOmegalyze - loads Ramachandran output into an array
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
*   type            "General" or "Pro"
*   omega           the omega (peptide) dihedral
*   conf            "Trans", "Cis", or "Twisted"
*/
#' C 439  GLY to  C 440  ARG: non-Pro : -15.03:Cis     :107.4 '
#' C 534  GLY to  C 535  ASP: non-Pro : 139.87:Twisted :173.67'
#' B 277  PHE to  B 278  PRO: Pro     : -21.73:Cis     :126.98'
function loadOmegalyze($datafile)
{
    $data = array_slice(file($datafile), 1); // drop first line
    //$data = file($datafile);
    $ret = array();
    foreach($data as $line)
    {
        $line = explode(':', rtrim($line));
        if ($line[0]==''){
          continue;
        }
        elseif (preg_match("/^#/",$line[0])){
          continue;
        }
        elseif (preg_match("/^SUMMARY/",$line[0])){
          continue;
        }
        elseif (preg_match("/^residue/",$line[0])){
          continue;
        }
        #$cnit = substr($line[0], 0, 11);
        $cnit = substr($line[0], 15, 26);
        /*if(strlen($cnit)==10)
        {
          $cnit = ' '.$cnit;
        }
        $cnit = substr($cnit,0,6).substr($cnit,7,4);
        echo $cnit."\n";*/
        $decomp = decomposeResName($cnit);
        $ret[$cnit] = array(
            'resName'   => $cnit,
            'resType'   => $decomp['resType'],
            'chainID'   => $decomp['chainID'],
            'resNum'    => $decomp['resNum'],
            'insCode'   => $decomp['insCode'],
            'altID'     => $decomp['altID'],
            'type'      => trim($line[1]),
            'omega'     => ltrim($line[2]) + 0,
            'conf'      => rtrim($line[3])
        );
    }
    return $ret;
}
#}}}########################################################################

#{{{ findOmegaOutliers - evaluates residues for bad score
############################################################################
/**
* Returns an array of 9-char residue names for residues that
* fall outside the allowed boundaries for this criteria.
* Inputs are from appropriate loadXXX() function above.
* Cis and twisted peptides are not strictly outliers, but the naming convention
* for these functions is thus preserved.
*/
function findOmegaOutliers($omega)
{
    $worst = array();
    if(is_array($omega)) foreach($omega as $res)
    {
        //The values set here are used for sorting the MP chart
        //CisPro should be grouped as non-outliers
        //All others should be grouped as outliers
        if($res['conf'] == 'Cis')
          if($res['type'] == 'Pro')
            $worst[$res['resName']] = 2;
          else
            $worst[$res['resName']] = 1;
        elseif($res['conf'] == 'Twisted')
          $worst[$res['resName']] = 2;
    }
    ksort($worst); // Put the residues into a sensible order
    return $worst;
}
#}}}########################################################################

#{{{ loadOmegaSummary - percentage calculations for omegalyze stats
############################################################################
/**
*Accepts the $omega array from loadOmegalyze
*Returns array of omegalyze statistics
*replace this with SUMMARY lines parsing if/when omegalyze becomes alt-sensitive
**/
function loadOmegaSummary($omega)
{
    $totalres = 0;
    $prototal = 0;
    $nonprototal = 0;
    $cisprocount = 0;
    $cisnonprocount = 0;
    $twistcount = 0;
    foreach($omega as $cnit => $item){
        //$totalres += 1;
        if($item['type'] == 'Pro'){
            $prototal += 1;
            if($item['conf'] == 'Cis') $cisprocount += 1;
            elseif($item['conf'] == 'Twisted') $twistcount += 1;
        }
        elseif($item['type'] == 'non-Pro'){
            $nonprototal += 1;
            if($item['conf'] == 'Cis') $cisnonprocount += 1;
            elseif($item['conf'] == 'Twisted') $twistcount += 1;
        }
    }
    $totalres = $prototal + $nonprototal;
    if($prototal==0) $cispropct = 0;
    else $cispropct = sprintf("%.2f", 100.0 * $cisprocount / $prototal);
    if($nonprototal==0) $cisnonpropct = 0;
    else $cisnonpropct = sprintf("%.2f", 100.0 * $cisnonprocount / $nonprototal);
    if($totalres==0) $twistpct = 0;
    else $twistpct = sprintf("%.2f", 100.0 * $twistcount / $totalres);

    $ret = array(
              'totalres' => $totalres,
              'prototal' => $prototal,
              'nonprototal' => $nonprototal,
              'cisprocount' => $cisprocount,
              'cisnonprocount'=> $cisnonprocount,
              'twistcount'=> $twistcount,
              'cispropct' => $cispropct,
              'cisnonpropct' => $cisnonpropct,
              'twistpct' => $twistpct);
    return $ret;
}
#}}}########################################################################

#{{{ runSuitenameReport - finds conformer and suiteness for every RNA suite
############################################################################
function runSuitenameReport($infile, $outfile)
{
    //exec("java -Xmx512m -cp ".MP_BASE_DIR."/lib/dangle.jar dangle.Dangle rnabb $infile | suitename -report > $outfile");
    //formerly a single exec with a pipe, this has been broken into two execs to facilitate mp_geo error catching
    //exec("mmtbx.mp_geo rna_backbone=True pdb=$infile > $midfile",$arg_list_filler,$mpgeo_return_code);
    //if($mp_geo_return_code != 0) return $mpgeo_return_code; //skip suitename step if bad input
    //exec("phenix.suitename -report -pointIDfields 7 -altIDfield 6 < $midfile > $outfile");
    //Now a straight call to suitename, which can read structure files directly
    exec("molprobity.suitename -report $infile > $outfile");
    //return $mpgeo_return_code;
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
*   triage          contains details about the reason for triage  rmi 070827
*   isOutlier       true if conformer = !!
*/
function loadSuitenameReport($datafile)
{
# OLD VERSION - did not account for alternate conform
# 404d.pdb:1:A:   1: :  G inc  __ 0.000
# 404d.pdb:1:A:   2: :  A 33 p 1a 0.544
# 404d.pdb:1:A:   3: :  A trig !! 0.000 alpha
# 404d.pdb:1:A:   4: :  G 33 t 1c 0.135
# 404d.pdb:1:A:   5: :  A 33 p 1a 0.579
# 404d.pdb:1:A:   6: :  G 33 t 1c 0.180
# 404d.pdb:1:A:   7: :  A 33 p 1a 0.458
# 404d.pdb:1:A:   8: :  A 33 t !! 0.000 7D dist 1c
# 404d.pdb:1:A:   9: :  G 33 p 1a 0.294
# 404d.pdb:1:A:  10: :  C 33 p 1a 0.739
#
# NEW VERSION - uses mmtbx.mp_geo in place of dangle - JJH 140108
# adds additional column for altloc (indicated with L)
#              L
# :1: A:   1: : :  G inc  __ 0.000
# :1: A:   2: : :  A 33 p 1a 0.544
# :1: A:   3: : :  A trig !! 0.000 alpha
# :1: A:   4: : :  G 33 t 1c 0.135
# :1: A:   5: : :  A 33 p 1a 0.579
# :1: A:   6: : :  G 33 t 1c 0.180
# :1: A:   7: : :  A 33 p 1a 0.458
# :1: A:   8: : :  A 33 t !! 0.000 7D dist 1c
# :1: A:   9: : :  G 33 p 1a 0.294
# :1: A:  10: : :  C 33 p 1a 0.739

    $data = file($datafile);
    //$ret = array(); // needs to return null if no data!
    foreach($data as $line)
    {
        if(startsWith($line, " all general case widths")) break;
        $line = explode(':', rtrim($line));
        if (count($line)==6) { // missing colon due to name length issue in suitename
          //$linestart = array(substr($line[0], 32));  // this code doesn't appear to correctly fix the second element of the array
          //$line = array_merge($linestart, $line);    // this code doesn't appear to correctly fix the second element of the array
          $linestart = array(substr($line[0], 0, 32), substr($line[0], 32));
          $line = array_merge($linestart, array_slice($line, 1));
          //echo $line[0].":".$line[1].":".$line[2].":".$line[3]."\n";
        }
        if (count($line) > 1) {
          $altloc = $line[5]; //added by JJH 140108
          $cnit = $line[2].$line[3].$line[4].$altloc.substr($line[6],0,3);
          //echo $cnit."\n";
          //$decomp = decomposeResName($cnit);
          $conf = substr($line[6],9,2);
          $ret[$cnit] = array(
            'resName'   => $cnit,
            //'resType'   => $decomp['resType'],
            //'chainID'   => $decomp['chainID'],
            //'resNum'    => $decomp['resNum'],
            //'insCode'   => $decomp['insCode'],
            'conformer' => $conf,
            'suiteness' => substr($line[6],12,5) + 0,
            'bin'       => substr($line[6],4,4),
            'triage'    => substr($line[6],18),
            'isOutlier' => ($conf == '!!')
          );
        }
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
    //exec("java -Xmx512m -cp ".MP_BASE_DIR."/lib/dangle.jar dangle.Dangle rnabb $infile | suitename -string -oneline | fold -w 60 > $outfile");
    //exec("mmtbx.mp_geo rna_backbone=True pdb=$infile | phenix.suitename -string -oneline -pointIDfields 7 -altIDfield 6 | fold -w 60 > $outfile");
    exec("molprobity.suitename -string -oneline $infile > $outfile");

}
#}}}########################################################################

#{{{ runCablam - generates CaBLAM analysis data
############################################################################
function runCablam($infile, $outfile)
{
    exec("phenix.cablam output_type=text $infile > $outfile");
}
#}}}########################################################################

#{{{ loadCablam - loads CaBLAM output into an array
############################################################################
/**
* Returns an array of entries, one per residue. Their keys:
*   resName         a formatted name for the residue: 'ccnnnnilttt'
*                       c: Chain ID, space for none
*                       n: sequence number, right justified, space padded
*                       i: insertion code, space for none
*                       l: alternate ID, space for none
*                       t: residue type (ALA, LYS, etc.), all caps,
*                          left justified, space padded
*   resType         3-letter residue code (e.g. ALA)
*   chainID         1-letter chain ID or ' '
*   resNum          residue number
*   insCode         insertion code or ' '
*   altID           alternate ID or ' '
*   scorePct        the fraction score from 0 (bad) to 1 (good)
*   alpha           similarity to alpha helix
*   beta            similarity to beta sheet
*   threeten        similarity to threeten helix
*residue : outlier_type : contour_level : ca_contour_level : sec struc recommendation : alpha score : beta score : threeten score
*/
function loadCablam($datafile)
{
    $data = array_slice(file($datafile), 1); // drop first line
    $ret = array();
    foreach($data as $line)
    {
        $line = explode(':', rtrim($line));
        if (preg_match("/^SUMMARY/",$line[0])){
          continue;
        }
        #elseif (preg_match("/^residue/",$line[0])){
        #  continue;
        #}
        $cnit = $line[0];
        $decomp = decomposeResName($cnit);
        $ret[$cnit] = array(
            'resName'     => $cnit,
            'resType'     => $decomp['resType'],
            'chainID'     => $decomp['chainID'],
            'resNum'      => $decomp['resNum'],
            'insCode'     => $decomp['insCode'],
            'altID'       => $decomp['altID'],
            'outlierType' => $line[1],
            'cablamScore' => $line[2]*100,
            'caGeomScore' => $line[3]*100,
            'secStruc'    => $line[4],
            'alpha'       => $line[5] + 0,
            'beta'        => $line[6] + 0,
            'threeten'    => $line[7] + 0
        );
    }
    return $ret;
}
#}}}########################################################################

#{{{ loadCablamSummary - extract SUMMARY lines from cablam output
############################################################################
function loadCablamSummary($datafile)
{
    $data = array_slice(file($datafile), -7); //look at last 7 lines, where summaries are
    foreach($data as $line)
    {
        if (preg_match("/^SUMMARY/",$line)){
//SUMMARY: Note: Regardless of number of alternates, each residue is counted as having at most one outlier.
//SUMMARY: CaBLAM found 225 full protein residues and 0 CA-only residues
//SUMMARY: 15 residues (6.7%) have disfavored conformations. (<=5% expected).
//SUMMARY: 5 residues (2.2%) have outlier conformations. (<=1% expected)
//SUMMARY: 2 residues (0.89%) have severe CA geometry outliers. (<=0.5% expected)
//SUMMARY: 63 residues (28.00%) are helix-like, 41 residues (18.22%) are beta-like
//SUMMARY: 0 residues (0.00%) are correctable to helix, 0 residues (0.00%) are correctable to beta
            if (preg_match("/full/",$line)){
                $linebits = explode(' ',$line);
                $residues = $linebits[3] + $linebits[8];
            }
            elseif (preg_match("/have outlier/",$line)){
                $linebits = explode(' ',$line);
                $cablam_outlier_count = $linebits[1];
                $cablam_outlier_percent = $linebits[3];//(2.2%)
                $cablam_outlier_percent = explode('%',ltrim($cablam_outlier_percent,"("))[0];
            }
            elseif (preg_match("/have severe/",$line)){
                $linebits = explode(' ',$line);
                $cageom_outlier_count = $linebits[1];
                $cageom_outlier_percent = $linebits[3];
                $cageom_outlier_percent = explode('%',ltrim($cageom_outlier_percent,"("))[0];
            }
            else{
                continue;
            }
        }
        else{
            continue;
        }
    }
    $ret = array(
        'residues'               => $residues,
        'cablam_outlier_count'   => $cablam_outlier_count,
        'cablam_outlier_percent' => $cablam_outlier_percent,
        'cageom_outlier_count'   => $cageom_outlier_count,
        'cageom_outlier_percent' => $cageom_outlier_percent);

    return $ret;
}
#}}}########################################################################

#{{{ findCablamOutliers - evaluates residues for bad score
/**
* Returns array of cnit residue names from loadCablam function above
* Default cutoff level is 0.05 for disfavored; pass 0.01 for disallowed instead
* ' Peptide Outlier    '
* ' Peptide Disfavored '
* ' CA Geo Outlier     '
**/
function findCablamOutliers($cablam)
{
    $worst = array();
    if(is_array($cablam)) foreach($cablam as $res)
    {
        ////This is only used to get a count of outliers
        //if($res['outlierType'] != '                    ')
        //    $worst[$res['resName']] = $res['cablamScore'];
        if(preg_match("/Outlier/",$res['outlierType']))
            $worst[$res['resName']] = $res['cablamScore'];
    }
    ksort($worst);
    return $worst;
}
#}}}

#{{{ runValidationReport - finds >4sigma geometric outliers for protein and RNA
############################################################################
function runValidationReport($infile, $outfile, $use_cdl)
{
    //exec("java -Xmx512m -cp ".MP_BASE_DIR."/lib/dangle.jar dangle.Dangle -$moltype -validate -outliers -sigma=0.0 $infile > $outfile");
    if($use_cdl) {$uc = "True";}
    else {$uc = "False"; }
    exec("mmtbx.mp_geo pdb=$infile out_file=$outfile cdl=$uc outliers_only=False bonds_and_angles=True",$arg_list_filler,$mpgeo_return_code);
    return $mpgeo_return_code;
}
#}}}########################################################################

#{{{ loadValidationBondReport - loads Dangle's geometry statistics (bonds)
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
*   measure         bond (A--B) or angle (A-B-C)
*   value           value of the bond or angle measurement
*   sigma           deviation from ideality
*   bondCount       number of bonds analyzed
*   outCount        number of bonds with >4sigma
*   isOutlier       with the -outliers flag all output are >4sigma outliers
*/
function loadValidationBondReport($datafile, $moltype)
{
#1m5u.pdb: A:  10: :B:ASP:CG--OD1:1.839:31.054
    $data = file($datafile);
    //$ret = array(); // needs to return null if no data!
    $hash1_1 = array();
    foreach($data as $line)
    {
        //if(startsWith($line, "#")) continue;
        //if(startsWith($line, "#bonds"))
        //{
        //  $line = explode(':', rtrim($line));
        //  $n_outliers = $line[1];
        //  $n_total = $line[2];
        //  continue;
        //}
        $line = explode(':', rtrim($line));
        $cnit = $line[1].$line[2].$line[3].$line[4].$line[5];
        //echo "'".$cnit."'\n";
        //$decomp = decomposeResName($cnit);
        $measure = $line[6];
        $value = $line[7] + 0;
        $sigma = $line[8] + 0;
        $type = $line[9];
        if ($moltype == "protein")
        {
          if ($type != "PROTEIN") continue;
        }
        elseif ($moltype == "rna")
        {
          if ($type != "NA") continue;
        }
        if (preg_match("/--/", $measure)) {
            if (array_key_exists($cnit, $hash1_1)) {
                $old_sigma_bond  = $hash1_1[$cnit]['sigma'];
                if (abs($sigma) > abs($old_sigma_bond)) {
                    $hash1_1[$cnit]['measure'] = $measure;
                    $hash1_1[$cnit]['value'] = $value;
                    $hash1_1[$cnit]['sigma'] = $sigma;
                }
                if (abs($sigma) > 4) {
                    $hash1_1[$cnit]['outCount'] = $hash1_1[$cnit]['outCount'] + 1;
                    $hash1_1[$cnit]['isOutlier'] = true;
                }
                $hash1_1[$cnit]['bondCount'] = $hash1_1[$cnit]['bondCount'] + 1;
            }
            else {
                $hash1_1[$cnit] = array(
                    'resName' => $cnit,
                    'type'    => $moltype,
                    'measure' => $measure,
                    'value'   => $value,
                    'sigma'   => $sigma,
                    'bondCount'   => 1,
                    'outCount'   => 0
                    //'isOutlier' => true
                );
                if (abs($sigma) > 4) {
                    $hash1_1[$cnit]['isOutlier'] = true;
                    $hash1_1[$cnit]['outCount'] = 1;
                }
                else $hash1_1[$cnit]['isOutlier'] = false;
            }
        }
    }
    return $hash1_1;
    //$hash1_1_length = count($hash1_1);
    //$hash1_2_length = count ($hash1_2);
    //if ($hash1_1_length > 0) {return $hash1_1; }
    //else { return $null; }
}
#}}}########################################################################

#{{{ loadValidationAngleReport - loads Dangle's geometry statistics (angles)
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
*   measure         bond (A--B) or angle (A-B-C)
*   value           value of the bond or angle measurement
*   sigma           deviation from ideality
*   angCount        number of angles
*   outCount        number of angles with >4sigma
*   isOutlier       with the -outliers flag all output are >4sigma outliers
*/
function loadValidationAngleReport($datafile, $moltype)
{
#1m5u.pdb: A:  10: :B:ASP:OD1-CG-OD2:109.733:5.486
    $data = file($datafile);
    //$ret = array(); // needs to return null if no data!
    //$hash1_1 = array();
    $hash1_2 = array();
    //$cnit = "";
    foreach($data as $line)
    {
        //if(startsWith($line, "#")) continue;
        $line = explode(':', rtrim($line));
        $cnit = $line[1].$line[2].$line[3].$line[4].$line[5];
        //$decomp = decomposeResName($cnit);
        $measure = $line[6];
        $value = $line[7] + 0;
        $sigma = $line[8] + 0;
        $type = $line[9];
        if ($moltype == "protein")
        {
          if ($type != "PROTEIN") continue;
        }
        elseif ($moltype == "rna")
        {
          if ($type != "NA") continue;
        }
        if (preg_match("/-.-/",$measure) || preg_match("/-..-/",$measure) || preg_match("/-...-/",$measure)) {
            if (array_key_exists($cnit, $hash1_2)) {
                $old_outlier_sigma = $hash1_2[$cnit]['sigma'];
                if (abs($sigma) > abs($old_outlier_sigma)) {
                    $hash1_2[$cnit]['measure'] = $measure;
                    $hash1_2[$cnit]['value'] = $value;
                    $hash1_2[$cnit]['sigma'] = $sigma;
                }
                if (abs($sigma) > 4) {
                    $hash1_2[$cnit]['outCount'] = $hash1_2[$cnit]['outCount'] + 1;
                    $hash1_2[$cnit]['isOutlier'] = true;
                }
                $hash1_2[$cnit]['angCount'] = $hash1_2[$cnit]['angCount'] + 1;
            }
            else {
                //$count = 1;
                $hash1_2[$cnit] = array(
                    'resName' => $cnit,
                    'type'    => $moltype,
                    'measure' => $measure,
                    'value'   => $value,
                    'sigma'   => $sigma,
                    'angCount' => 1,
                    'outCount' => 0
                    //'isOutlier' => true
                );
                if (abs($sigma) > 4) {
                    $hash1_2[$cnit]['isOutlier'] = true;
                    $hash1_2[$cnit]['outCount'] = 1;
                }
                else $hash1_2[$cnit]['isOutlier'] = false;
            }

        }
    }
    return $hash1_2;
    //$hash1_2_length = count($hash1_2);
    //if ($hash1_2_length > 0 ) {return $hash1_2; }
    //else { return $null; }
}

#}}}########################################################################

#{{{ load_chiral_summary
############################################################################
function load_chiral_summary($chiral_result_file)
{
    #SUMMARY: 3 outliers out of 98 CA chiral centers (3.06%)
    #SUMMARY: 0 outliers out of 47 other chiral centers (0.00%)
    $chiral_outliers = 0;
    $chiral_centers = 0;
    $total_outliers = 0;
    $tetra_centers = 0;
    $tetra_outliers = 0;
    $pseudochiral_outliers = 0;
    $data = file($chiral_result_file);
    foreach($data as $line)
    {
      #SUMMARY: 22 total outliers at 67 tetrahedral centers (32.84%)
      #SUMMARY: 8 handedness outliers at 65 chiral centers (12.31%)
      #SUMMARY: 13 tetrahedral geometry outliers
      #SUMMARY: 1 pseudochiral naming errors
      #$line = explode(' ', rtrim($line));
      #if ($line[1] == 'outliers' and $line[5] == 'CA')
      if (preg_match("/^SUMMARY/",$line))
      {
        $line = explode(' ', rtrim($line));
        if ($line[2] == "handedness")
        {
          $chiral_outliers += $line[1]+0;
          $chiral_centers  += $line[5]+0;
        }
        elseif ($line[2] == "total")
        {
          $total_outliers += $line[1]+0;
          $tetra_centers  += $line[5]+0;
        }
        elseif ($line[2] == "tetrahedral")
        {
          $tetra_outliers += $line[1]+0;
        }
        elseif ($line[2] == "pseudochiral")
        {
          $pseudochiral_outliers += $line[1]+0;
        }
      }
     ###if ($line[1] == 'outliers' and $line[5] == 'CA')
#    ### if (preg_match("/CA chiral/",$line))
     ###{
     ###  ##$line = explode(' ', rtrim($line));
     ###  $other_outliers += $line[0]+0;
     ###  $other_centers += $line[4]+0;
      #}
    }
    #$outliers = $ca_outliers+$other_outliers;
    #$centers = $ca_centers+$other_centers;
    if ($chiral_centers == 0) $percent_chiral_outliers = 0;
    else $percent_chiral_outliers = $chiral_outliers*100/$chiral_centers;
    #if ($tetra_centers == 0) $percent_tetra_outliers = 0;
    #else $percent_tetra_outliers = $tetra_outliers*100/$tetra_centers;
    $hash = array(
      'chiral_outliers' => $chiral_outliers,
      'chiral_centers' => $chiral_centers,
      'percent_chiral_outliers' => $percent_chiral_outliers,
      'tetra_outliers' => $tetra_outliers,
      'tetra_centers' => $tetra_centers,
      'total_outliers' => $total_outliers,
      'pseudo_outliers' => $pseudochiral_outliers);
    return $hash;
}
#}}}############################################################################

#{{{ loadUndowserSummary
function loadUndowserSummary($undowser_result_file)
{
  $data = file($undowser_result_file);
  foreach($data as $line)
  {
    #SUMMARY: 6 waters out of 86 have clashes (6.98%) 
    if (preg_match("/^SUMMARY/",$line))
    {
      $line = explode(' ', rtrim($line));
      $clashes = $line[1];
      $waters = $line[5];
      $clashpct = $line[8];
      $clashpct = substr($clashpct,1,-2); #remove '(' and '%)'
    }
  }
  $hash = array(
    'water_clashes' => $clashes,
    'water_count' => $waters,
    'water_clash_pct' => $clashpct);
  return $hash;
}
#}}}############################################################################

#{{{ findGeomOutliers - evaluates residues for bad score
############################################################################
/**
* Returns an array of 9-char residue names for residues that
* fall outside the allowed boundaries for this criteria.
* Inputs are from appropriate loadXXX() function above.
*/
function findGeomOutliers($geom)
{
    $worst = array();
    if(is_array($geom)) foreach($geom as $res)
    {
        if($res['isOutlier'])
            $worst[$res['resName']] = $res['resName'];
    }
    ksort($worst); // Put the residues into a sensible order
    return $worst;
}
#}}}########################################################################

#{{{ findAltTotal - count total residues, accounting for alternates
############################################################################
function findAltTotal($metric)
{
    $total = count($metric);
    $altTrim = array();
    if(is_array($metric)) foreach($metric as $res)
    {
        $altloc = substr($res['resName'],7,1);
        if ($altloc != ' ')
        {
          $b_key = substr($res['resName'],0,7).' '.substr($res['resName'],8,3);
          //if (isset($res['resName'][$b_key]))
          if (isset($metric[$b_key]))
          {
            $altTrim[$b_key] = 1;
          }
        }
    }
    $total -= count($altTrim);
    return $total;
}
#}}}########################################################################

#{{{ hasMoltype - determines if a geometry array has residues of type moltype
############################################################################
/**
* For figuring out whether a geometry array has protein or nucleic acid in it.
*/
function hasMoltype($geom, $moltype) {
    if(is_array($geom)) foreach($geom as $res) {
        if($res['type'] == $moltype) {
            return true;
        }
    }
    return false;
}
#}}}########################################################################


#{{{ runJiffiloop - fills gaps in protein structures with fragments
############################################################################
/**
* Documentation for this function.
*/
function runJiffiloop($inFile, $outPdbPrefix, $args) {
    $ffcom = "java -Xmx512m -jar ".MP_BASE_DIR."/lib/jiffiloop.jar ";
    //$ffcom .= "-libloc ".MP_BASE_DIR."/lib/fragmentfiller/ -pdbloc ".MP_BASE_DIR."/lib/fragmentfiller/pdblibrary/ ";
    $ffcom .= "-pdbloc ".MP_BASE_DIR."/lib/jiffiloop/pdblibrary/ ";
    $ffcom .= "$args $inFile $outPdbPrefix";
  exec($ffcom);
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

    //calculate all distances and build association matrix
    foreach($res_xyz as $cnit => $xyz)
    {
        foreach($self_bads as $cnit2 => $bads2)
        {
             $xyz2 = $res_xyz[$cnit2];
             $dx = $xyz['x'] - $xyz2['x'];
             $dy = $xyz['y'] - $xyz2['y'];
             $dz = $xyz['z'] - $xyz2['z'];
             if($dx*$dx + $dy*$dy + $dz*$dz <= $range2 && $self_bads[$cnit]!=0)
             {
                    $local_mat[$cnit][$cnit2]=1;
             }
        }
    }

    while(true)
    {
        //at each iteration count of how bad each case is
        $local_bads = array();
        foreach($res_xyz as $cnit => $xyz)
        {
            foreach($self_bads as $cnit2 => $bads2)
            {
                if($local_mat[$cnit][$cnit2]==1 && !preg_match('/(HOH|DOD|H20|D20|WAT|SOL|TIP|TP3|MTO|HOD|DOH)/',$cnit))
                {
                    $local_bads[$cnit] += $bads2;
                }
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
        #$xyz = $res_xyz[$cnit];
        $leftover_bads = 0;
        foreach($self_bads as $cnit2 => $bads)
        {
            if($local_mat[$cnit][$cnit2]==1)
            {
                unset($self_bads[$cnit2]); // faster than 0 -- won't traverse again
                unset($local_bads[$cnit2]);
            }
        }
        if(count($self_bads)==0) break;
        //limit the number of cycles to 25
        $cycles++;
        if($cycles > 25) break;
    }
    #var_export($worst_res); echo "\n==========\n";

    return $worst_res;
}
#}}}########################################################################

# old, deprecated version - removed on 131127 by JJH
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
/*function decomposeResName($name)
{
    if(!$_SESSION['useSEGID'])
    {
      return array(
        'resType'   => substr($name, 7, 3),
        'chainID'   => substr($name, 0, 2),
        'resNum'    => trim(substr($name, 2, 4))+0,
        'insCode'   => substr($name, 6, 1));
    }
    else
    {
      return array(
        'resType'   => substr($name, 9, 3),
        'chainID'   => substr($name, 0, 4),
        'resNum'    => trim(substr($name, 4, 4))+0,
        'insCode'   => substr($name, 8, 1));
    }
}*/
#}}}########################################################################

#{{{ decomposeResName - breaks a 11-character packed name into pieces
############################################################################
/**
* Decomposes this: ' A  10 AASP'
*   resName         a formatted name for the residue: 'ccnnnnilttt'
*                       c: 2-char Chain ID, space for none
*                       n: sequence number, right justified, space padded
*                       i: insertion code, space for none
*                       l: alternate ID, space for none
*                       t: residue type (ALA, LYS, etc.), all caps,
*                          left justified, space padded
*
* Into this (as an array):
*   resType         3-letter residue code (e.g. ALA)
*   chainID         2-letter chain ID or '  '
*   resNum          residue number
*   insCode         insertion code or ' '
*   altID           alternate ID or ' '
*/
function decomposeResName($name)
{
    if(!$_SESSION['useSEGID'])
    {
      return array(
        'resType'   => substr($name, 8, 3),
        'chainID'   => substr($name, 0, 2),
        'resNum'    => trim(substr($name, 2, 4))+0,
        'insCode'   => substr($name, 6, 1),
        'altID'     => substr($name, 7, 1));
    }
    else
    {
      return array(
        'resType'   => substr($name, 10, 3),
        'chainID'   => substr($name, 0, 4),
        'resNum'    => trim(substr($name, 4, 4))+0,
        'insCode'   => substr($name, 8, 1),
        'altID'     => substr($name, 9, 1));
    }
}

#}}}########################################################################

#{{{ decomposeResNameCctbx - breaks a 9-character packed name into pieces
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
function decomposeResNameCctbx($name)
{
    return array(
        'resType'   => substr($name, 7, 3),
        'chainID'   => substr($name, 0, 1),
        'resNum'    => trim(substr($name, 1, 4))+0,
        'insCode'   => substr($name, 5, 1)
    );
}
#}}}########################################################################

#{{{ pdbComposeResName - makes a 11-char res ID from a PDB ATOM line
############################################################################
function pdbComposeResName($pdbline)
{
    if(!$_SESSION['useSEGID'])
    {
      return substr($pdbline, 20, 7) . substr($pdbline, 16, 4);
    }
    else
    {
      return substr($pdbline, 72, 4) . substr($pdbline, 22, 5) . substr($pdbline, 16, 4);
    }
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

#{{{ mapAlternates - find all alternates for each residue
############################################################################
function mapAlternates($res)
{
    $out = array();

    foreach($res as $k => $v)
    {
      $altloc = substr($v, 7, 1);
      $key = substr($v,0,7).' '.substr($v,8,3);
      if ($altloc != ' ')
      {
        if (!isset($out[$key]))
        {
          $out[$key] = array();
        }
        $out[$key][] = $altloc;
      }
    }
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

#{{{ listAtomResidues - lists CNIT codes for amino acid residues in a PDB
############################################################################
/**
* Returns NULL if the file could not be read.
* Otherwise, an array of CNIT 9-char residue codes.
* Does not account for the possibility of multiple MODELs
*/
function listAtomResidues($infile) {
    $out = array();

    $in = fopen($infile, "r");
    if(!$in) return NULL;
    while(!feof($in))
    {
        $s = fgets($in, 1024);
        if(startsWith($s, "ATOM"))
        {
            $res = pdbComposeResName($s);
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
            //echo "'".$cnit."'\n";
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
function computeResCenters($infile, $excludeWaters = false)
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
            if($excludeWaters && preg_match('/(HOH|DOD|H20|D20|WAT|SOL|TIP|TP3|MTO|HOD|DOH)/', $s)) continue;
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
        if(!$_SESSION['useSEGID'])
        {
          $num = substr($res, 2, 4) + 0;
          // If old run is ending, append it and start fresh:
          if(isset($run) && !($num - $prevNum <= 1 && $chainID == $res{0}))
          {
              $out[$chainID][] = $run;
              unset($run);
          }
          // Append this residue to the current run (which is potentially empty)
          $prevNum    = $num;
          $chainID    = substr($res,0,2);
          $run[]      = $res;
        }
        else
        {
          $num = substr($res, 4, 4) + 0;
          // If old run is ending, append it and start fresh:
          if(isset($run) && !($num - $prevNum <= 1 && $chainID == $res{0}))
          {
              $out[$chainID][] = $run;
              unset($run);
          }
          // Append this residue to the current run (which is potentially empty)
          $prevNum    = $num;
          $chainID    = substr($res,0,4);
          $run[]      = $res;
        }
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
