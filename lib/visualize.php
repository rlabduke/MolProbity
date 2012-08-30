<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Provides kinemage-creation functions for visualizing various
    aspects of the analysis.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/pdbstat.php');
require_once(MP_BASE_DIR.'/lib/analyze.php');
require_once(MP_BASE_DIR.'/lib/sortable_table.php');
require_once(MP_BASE_DIR.'/lib/eff_resol.php');

#{{{ makeRamachandranKin - creates a kinemage-format Ramachandran plot
############################################################################
function makeRamachandranKin($infile, $outfile)
{
    //exec("java -Xmx256m -cp ".MP_BASE_DIR."/lib/hless.jar hless.Ramachandran -nosummary $infile > $outfile");
    exec("java -Xmx256m -cp ".MP_BASE_DIR."/lib/chiropraxis.jar chiropraxis.rotarama.Ramalyze -kinplot $infile > $outfile");
}
#}}}########################################################################

#{{{ makeRamachandranPDF - creates a multi-page PDF-format Ramachandran plot
############################################################################
function makeRamachandranPDF($infile, $outfile)
{
    exec("java -Xmx256m -cp ".MP_BASE_DIR."/lib/chiropraxis.jar chiropraxis.rotarama.Ramalyze -pdf $infile $outfile");
}
#}}}########################################################################

#{{{ [NOT SUPPORTED] convertKinToPostscript - uses Mage to do EPS output
############################################################################
// Would have to add Mage to bin/ for this to work again.
/**
* Outputs are named $infile.1.eps, $infile.2.eps, etc.
* One page is generated per frame of animation.
* /
function convertKinToPostscript($infile)
{
    exec("mage -postscript $infile");
}
*/
#}}}########################################################################

#{{{ makeCbetaDevPlot - creates a 2-D kinemage scatter plot
############################################################################
function makeCbetaDevPlot($infile, $outfile)
{
    exec("prekin -cbdevdump $infile | java -cp ".MP_BASE_DIR."/lib/hless.jar hless.CBScatter > $outfile");
}
#}}}########################################################################

#{{{ makeSuitenameKin - creates a high-D plot of suite conformations
############################################################################
function makeSuitenameKin($infile, $outfile)
{
    exec("java -Xmx256m -cp ".MP_BASE_DIR."/lib/dangle.jar dangle.Dangle rnabb $infile | suitename -kinemage > $outfile");
}
#}}}########################################################################

#{{{ makeFlipkin - runs Flipkin to generate a summary of the Reduce -build changes
############################################################################
/**
*/
function makeFlipkin($inpath, $outpathAsnGln, $outpathHis)
{
    // $_SESSION[hetdict] is used to set REDUCE_HET_DICT environment variable,
    // so it doesn't need to appear on the command line here.
    //exec("flipkin -limit" . MP_REDUCE_LIMIT . " $inpath > $outpathAsnGln");
    //exec("flipkin -limit" . MP_REDUCE_LIMIT . " -h $inpath > $outpathHis");
    exec("flipkin $inpath > $outpathAsnGln");
    exec("flipkin -h $inpath > $outpathHis");
}
#}}}########################################################################

#{{{ resGroupsForPrekin - converts residue groups to Prekin switches
############################################################################
/**
* Takes a group of residues in the format produced by
* lib/analyze.php:groupAdjacentRes() and returns an array of
* Prekin switches specifying those residues:
*   -chainid _ -range "1-2,4-5,10-10"
*   -chainid A -range "1-47,100-101"
* etc.
* The array keys are the chainIDs, using _ for blank.
*/
function resGroupsForPrekin($data)
{
    $out = array();
    foreach($data as $chainID => $chain)
    {
        if($chainID == ' ') $chainID = '_';
        $line   = "-chainid $chainID -range \"";
        $comma  = false;
        foreach($chain as $run)
        {
            reset($run);
            $first  = trim(substr(current($run), 1, 4));;
            $last   = trim(substr(end($run), 1, 4));
            if($comma) $line .= ',';
            else $comma = true;
            $line .= "$first-$last";
        }
        $line .= '"';
        $out[$chainID] = $line;
    }
    return $out;
}
#}}}########################################################################



#{{{ [deprecated] makeMulticritKin - display all quality metrics at once in 3-D
############################################################################
/**
* $infiles is an array of one or more PDB files to process
* $outfile will be overwritten.
* $opt controls what will be output. Each key below maps to a boolean:
*   Bscale              color scale by B-factor
*   Qscale              color scale by occupancy
*   altconf             alternate conformations
*   ribbons             ribbons rainbow colored N to C
*   rama                Ramachandran outliers
*   rota                rotamer outliers
*   cbdev               C-beta deviations greater than 0.25A
*   pperp               phosphate-base perpendicular outliers
*   dots                all-atom contacts dots
*       hbdots          H-bond dots
*       vdwdots         van der Waals (contact) dots
* $nmrConstraints is optional, and if present will generate lines for violated NOEs
*/
function makeMulticritKin($infiles, $outfile, $opt, $nmrConstraints = null)
{
    if(file_exists($outfile)) unlink($outfile);
    
    $stats = describePdbStats( pdbstat(reset($infiles)), false );
    $h = fopen($outfile, 'a');
    fwrite($h, "@text\n");
    foreach($stats as $stat)
        fwrite($h, "[+]   $stat\n");
    $isMultiModel = (count($infiles) > 1);
    if($isMultiModel)
        fwrite($h, "Statistics for first model only; ".count($infiles)." total models included in kinemage.\n");
    fwrite($h, "@kinemage 1\n");
    fwrite($h, "@onewidth\n");
    fclose($h);
    
    foreach($infiles as $infile)
    {
        // Animate is used only for multi-model kins.
        exec("prekin -quiet -lots -append ".($isMultiModel ? "-animate" : "")." -onegroup $infile >> $outfile");
        
        if($opt['ribbons'])
        {
            if($isMultiModel) makeRainbowRibbons($infile, $outfile);
            else
            {
                $h = fopen($outfile, 'a');
                fwrite($h, "@group {ribbon} off dominant\n");
                fclose($h);
                makeBfactorRibbons($infile, $outfile);
            }
        }
        if($nmrConstraints)
        {
            if(!$isMultiModel) { $h = fopen($outfile, 'a'); fwrite($h, "@group {NOEs} dominant\n"); fclose($h); }
            exec("noe-display -cv -s viol -ds+ -fs -k $infile $nmrConstraints < /dev/null >> $outfile");
        }
        if($opt['dots'])
        {
            if(!$isMultiModel) { $h = fopen($outfile, 'a'); fwrite($h, "@group {Probe dots} dominant\n"); fclose($h); }
            makeProbeDots($infile, $outfile, $opt['hbdots'], $opt['vdwdots']);
        }
        if($opt['rama'])
        {
            if(!$isMultiModel) { $h = fopen($outfile, 'a'); fwrite($h, "@group {Rama outliers} dominant\n"); fclose($h); }
            makeBadRamachandranKin($infile, $outfile);
        }
        if($opt['rota'])
        {
            if(!$isMultiModel) { $h = fopen($outfile, 'a'); fwrite($h, "@group {rotamer outliers} dominant\n"); fclose($h); }
            makeBadRotamerKin($infile, $outfile);
        }
        if($opt['cbdev'])
        {
            if(!$isMultiModel) { $h = fopen($outfile, 'a'); fwrite($h, "@group {Cb deviations} dominant\n"); fclose($h); }
            makeBadCbetaBalls($infile, $outfile);
        }
        if($opt['pperp'])
        {
            if(!$isMultiModel) { $h = fopen($outfile, 'a'); fwrite($h, "@group {base-P outliers} dominant\n"); fclose($h); }
            makeBadPPerpKin($infile, $outfile);
        }
        if($opt['Bscale'])
        {
            if(!$isMultiModel) { $h = fopen($outfile, 'a'); fwrite($h, "@group {B factors} off recessiveon\n"); fclose($h); }
            makeBfactorScale($infile, $outfile);
        }
        if($opt['Qscale'])
        {
            if(!$isMultiModel) { $h = fopen($outfile, 'a'); fwrite($h, "@group {occupancy} off recessiveon\n"); fclose($h); }
            makeOccupancyScale($infile, $outfile);
        }
        if($opt['altconf'])
        {
            if(!$isMultiModel) { $h = fopen($outfile, 'a'); fwrite($h, "@group {alt confs} off recessiveon\n"); fclose($h); }
            makeAltConfKin($infile, $outfile);
        }
    }

    // KiNG allows us to do this to control what things are visible
    $h = fopen($outfile, 'a');
    fwrite($h, "@master {mainchain} off\n");
    fwrite($h, "@master {sidechain} off\n");
    fwrite($h, "@master {H's} off\n");
    fwrite($h, "@master {water} off\n");
    fwrite($h, "@master {Calphas} on\n");

    if($opt['dots'])
    {
        fwrite($h, "@master {wide contact} off\n");
        fwrite($h, "@master {close contact} off\n");
        fwrite($h, "@master {small overlap} off\n");
        fwrite($h, "@master {H-bonds} off\n");
    }

    if($isMultiModel)
    {
        // Turns ribbons off but makes sure alpha/beta/coil are on,
        // so it just takes one click to make ribbons visible.
        if($opt['ribbons'])     fwrite($h, "@master {alpha} on\n");
        if($opt['ribbons'])     fwrite($h, "@master {beta} on\n");
        if($opt['ribbons'])     fwrite($h, "@master {coil} on\n");
        if($opt['ribbons'])     fwrite($h, "@master {ribbon} off\n");
        if($opt['Bscale'])      fwrite($h, "@master {B factors} off\n");
        if($opt['Qscale'])      fwrite($h, "@master {occupancy} off\n");
        if($opt['altconf'])     fwrite($h, "@master {mc alt confs} off\n");
        if($opt['altconf'])     fwrite($h, "@master {sc alt confs} off\n");
    }
    fclose($h);
}
#}}}########################################################################

#{{{ makeMulticritKin2 - display all quality metrics at once in 3-D
############################################################################
/**
* $infiles is an array of one or more PDB files to process
* $outfile will be overwritten.
* $opt controls what will be output. Each key below maps to a boolean:
*   Bscale              color scale by B-factor
*   Qscale              color scale by occupancy
*   altconf             alternate conformations
*   ribbons             ribbons rainbow colored N to C
*   rama                Ramachandran outliers
*   rota                rotamer outliers
*   geom                Bond length and angle geometry
*   cbdev               C-beta deviations greater than 0.25A
*   pperp               phosphate-base perpendicular outliers
*   clashdots           small and bad overlap dots
*   hbdots              H-bond dots
*   vdwdots             van der Waals (contact) dots
* $viewRes is a list of 9-char "CNIT" names of residues for which there should views
*   (e.g. outliers that someone should inspect, etc.)
* $nmrConstraints is optional, and if present will generate lines for violated NOEs
* $clashLimit is an option for probe, to set the clash cutoff to something different than -0.4
*/
function makeMulticritKin2($infiles, $outfile, $opt, $viewRes = array(), $nmrConstraints = null, $clashLimit = null)
{
    if(file_exists($outfile)){ 
        if(!unlink($outfile)){
            echo "delete .kin file failed!\n";
        }
    }
    
    $pdbstats = pdbstat(reset($infiles));
    $stats = describePdbStats($pdbstats, false);
    $h = fopen($outfile, 'a');
    fwrite($h, "@text\n");
    foreach($stats as $stat)
        fwrite($h, "[+]   $stat\n");
    $isMultiModel = (count($infiles) > 1);
    if($isMultiModel)
        fwrite($h, "Statistics for first model only; ".count($infiles)." total models included in kinemage.\n");
    
    // For Dave and Jane: version numbers!
    // This *is* useful for looking at old multikins...
    fwrite($h, "\n\n\n");
    fwrite($h, exec("prekin -version")."\n");
    $reduce_help = explode("\n", shell_exec("reduce -help 2>&1"));
    fwrite($h, "$reduce_help[0]\n");
    //fwrite($h, "Probe: ".exec("probe -version")."\n"); -- Probe puts its version in the @caption
    
    fwrite($h, "@kinemage 1\n");
    fwrite($h, "@onewidth\n");
    fclose($h);
    
    $view_info = makeResidueViews(reset($infiles), $outfile, $viewRes);
    #echo "made residue views OK\n";
    
    foreach($infiles as $infile)
    {
        // Animate is used even for single models, so they can be appended later.
        //echo("prekin -quiet -lots -append -animate -onegroup $infile >> $outfile\n");
        exec("prekin -quiet -lots -append -animate -onegroup $infile >> $outfile");
        
        #echo "before ribbon options\n";
        if($opt['ribbons'])
        {
            //echo("I shouldn't be in here\n");
            if($isMultiModel)   makeRainbowRibbons($infile, $outfile);
            else                makeBfactorRibbons($infile, $outfile);
        }
        #echo "after ribbon options\n";
        
        if ($nmrConstraints)
            exec("noe-display -cv -s viol -ds+ -fs -k $infile $nmrConstraints < /dev/null >> $outfile");
        if($opt['clashdots'] || $opt['hbdots'] || $opt['vdwdots'])
        {
            #echo "making Probe Dots...\n";
            if ($clashLimit == null){
              makeProbeDots($infile, $outfile, $opt['hbdots'], $opt['vdwdots'], $opt['clashdots']);
            } else {
              makeProbeDots($infile, $outfile, $opt['hbdots'], $opt['vdwdots'], $opt['clashdots'], $clashLimit);
            }
            #echo "Probe dots OK\n";
        }
        if($opt['rama'])
        {
            #echo "making Rama...\n";
            makeBadRamachandranKin($infile, $outfile);
            #echo "Rama OK\n";
        }
        if($opt['rota'])
        {
            #"making Bad Rota...\n";
            makeBadRotamerKin($infile, $outfile);
            #"Bad Rota OK\n";
        }
        if($opt['geom'])
        {
            #"making Geom...\n";
            makeBadGeomKin($infile, $outfile);
            #"Geom OK\n";
        }
        if($opt['cbdev'])
        {
            #"making Cbeta...\n";
            makeBadCbetaBalls($infile, $outfile);
            #"Cbeta OK\n";
        }
        if($opt['pperp'])
        {
            #"Making BadPPerp...\n";
            makeBadPPerpKin($infile, $outfile);
            #"BadPPerp OK\n";
        }
        if($opt['Bscale'])
        {
            #"making Bfactor...\n";
            makeBfactorScale($infile, $outfile);
            #"Bfactor OK\n";
        }
        if($opt['Qscale'])
        {
            #"making Occupancy...\n";
            makeOccupancyScale($infile, $outfile);
            #"Occupancy OK\n";
        }
        if($opt['altconf'])
        {
            #"making Alts...\n";
            makeAltConfKin($infile, $outfile);
            #"Alts OK\n";
        }
        if($view_info)
        {
            #"making ViewMarkers...\n";
            makeResidueViewMarkers($outfile, $view_info);
            #"ViewMarkers OK\n";
        }
        #echo "make it through all options OK\n";
    }

    // KiNG allows us to do this to control what things are visible
    $h = fopen($outfile, 'a');
    fwrite($h, "@master {mainchain} off\n");
    fwrite($h, "@master {sidechain} off\n");
    if($pdbstats['hydrogens'] > 0)  fwrite($h, "@master {H's} off\n");
    if($pdbstats['waters'] > 0)     fwrite($h, "@master {water} off\n");
    fwrite($h, "@master {Calphas} on\n");
    fwrite($h, "@master {Virtual BB} on\n"); // for protein + DNA/RNA structures

    if($opt['vdwdots'])     fwrite($h, "@master {vdw contact} off\n");
    if($opt['clashdots'])   fwrite($h, "@master {small overlap} off\n");
    if($opt['hbdots'])      fwrite($h, "@master {H-bonds} off\n");

    if($opt['ribbons'])     fwrite($h, "@master {ribbon} off\n");
    if($opt['Bscale'])      fwrite($h, "@master {B factors} off\n");
    if($opt['Qscale'])      fwrite($h, "@master {occupancy} off\n");
    if($opt['altconf'] && $pdbstats['all_alts'] > 0)
    {
        fwrite($h, "@master {mc alt confs} off\n");
        fwrite($h, "@master {sc alt confs} off\n");
    }
    
    fclose($h);
}
#}}}########################################################################

#{{{ makeResidueViews - creates @view entries for all listed residue
############################################################################
/* Returns a data structure with view info. */
function makeResidueViews($infile, $outfile, $cnits, $excludeWaters = true)
{
    $h = fopen($outfile, 'a');
    fwrite($h, "@1viewid {Overview}\n"); // auto-determine center, span, etc.
    $centers = computeResCenters($infile, true);
    $views = array();
    $i = 2;
    foreach($cnits as $res)
    {
        if($excludeWaters && preg_match('/(HOH|DOD|H20|D20|WAT|SOL|TIP|TP3|MTO|HOD|DOH)$/', $res)) continue;
        $c = $centers[$res];
        fwrite($h, "@{$i}viewid {{$res}}\n@{$i}span 20\n@{$i}zslab 200\n@{$i}center $c[x] $c[y] $c[z]\n");
        #echo "@{$i}viewid {{$res}}\n@{$i}span 20\n@{$i}zslab 200\n@{$i}center $c[x] $c[y] $c[z]\n";
        $views[$i] = array(
            'num'   => $i,
            'id'    => $res,
            'x'     => $c['x'],
            'y'     => $c['y'],
            'z'     => $c['z'],
        );
        $i++;
    }
    fclose($h);
    return $views;
}
#}}}########################################################################

#{{{ makeResidueViewMarkers - creates objects marking each view center
############################################################################
/* Takes a data structure with view info, as returned by makeResidueViews(). */
function makeResidueViewMarkers($outfile, $views)
{
    $h = fopen($outfile, 'a');
    fwrite($h, "@subgroup {view markers} off dominant master= {view markers}\n");
    fwrite($h, "@vectorlist {view markers} color= gray\n");
    foreach($views as $v)
    {
        # Cross:
        #fwrite($h, "{View $v[num]: $v[id]}P ".($v['x']-0.25)." $v[y] $v[z] {\"} ".($v['x']+0.25)." $v[y] $v[z]\n");
        #fwrite($h, "{View $v[num]: $v[id]}P $v[x] ".($v['y']-0.25)." $v[z] {\"} $v[x] ".($v['y']+0.25)." $v[z]\n");
        #fwrite($h, "{View $v[num]: $v[id]}P $v[x] $v[y] ".($v['z']-0.25)." {\"} $v[x] $v[y] ".($v['z']+0.25)."\n");
        # Box:
        fwrite($h, "{View $v[num]: $v[id]}P ".($v['x']-0.25)." ".($v['y']-0.25)." ".($v['z']-0.25)." {\"} ".($v['x']+0.25)." ".($v['y']-0.25)." ".($v['z']-0.25)."\n");
        fwrite($h, "{\"} ".($v['x']+0.25)." ".($v['y']+0.25)." ".($v['z']-0.25)." {\"} ".($v['x']-0.25)." ".($v['y']+0.25)." ".($v['z']-0.25)."\n");
        fwrite($h, "{\"} ".($v['x']-0.25)." ".($v['y']-0.25)." ".($v['z']-0.25)."\n");
        fwrite($h, "{View $v[num]: $v[id]}P ".($v['x']-0.25)." ".($v['y']-0.25)." ".($v['z']+0.25)." {\"} ".($v['x']+0.25)." ".($v['y']-0.25)." ".($v['z']+0.25)."\n");
        fwrite($h, "{\"} ".($v['x']+0.25)." ".($v['y']+0.25)." ".($v['z']+0.25)." {\"} ".($v['x']-0.25)." ".($v['y']+0.25)." ".($v['z']+0.25)."\n");
        fwrite($h, "{\"} ".($v['x']-0.25)." ".($v['y']-0.25)." ".($v['z']+0.25)."\n");
        fwrite($h, "{View $v[num]: $v[id]}P ".($v['x']-0.25)." ".($v['y']-0.25)." ".($v['z']-0.25)." {\"} ".($v['x']-0.25)." ".($v['y']-0.25)." ".($v['z']+0.25)."\n");
        fwrite($h, "{View $v[num]: $v[id]}P ".($v['x']-0.25)." ".($v['y']+0.25)." ".($v['z']-0.25)." {\"} ".($v['x']-0.25)." ".($v['y']+0.25)." ".($v['z']+0.25)."\n");
        fwrite($h, "{View $v[num]: $v[id]}P ".($v['x']+0.25)." ".($v['y']-0.25)." ".($v['z']-0.25)." {\"} ".($v['x']+0.25)." ".($v['y']-0.25)." ".($v['z']+0.25)."\n");
        fwrite($h, "{View $v[num]: $v[id]}P ".($v['x']+0.25)." ".($v['y']+0.25)." ".($v['z']-0.25)." {\"} ".($v['x']+0.25)." ".($v['y']+0.25)." ".($v['z']+0.25)."\n");
    }
    fclose($h);
    return $views;
}
#}}}########################################################################

#{{{ makeAltConfKin - appends mc and sc alts
############################################################################
function makeAltConfKin($infile, $outfile, $mcColor = 'brown', $scColor = 'brown')
{
    $alts   = findAltConfs($infile);
    $mcGrp  = groupAdjacentRes(array_keys($alts['mc']));
    $scGrp  = groupAdjacentRes(array_keys($alts['sc']));
    $mc     = resGroupsForPrekin($mcGrp);
    $sc     = resGroupsForPrekin($scGrp);
    
    foreach($mc as $mcRange)
        exec("prekin -quiet -append -nogroup -listmaster 'mc alt confs' -bval -scope $mcRange -show 'mc($mcColor)' $infile >> $outfile");

    foreach($sc as $scRange)
        exec("prekin -quiet -append -nogroup -listmaster 'sc alt confs' -bval -scope $scRange -show 'sc($scColor)' $infile >> $outfile");
}
#}}}########################################################################

#{{{ makeBadRamachandranKin - appends mc of Ramachandran outliers
############################################################################
/**
* rama is the data from loadRamachandran(),
* or null to have the data generated automatically.
*/
function makeBadRamachandranKin($infile, $outfile, $rama = null, $color = 'green')
{
    // Put in subgroup to provide Rama master (not needed when using Prekin)
    $out = fopen($outfile, 'a');
    fwrite($out, "@subgroup {Rama outliers} master= {Rama outliers}\n");
    fclose($out);
    
    // Jane still likes this best.
    //exec("java -Xmx256m -cp ".MP_BASE_DIR."/lib/hless.jar hless.Ramachandran -nosummary -outliers $color < $infile >> $outfile");
    
    // New ramachandran kin from chiropraxis
    exec("java -Xmx256m -cp ".MP_BASE_DIR."/lib/chiropraxis.jar chiropraxis.rotarama.Ramalyze -kinmarkup $infile >> $outfile");
    
    // This uses Prekin, but just produces chunks of mainchain. Hard to see.
    /*if(!$rama)
    {
        $tmp = mpTempfile("tmp_rama_");
        runRamachandran($infile, $tmp);
        $rama = loadRamachandran($tmp);
        unlink($tmp);
    }
    
    foreach($rama as $res)
    {
        if($res['eval'] == 'OUTLIER')
            $worst[] = $res['resName'];
    }
    $mc = resGroupsForPrekin(groupAdjacentRes($worst));
    
    foreach($mc as $mcRange)
    {
        //echo("prekin -append -nogroup -listmaster 'Rama outliers' -scope $mcRange -show 'mc($color)' $infile >> $outfile\n");
        exec("prekin -append -nogroup -listmaster 'Rama outliers' -scope $mcRange -show 'mc($color)' $infile >> $outfile");
    }*/
}
#}}}########################################################################

#{{{ makeBadRotamerKin - appends sc of bad rotamers
############################################################################
/**
* rota is the data from loadRotamer(),
* or null to have it generated on the fly.
*/
function makeBadRotamerKin($infile, $outfile, $rota = null, $color = 'gold', $cutoff = 1.0)
{
    if(!$rota)
    {
        $tmp = mpTempfile("tmp_rota_");
        runRotamer($infile, $tmp);
        $rota = loadRotamer($tmp);
        unlink($tmp);
    }

    foreach($rota as $res)
    {
        if($res['scorePct'] <= $cutoff)
            $worst[] = $res['resName'];
    }
    $sc = resGroupsForPrekin(groupAdjacentRes($worst));
    
    $h = fopen($outfile, 'a');
    fwrite($h, "@subgroup {rotamer outliers} dominant\n");
    fclose($h);
    
    foreach($sc as $chainID => $scRange)
    {
        //echo("prekin -quiet -append -nogroup -nosubgroup -listname 'chain $chainID' -listmaster 'rotamer outliers' -bval -scope $scRange -show 'sc($color)' $infile >> $outfile\n");
        exec("prekin -quiet -append -nogroup -nosubgroup -listname 'chain $chainID' -listmaster 'rotamer outliers' -bval -scope $scRange -show 'sc($color)' $infile >> $outfile");
    }
}
#}}}########################################################################

#{{{ makeBadGeomKin - appends mc of Ramachandran outliers
############################################################################
function makeBadGeomKin($infile, $outfile) {
    //$out = fopen($outfile, 'a');
    exec("java -Xmx256m -cp ".MP_BASE_DIR."/lib/dangle.jar dangle.Dangle -kin -sub -validate -protein $infile >> $outfile");
    exec("java -Xmx256m -cp ".MP_BASE_DIR."/lib/dangle.jar dangle.Dangle -kin -sub -validate -rna $infile >> $outfile");
    //exec("java -Xmx256m -cp ".MP_BASE_DIR."/lib/chiropraxis.jar chiropraxis.dangle.Dangle -kin -validate -dna $infile >> $outfile");
}
#}}}########################################################################

#{{{ makeBadCbetaBalls - plots CB dev in 3-D, appending to the given file
############################################################################
function makeBadCbetaBalls($infile, $outfile)
{
    $h = fopen($outfile, 'a');
    fwrite($h, "@subgroup {CB dev} dominant\n");
    fclose($h);
    
    // C-beta deviation balls >= 0.25A
    $cbeta_dev_script = 
'BEGIN { doprint = 0; bigbeta = 0; }
$0 ~ /^@/ { doprint = 0; }
$0 ~ /^@(point)?master/ { print $0 }
$0 ~ /^@balllist/ { doprint = 1; print $0 " master= {Cbeta dev}"; }
{ bigbeta = 0 }
match($0, /^\{ cb .+ r=([0-9]\.[0-9]+) /, frag) { gsub(/gold|pink/, "magenta"); bigbeta = (frag[1]+0 >= 0.25); }
doprint && bigbeta';
    
    exec("prekin -append -quiet -cbetadev $infile | gawk '$cbeta_dev_script' >> $outfile");
}
#}}}########################################################################

#{{{ makeBadPPerpKin - plots phosphate-base perpendicular outliers on kin
############################################################################
function makeBadPPerpKin($infile, $outfile)
{
    $h = fopen($outfile, 'a');
    fwrite($h, "@subgroup {base-phos perp} dominant master= {base-P outliers}\n");
    fclose($h);
    
    exec("prekin -quiet -append -nogroup -pperptoline -pperpoutliers $infile >> $outfile");
}
#}}}########################################################################

#{{{ makeProbeDots - appends mc and sc Probe dots
############################################################################
/**
* Documentation for this function.
*/
function makeProbeDots($infile, $outfile, $hbDots = false, $vdwDots = false, $clashDots = true, $clashLimit = null)
{
    $options = "";
    if(!$hbDots)    $options .= " -nohbout";
    if(!$vdwDots)   $options .= " -novdwout";
    if(!$clashDots) $options .= " -noclashout";
    
    // -dotmaster adds a "dots" master -- useful when using this kin with Probe remote update
    if ($clashLimit == null) {
      exec("probe $options -4H -quiet -noticks -nogroup -dotmaster -mc -self 'alta' $infile >> $outfile");
    } else {
      exec("probe $options -DIVlow$clashLimit -4H -quiet -noticks -nogroup -dotmaster -mc -self 'alta' $infile >> $outfile");
    }
}
#}}}########################################################################

#{{{ makeRainbowRibbons - make ribbon kin color-coded N to C
############################################################################
/**
* Create a ribbon colored from N to C with varying hues
* Output will be appended onto outfile.
*/
function makeRainbowRibbons($infile, $outfile)
{
    exec("prekin -quiet -append -nogroup -colornc -bestribbon $infile >> $outfile");
}
#}}}########################################################################

#{{{ makeBfactorRibbons - make ribbon kin color-coded by C-alpha temp. factor
############################################################################
/**
* Create a ribbon colored by B-value:
* 0%--purple--40%--lilac--70%--lilactint--%90--white--%100
*
* Used to color a ribbon kinemage by B-factor!
* The mode==1 block extracts CA B-values from a PDB file
* The mode==2 block reads kinemage lines,
*   looks up the B-value of a given residue CA,
*   compares it to the rest of the structure to determine a color,
*   inserts the color name and writes the modified line.
*
* Output will be appended onto outfile.
*/
function makeBfactorRibbons($infile, $outfile)
{
    $bbB_ribbon_script =
'BEGIN { mode = 0; }
FNR == 1 {
    mode += 1;
    if(mode == 2) {
        size = asort(bvals, sortedbs);
        b1 = int((40 * size) / 100);
        b1 = sortedbs[b1];
        b2 = int((70 * size) / 100);
        b2 = sortedbs[b2];
        b3 = int((90 * size) / 100);
        b3 = sortedbs[b3];
    }
}
mode==1 && match($0, /ATOM  ...... CA  (...) (.)(....)(.)/, frag) {
    resno = frag[3] + 0;
    reslbl = tolower( frag[1] " " frag[2] " " resno frag[4] );
    bvals[reslbl] = substr($0, 61, 6) + 0;
}
mode==2 && match($0, /(^\{ *[^ ]+ ([^}]+))(\} *[PL] )(.+$)/, frag) {
    reslbl = frag[2];
    bval = bvals[reslbl];
    if(bval >= b3) color = "white";
    else if(bval >= b2) color = "lilactint";
    else if(bval >= b1) color = "lilac";
    else color = "purple";
    $0 = frag[1] " B" bval frag[3] color " " frag[4];
}
mode==2 { print $0; }';

    $tmp = mpTempfile("tmp_kin_");
    exec("prekin -append -bestribbon -nogroup $infile > $tmp");
    exec("gawk '$bbB_ribbon_script' $infile $tmp >> $outfile");
    unlink($tmp);
}
#}}}########################################################################

#{{{ makeBfactorScale - mc,sc colored by B-factor
############################################################################
/**
* Create a kinemage colored by B-value, using a black-body radiation scale.
* Output will be appended onto outfile.
*/
function makeBfactorScale($infile, $outfile)
{
    $colors = "-colorscale 'blue,5,purple,10,magenta,15,hotpink,20,red,30,orange,40,gold,50,yellow,60,yellowtint,80,white'";
    exec("prekin -quiet -append -nogroup -listmaster 'B factors' -bval -scope -show 'mc,sc' -bcol $colors $infile >> $outfile");
}
#}}}########################################################################

#{{{ makeOccupancyScale - mc,sc colored by occupancy
############################################################################
/**
* Create a kinemage colored by occupancy, using a mostly purple color scale.
* Output will be appended onto outfile.
*/
function makeOccupancyScale($infile, $outfile)
{
    $colors = "-colorscale 'white,0.02,lilactint,0.33,lilac,0.66,purple,0.99,gray,1.01,green'";
    exec("prekin -quiet -append -nogroup -listmaster 'occupancy' -bval -scope -show 'mc,sc' -ocol $colors $infile >> $outfile");
}
#}}}########################################################################



#{{{ makeSummaryStatsTable - HTML table summary of validation statistics
############################################################################
/**
* Documentation for this function.
*/
function makeSummaryStatsTable($resolution, $clash, $rama, $rota, $cbdev, $pperp, $suites, $bbonds, $bangles)
{
    $entry = "";
    $bgPoor = '#ff9999';
    $bgFair = '#ffff99';
    $bgGood = '#99ff99';
    
    $entry .= "<p><table border='1' width='100%'>\n";
    if(is_array($clash))
    {
        $clashPct = runClashStats($resolution, $clash['scoreAll'], $clash['scoreBlt40']);
        if($clashPct['pct_rank'] < 33)     $bg = $bgPoor;
        elseif($clashPct['pct_rank'] < 66) $bg = $bgFair;
        else                                $bg = $bgGood;
        if ($clash['scoreAll']<0)           $bg = $bgFair; // for catching a bug with probe giving clashscore = -1
        $entry .= "<tr><td rowspan='2' align='center'>All-Atom<br>Contacts</td>\n";
        $entry .= "<td>Clashscore, all atoms:</td><td colspan='2' bgcolor='$bg'>$clash[scoreAll]</td>\n";
        if ($clash['scoreAll']<0) {
          $entry .= "<td>unknown percentile<sup>*</sup> (N=$clashPct[n_samples], $clashPct[minresol]&Aring; - $clashPct[maxresol]&Aring;)</td></tr>\n";
        
          $entry .= "<tr><td colspan='3'>An error has occurred; clashscore should not be negative! Please report this bug.</td></tr>\n";
        } else {
          //$entry .= "<td>$clashPct[pct_rank]<sup>".ordinalSuffix($clashPct['pct_rank'])."</sup> percentile<sup>*</sup> (N=$clashPct[n_samples], $clashPct[minresol]&Aring; - $clashPct[maxresol]&Aring;)</td></tr>\n";
          if (($clashPct[minresol] == 0) && ($clashPct[maxresol] == 9999)) {
            $percentileOut = "all resolutions";
          } else {
            $diff = $resolution - $clashPct[minresol];
            //echo $diff." ".gettype($diff)." ";
            $diff2 = -($resolution - $clashPct[maxresol]);
            //echo $diff2." ".gettype($diff2)." ";
            //$test = ($diff2 > 0.245 && $diff2 < 0.255);
            //echo "diff2 = 0.25:".$test;
            if (($diff > 0.245)&&($diff2 > 0.245)&&($diff < 0.255)&&($diff2 < 0.255)) { 
              $percentileOut = "$resolution&Aring; &plusmn; $diff&Aring;"; 
            } else { 
              $percentileOut = "$clashPct[minresol]&Aring; - $clashPct[maxresol]&Aring;"; 
            }
          }
          $entry .= "<td>$clashPct[pct_rank]<sup>".ordinalSuffix($clashPct['pct_rank'])."</sup> percentile<sup>*</sup> (N=$clashPct[n_samples], $percentileOut)</td></tr>\n";
          $entry .= "<tr><td colspan='4'>Clashscore is the number of serious steric overlaps (&gt; 0.4 &Aring;) per 1000 atoms.</td></tr>\n";
        }
        //if($clashPct['pct_rank40'] <= 33)       $bg = $bgPoor;
        //elseif($clashPct['pct_rank40'] <= 66)   $bg = $bgFair;
        //else                                    $bg = $bgGood;
        //$entry .= "<tr><td>Clashscore, B&lt;40:</td><td bgcolor='$bg'>$clash[scoreBlt40]</td>\n";
        //$entry .= "<td>$clashPct[pct_rank40]<sup>".ordinalSuffix($clashPct['pct_rank40'])."</sup> percentile<sup>*</sup> (N=$clashPct[n_samples], $clashPct[minresol]&Aring; - $clashPct[maxresol]&Aring;)</td></tr>\n";
    }
    $proteinRows = 0;
    if(is_array($rama))    $proteinRows += 2;
    if(is_array($rota))    $proteinRows += 1;
    if(is_array($cbdev))   $proteinRows += 1;
    if(is_array($clash) && is_array($rota) && is_array($rama)) $proteinRows += 1;
    if(hasMoltype($bbonds, "protein")) $proteinRows += 1;
    if(hasMoltype($bangles, "protein")) $proteinRows += 1;
    if($proteinRows > 0)
    {
        $entry .= "<tr><td rowspan='$proteinRows' align='center'>Protein<br>Geometry</td>\n";
        $firstRow = true;
        if(is_array($rota))
        {
            $rotaOut = count(findRotaOutliers($rota));
            $rotaTot = count($rota);
            $rotaOutPct = sprintf("%.2f", 100.0 * $rotaOut / $rotaTot);
            
            if($firstRow) $firstRow = false;
            else $entry .= "<tr>";
            
            if($rotaOutPct+0 <= 1)      $bg = $bgGood;
            elseif($rotaOutPct+0 <= 5)  $bg = $bgFair;
            else                        $bg = $bgPoor;
            $entry .= "<td>Poor rotamers</td><td bgcolor='$bg'>$rotaOut</td><td bgcolor='$bg'>$rotaOutPct%</td>\n";
            $entry .= "<td>Goal: &lt;1%</td></tr>\n";
        }
        if(is_array($rama))
        {
            $ramaOut = count(findRamaOutliers($rama));
            foreach($rama as $r) { if($r['eval'] == "Favored") $ramaFav++; }
            $ramaTot = count($rama);
            $ramaOutPct = sprintf("%.2f", 100.0 * $ramaOut / $ramaTot);
            $ramaFavPct = sprintf("%.2f", 100.0 * $ramaFav / $ramaTot);
            
            if($firstRow) $firstRow = false;
            else $entry .= "<tr>";

            if($ramaOutPct+0 <= 0.05) $bg = $bgGood;
            elseif($ramaOut == 1 || $ramaOutPct+0 <= 0.5) $bg = $bgFair;
            else $bg = $bgPoor;
            $entry .= "<td>Ramachandran outliers</td><td bgcolor='$bg'>$ramaOut</td><td bgcolor='$bg'>$ramaOutPct%</td>\n";
            $entry .= "<td>Goal: &lt;0.05%</td></tr>\n";
            if($ramaFavPct+0 >= 98)     $bg = $bgGood;
            elseif($ramaFavPct+0 >= 95) $bg = $bgFair;
            else                        $bg = $bgPoor;
            $entry .= "<tr><td>Ramachandran favored</td><td bgcolor='$bg'>$ramaFav</td><td bgcolor='$bg'>$ramaFavPct%</td>\n";
            $entry .= "<td>Goal: &gt;98%</td></tr>\n";
        }
        if(is_array($clash) && is_array($rota) && is_array($rama))
        {
            if($firstRow) $firstRow = false;
            else $entry .= "<tr>";
            
            $axr = $resolution;                                     // Actual Xtalographic Resolution
            $mer = getEffectiveResolution($clash, $rota, $rama);    // MolProbity Effective Resolution
            $mer_pct = getEffectiveResolutionPercentile($mer, $axr);
            
            /*if(!$axr)*/                             $bg = $bgFair;  // unknown AXR
            //elseif($mer < $axr)                     $bg = $bgGood;  // below
            //elseif(abs(($mer-$axr)/$axr) <= 0.20)   $bg = $bgFair;  // within 20% of actual
            //else                                    $bg = $bgPoor;  // more than 20% above
            if($mer_pct['pct_rank'] < 33)             $bg = $bgPoor;  // switch to using percentiles for bg colors vbc 120629
            if($mer_pct['pct_rank'] >= 66)            $bg = $bgGood;  // to try to compensate for high-res structures looking worse than they are
            if (is_infinite($mer)) {
              $mer = -1;
              $bg = $bgFair;
            }
            
            $entry .= "<td>MolProbity score<sup><small>^</small></sup></td><td colspan='2' bgcolor='$bg'>";
            $entry .= sprintf('%.2f', $mer);
            //$entry .= "</td><td>Goal: &lt;$axr</td></tr>\n";
            if ($mer == -1) {
              $entry .= "</td><td>unknown percentile<sup>*</sup> (N=$mer_pct[n_samples], $mer_pct[minresol]&Aring; - $mer_pct[maxresol]&Aring;)</td></tr>\n";
            } else {
              if (($mer_pct[minresol] == 0) && ($mer_pct[maxresol] == 9999)) {
                $percentileOut = "all resolutions";
              } else {
                $diff = $resolution - $mer_pct[minresol];
                $diff2 = -($resolution - $mer_pct[maxresol]);
                if (($diff > 0.245)&&($diff2 > 0.245)&&($diff < 0.255)&&($diff2 < 0.255)) {
                  $percentileOut = "$resolution&Aring; &plusmn; $diff&Aring;";
                } else {
                  $percentileOut = "$mer_pct[minresol]&Aring; - $mer_pct[maxresol]&Aring;";
                }
              }
              $entry .= "</td><td>$mer_pct[pct_rank]<sup>".ordinalSuffix($mer_pct['pct_rank'])."</sup> percentile<sup>*</sup> (N=$mer_pct[n_samples], $percentileOut)</td></tr>\n";
          //$entry .= "</td><td>$mer_pct[pct_rank]<sup>".ordinalSuffix($mer_pct['pct_rank'])."</sup> percentile<sup>*</sup> (N=$mer_pct[n_samples], $mer_pct[minresol]&Aring; - $mer_pct[maxresol]&Aring;)</td></tr>\n";
            }
        }
        if(is_array($cbdev))
        {
            $cbOut = count(findCbetaOutliers($cbdev));
            $cbTot = count($cbdev);
            $cbOutPct = sprintf("%.2f", 100.0 * $cbOut / $cbTot);
            if($firstRow) $firstRow = false;
            else $entry .= "<tr>";
            
            if($cbOut == 0) $bg = $bgGood;
            else            $bg = $bgFair;
            if($cbOut/$cbTot > 0.05) $bg = $bgPoor;
            $entry .= "<td>C&beta; deviations &gt;0.25&Aring;</td><td bgcolor='$bg'>$cbOut</td><td bgcolor='$bg'>$cbOutPct%</td>\n";
            $entry .= "<td>Goal: 0</td></tr>\n";
        }
        if(hasMoltype($bbonds, "protein"))
        {
            if($firstRow) $firstRow = false;
            else $entry .= "<tr>";
            
            $total = 0;
            $outCount = 0;
            foreach($bbonds as $cnit => $item) {
                if($item['type'] == 'protein') {
                    if($item['isOutlier']) {
                      $outCount += $item['outCount'];
                    }
                    $total += $item['bondCount'];
                }
            }
            $geomOutPct = sprintf("%.2f", 100.0 * $outCount / $total);
            if ($outCount/$total < 0.002)    $bg = $bgFair;
            if ($outCount/$total == 0.0)    $bg = $bgGood;
            else                            $bg = $bgPoor;
            $entry .= "<td>Bad backbone bonds:</td><td bgcolor='$bg'>$outCount / $total</td><td bgcolor='$bg'>$geomOutPct%</td>\n<td>Goal: 0%</td></tr>\n";
        }
        if(hasMoltype($bangles, "protein"))
        {
            if($firstRow) $firstRow = false;
            else $entry .= "<tr>";
            
            $total = 0;
            $outCount = 0;
            foreach($bangles as $cnit => $item) {
                if($item['type'] == 'protein') {
                    if($item['isOutlier']) {
                        $outCount += $item['outCount'];
                    }
                    $total += $item['angCount'];
                }
            }
            $geomOutPct = sprintf("%.2f", 100.0 * $outCount / $total);
            if ($outCount/$total < 0.005)    $bg = $bgFair;
            if ($outCount/$total < 0.001)    $bg = $bgGood;
            else                            $bg = $bgPoor;
            $entry .= "<td>Bad backbone angles:</td><td bgcolor='$bg'>$outCount / $total</td><td bgcolor='$bg'>$geomOutPct%</td>\n<td>Goal: <0.1%</td></tr>\n";
        }
    }// end of protein-specific stats
    $nucleicRows = 0;
    if(is_array($pperp))   $nucleicRows += 1;
    if(is_array($suites))  $nucleicRows += 1;
    if(hasMoltype($bbonds, "rna")) $nucleicRows += 1;
    if(hasMoltype($bangles, "rna")) $nucleicRows += 1;
    if($nucleicRows > 0)
    {
        $entry .= "<tr><td rowspan='$nucleicRows' align='center'>Nucleic Acid<br>Geometry</td>\n";
        $firstRow = true;
        if(is_array($pperp))
        {
            if($firstRow) $firstRow = false;
            else $entry .= "<tr>";
            
            $pperpOut = count(findBasePhosPerpOutliers($pperp));
            $pperpTot = count($pperp);
            $pperpOutPct = sprintf("%.2f", 100.0 * $pperpOut / $pperpTot);
            
            $bg = $bgFair;            
            if($pperpOut == 0)             $bg = $bgGood;
            if($pperpOut/$pperpTot > 0.05) $bg = $bgPoor;

            $entry .= "<td>Probably wrong sugar puckers:</td><td bgcolor='$bg'>$pperpOut</td><td bgcolor='$bg'>$pperpOutPct%</td>\n";
            $entry .= "<td>Goal: 0</td></tr>\n";
        }
        if(is_array($suites))
        {
            if($firstRow) $firstRow = false;
            else $entry .= "<tr>";
            
            $suitesOut = count(findSuitenameOutliers($suites));
            $suitesTot = count($suites);
            $suitesOutPct = sprintf("%.2f", 100.0 * $suitesOut / $suitesTot);
            
            $bg = $bgFair;
            if($suitesOut/$suitesTot<= 0.05)    $bg = $bgGood;
            if($suitesOut/$suitesTot > 0.15)    $bg = $bgPoor;
            $entry .= "<td>Bad backbone conformations<sup><small>#</small></sup>:</td><td bgcolor='$bg'>$suitesOut</td><td bgcolor='$bg'>$suitesOutPct%</td>\n";
            $entry .= "<td>Goal: <= 5%</td></tr>\n";
        }
        if(hasMoltype($bbonds, "rna"))
        {
            if($firstRow) $firstRow = false;
            else $entry .= "<tr>";
            
            $total = 0;
            $outCount = 0;
            foreach($bbonds as $cnit => $item) {
                if($item['type'] == 'rna') {
                    if($item['isOutlier']) {
                        $outCount += $item['outCount'];
                    }
                    $total += $item['bondCount'];
                }
            }
            $geomOutPct = sprintf("%.2f", 100.0 * $outCount / $total);
            if ($outCount/$total < 0.002)    $bg = $bgFair;
            if ($outCount/$total < 0.000001)    $bg = $bgGood;
            else                            $bg = $bgPoor;
            $entry .= "<td>Bad bonds:</td><td bgcolor='$bg'>$outCount / $total</td><td bgcolor='$bg'>$geomOutPct%</td>\n<td>Goal: 0%</td></tr>\n";
        }
        if(hasMoltype($bangles, "rna"))
        {
            if($firstRow) $firstRow = false;
            else $entry .= "<tr>";
            
            $total = 0;
            $outCount = 0;
            foreach($bangles as $cnit => $item) {
                if($item['type'] == 'rna') {
                    if($item['isOutlier']) {
                        $outCount += $item['outCount'];
                    }
                    $total += $item['angCount'];
                }
            }
            $geomOutPct = sprintf("%.2f", 100.0 * $outCount / $total);
            if ($outCount/$total < 0.005)    $bg = $bgFair;
            if ($outCount/$total < 0.001)    $bg = $bgGood;
            else                            $bg = $bgPoor;
            $entry .= "<td>Bad angles:</td><td bgcolor='$bg'>$outCount / $total</td><td bgcolor='$bg'>$geomOutPct%</td>\n<td>Goal: <0.1%</td></tr>\n";
        }
    }
    $entry .= "</table>\n";
    $firstRow = true;
    if(is_array($rota) || is_array($rama) || is_array($cbdev) || is_array($bbonds) || is_array($bangles) || is_array($pperp) || is_array($suites)) {
        if($firstRow) $firstRow = false;
        else $entry .= "<br>";
        $entry .= "<small>In the two column results, the left column gives the raw count, right column gives the percentage.</small>\n";
    }
    if(is_array($clash)) {
        if($firstRow) $firstRow = false;
        else $entry .= "<br>";
        $entry .= "<small>* 100<sup>th</sup> percentile is the best among structures of comparable resolution; 0<sup>th</sup> percentile is the worst.  For clashscore the comparative set of structures was selected in 2004, for MolProbity score in 2006.</small>\n";
    }
    if(is_array($suites)) {
        if($firstRow) $firstRow = false;
        else $entry .= "<br>";
        $entry .= "<small><sup>#</sup> RNA backbone was recently shown to be rotameric.  Outliers are RNA suites that don't fall into recognized rotamers.</small>\n";
    }
    if(is_array($clash) && is_array($rota) && is_array($rama)) {
      if($firstRow) $firstRow = false;
      else $entry .= "<br>";
      //$entry .= "<small><sup>^</sup> MolProbity score is defined as the following: 0.42574*log(1+clashscore) + 0.32996*log(1+max(0,pctRotOut-1)) + 0.24979*log(1+max(0,100-pctRamaFavored-2)) + 0.5</small>\n";
      $entry .= "<small><sup>^</sup> MolProbity score combines the clashscore, rotamer, and Ramachandran evaluations into a single score, normalized to be on the same scale as X-ray resolution.</small>\n";
    }
    $entry .= "</p>\n"; // end of summary stats table
    return $entry;
}
#}}}########################################################################

#{{{ writeMulticritChart - compose table of quality metrics at once in 2-D
############################################################################
/**
* $outfile will be overwritten with a data structure suitable for
*   sortable_table.php; see that code for documentation.
* $snapfile will be overwritten with an HTML snapshot of the unsorted table;
*   if null, this will be skipped.
* $clash    is the data structure from loadClashlist()
* $rama     is the data structure from loadRamachandran()
* $rota     is the data structure from loadRotamer()
* $cbdev    is the data structure from loadCbetaDev()
* $pperp    is the data structure from loadBasePhosPerp()
* $suites   is the data structure from loadSuitenameReport()
* Any of them can be set to null if the data is unavailable.
*/
function writeMulticritChart($infile, $outfile, $snapfile, $clash, $rama, $rota, $cbdev, $pperp, $suites, $bbonds, $bangles, $outliersOnly = false, $doHtmlTable = true)
{
    $startTime = time();
    //{{{ Process validation data
    // Make sure all residues are represented, and in the right order.
    $res = listResidues($infile);
    $Bfact = listResidueBfactors($infile);
    $Bfact = $Bfact['res'];
    
    $orderIndex = 0; // used to maintain original PDB order on sorting.
    foreach($res as $k => $v)
    {
        $res[$k] = array('cnit' => $v, 'order' => $orderIndex++, 'resHiB' => $Bfact[$v]);
    }
    
    if(is_array($clash))
    {
        $with = $clash['clashes-with'];
        foreach($clash['clashes'] as $cnit => $worst)
        {
            $res[$cnit]['clash_val'] = $worst;
            $res[$cnit]['clash'] = "$worst&Aring;<br><small>".$with[$cnit]['srcatom']." with ".$with[$cnit]['dstcnit']." ".$with[$cnit]['dstatom']."</small>";
            $res[$cnit]['clash_isbad'] = true;
            $res[$cnit]['any_isbad'] = true;
            //echo "clash ".$cnit."\n";
        }
    }
    if(is_array($rama))
    {
        foreach($rama as $item)
        {
            $res[$item['resName']]['rama_val'] = $item['scorePct'];
            $phipsi = sprintf("%.1f,%.1f", $item['phi'], $item['psi']);
            if (isset($item['type'])) {
              if($item['eval'] == "OUTLIER")
              {
                $res[$item['resName']]['rama'] = "$item[eval] ($item[scorePct]%)<br><small>$item[type] / $phipsi</small>";
                $res[$item['resName']]['rama_isbad'] = true;
                $res[$item['resName']]['any_isbad'] = true;
                // ensures that all outliers sort to the top, b/c 0.2 is a Gly outlier but not a General outlier
                $res[$item['resName']]['rama_val'] -= 100.0;
              }
              else
              $res[$item['resName']]['rama'] = "$item[eval] ($item[scorePct]%)<br><small>$item[type] / $phipsi</small>";
            }
        }
    }
    if(is_array($rota))
    {
        foreach($rota as $item)
        {
            $res[$item['resName']]['rota_val'] = $item['scorePct'];
            if($item['scorePct'] <= 1.0)
            {
                $res[$item['resName']]['rota'] = "$item[scorePct]%<br><small>chi angles: ".formatChiAngles($item)."</small>";
                $res[$item['resName']]['rota_isbad'] = true;
                $res[$item['resName']]['any_isbad'] = true;
            }
            else
                $res[$item['resName']]['rota'] = "$item[scorePct]% (<i>$item[rotamer]</i>)<br><small>chi angles: ".formatChiAngles($item)."</small>";
        }
    }
    if(is_array($cbdev))
    {
        foreach($cbdev as $item)
        {
            $res[$item['resName']]['cbdev_val'] = $item['dev'];
            if($item['dev'] >= 0.25)
            {
                $res[$item['resName']]['cbdev'] = "$item[dev]&Aring;";
                $res[$item['resName']]['cbdev_isbad'] = true;
                $res[$item['resName']]['any_isbad'] = true;
            }
            else {
                if($res[$item['resName']]['cbdev'] == null) //for fixing a bug where an ok alt conf dev would get reported instead of the bad dev.
                    $res[$item['resName']]['cbdev'] = "$item[dev]&Aring;";
            }
        }
    }
    if(is_array($pperp))
    {
        foreach($pperp as $item)
        {
            if($item['outlier'])
            {
                //echo "pperp ".$item['resName']."\n";
                $reasons = array();
                if    ($item['deltaOut'] && $item['epsilonOut'] && $item['3Pdist'] < 3.0) $reasons[] = "&delta & &epsilon outlier <br> (base-p distance indicates 2'-endo";
                elseif($item['deltaOut'] && $item['epsilonOut'] && $item['3Pdist'] >= 3.0) $reasons[] = "&delta & &epsilon outlier <br> (base-p distance indicates 3'-endo";
                elseif($item['deltaOut'] && $item['3Pdist'] < 3.0)   $reasons[] = "&delta outlier <br> (base-p distance indicates 2'-endo";
                elseif($item['deltaOut'] && $item['3Pdist'] >= 3.0) $reasons[] = "&delta outlier <br> (base-p distance indicates 3'-endo";
                elseif($item['epsilonOut'] && $item['3Pdist'] < 3.0)   $reasons[] = "&epsilon outlier <br> (base-p distance indicates 2'-endo";
                elseif($item['epsilonOut'] && $item['3Pdist'] >= 3.0) $reasons[] = "&epsilon outlier <br> (base-p distance indicates 3'-endo";
                $res[$item['resName']]['pperp_val'] = 1; // no way to quantify this
                $res[$item['resName']]['pperp'] = "suspect sugar pucker  -  ".implode(", ", $reasons).")";
                $res[$item['resName']]['pperp_isbad'] = true;
                $res[$item['resName']]['any_isbad'] = true;
            }
        }
    }
    if(is_array($suites))
    {
        foreach($suites as $cnit => $item)
        {
            $res[$cnit]['suites_val'] = $item['suiteness'];
            $bin = "&delta&delta&gamma $item[bin]";
            if($bin == '&delta&delta&gamma trig')
            {
                $bin = "&delta&delta&gamma none (triaged  $item[triage]  )";
                $res[$cnit]['suites_val'] = -1; // sorts to very top
            }
            elseif($bin == '&delta&delta&gamma inc ')
            {
                $bin = '&delta&delta&gamma none (incomplete)';
                $res[$cnit]['suites_val'] = 0.0001; // sorts just below all outliers
            }
            elseif(preg_match('/7D dist/', $item['triage']))
            {
                $bin = "$bin ( $item[triage] )";
            }
            
            if($item['isOutlier'])
            {
                $res[$cnit]['suites'] = "OUTLIER<br><small>$bin</small>";
                $res[$cnit]['suites_isbad'] = true;
                $res[$cnit]['any_isbad'] = true;
            }
            elseif($item['bin'] == 'inc ')
                $res[$cnit]['suites'] = "conformer: $item[conformer]<br><small>$bin</small>";
            else
                $res[$cnit]['suites'] = "conformer: $item[conformer]<br><small>$bin, suiteness = $item[suiteness]</small>";
        }
    }
    if(is_array($bbonds)) {
        foreach($bbonds as $cnit => $item) {
            if ($item['isOutlier']) {
                $res[$cnit]['bbonds_val'] = abs($item['sigma']);
                $res[$cnit]['bbonds'] = "$item[count] OUTLIER(S)<br><small>worst is $item[measure]: $item[sigma] &sigma</small>";
                $res[$cnit]['bbonds_isbad'] = true;
                $res[$cnit]['any_isbad'] = true;
            }
        }
    }
    if(is_array($bangles)) {
        foreach($bangles as $cnit => $item) {
            if ($item['isOutlier']) {
                $res[$cnit]['bangles_val'] = abs($item['sigma']);
                $res[$cnit]['bangles'] = "$item[count] OUTLIER(S)<br><small>worst is $item[measure]: $item[sigma] &sigma</small>";
                $res[$cnit]['bangles_isbad'] = true;
                $res[$cnit]['any_isbad'] = true;
            }
        }
    }
    //}}} Process validation data
    //echo "Processing validation data took ".(time() - $startTime)." seconds\n";
    
    // Set up output data structure
    $table = array('prequel' => '', 'headers' => array(), 'rows' => array(), 'footers' => array(), 'sequel' => '');
    
    $startTime = time();
    //{{{ Table prequel and headers
    // Do summary chart
    $pdbstats = pdbstat($infile);
    $table['prequel'] = makeSummaryStatsTable($pdbstats['resolution'], $clash, $rama, $rota, $cbdev, $pperp, $suites, $bbonds, $bangles);
    
    if ($doHtmlTable) {
      $header1 = array();
      $header1[] = array('html' => "<b>#</b>",                                            'sort' => 1);
      $header1[] = array('html' => "<b>Res</b>",                                          'sort' => 1);
      $header1[] = array('html' => "<b>High B</b>",                                       'sort' => -1);
      if(is_array($clash))  $header1[] = array('html' => "<b>Clash &gt; 0.4&Aring;</b>",  'sort' => -1);
      if(is_array($rama))   $header1[] = array('html' => "<b>Ramachandran</b>",           'sort' => 1);
      if(is_array($rota))   $header1[] = array('html' => "<b>Rotamer</b>",                'sort' => 1);
      if(is_array($cbdev))  $header1[] = array('html' => "<b>C&beta; deviation</b>",      'sort' => -1);
      if(is_array($pperp))  $header1[] = array('html' => "<b>Base-P perp. dist.</b>",     'sort' => -1);
      if(is_array($suites)) $header1[] = array('html' => "<b>RNA suite conf.</b>",        'sort' => 1);
      if(is_array($bbonds)) $header1[] = array('html' => "<b>Bond lengths.</b>",      'sort' => -1);
      if(is_array($bangles)) $header1[] = array('html' => "<b>Bond angles.</b>",      'sort' => -1);
      
      $header2 = array();
      $header2[] = array('html' => "");
      $header2[] = array('html' => "");
      $header2[] = array('html' => sprintf("Avg: %.2f", array_sum($Bfact)/count($Bfact)));
      if(is_array($clash))  $header2[] = array('html' => "Clashscore: $clash[scoreAll]");
      if(is_array($rama))   $header2[] = array('html' => "Outliers: ".count(findRamaOutliers($rama))." of ".count($rama));
      if(is_array($rota))   $header2[] = array('html' => "Poor rotamers: ".count(findRotaOutliers($rota))." of ".count($rota));
      if(is_array($cbdev))  $header2[] = array('html' => "Outliers: ".count(findCbetaOutliers($cbdev))." of ".count($cbdev));
      if(is_array($pperp))  $header2[] = array('html' => "Outliers: ".count(findBasePhosPerpOutliers($pperp))." of ".count($pperp));
      if(is_array($suites)) $header2[] = array('html' => "Outliers: ".count(findSuitenameOutliers($suites))." of ".count($suites));
      if(is_array($bbonds)) $header2[] = array('html' => "Outliers: ".count(findGeomOutliers($bbonds))." of ".count($bbonds));
      if(is_array($bangles)) $header2[] = array('html' => "Outliers: ".count(findGeomOutliers($bangles))." of ".count($bangles));
      
      $table['headers'] = array($header1, $header2);
    }
    //}}} Table prequel and headers
    //echo "Table prequel and headers took ".(time() - $startTime)." seconds\n";
    
    $startTime = time();
    //{{{ Table body
    if ($doHtmlTable) {
      $rows = array();
      foreach($res as $cnit => $eval)
      {
        if($outliersOnly && !$eval['any_isbad']) continue;
        $cni = substr($cnit, 0, 6);
        $type = substr($cnit, 6, 3);
        $row = array();
        //$row[] = array('html' => $cnit,             'sort_val' => $eval['order']+0);
        $row[] = array('html' => $cni,              'sort_val' => $eval['order']+0);
        $row[] = array('html' => $type,             'sort_val' => $type);
        $row[] = array('html' => $eval['resHiB'],   'sort_val' => $eval['resHiB']+0);
        foreach(array('clash', 'rama', 'rota', 'cbdev', 'pperp', 'suites', 'bbonds', 'bangles') as $type)
        {
          if(is_array($$type))
          {
            $cell = array();
            if(isset($eval[$type]))             $cell['html'] = $eval[$type];
            else                                $cell['html'] = "-";
            if(isset($eval[$type.'_isbad']))    $cell['color'] = '#ff6699';
            if(isset($eval[$type.'_val']))      $cell['sort_val'] = $eval[$type.'_val']+0;
            $row[] = $cell;
          }
        }
        /*
        if(is_array($clash))
        $row[] = array('html' => (isset($eval['clash']) ? $eval['clash'] : "-"),        'sort_val' => $eval['clash_val']+0);
        if(is_array($rama))
        $row[] = array('html' => (isset($eval['rama']) ? $eval['rama'] : "-"),          'sort_val' => $eval['rama_val']+0);
        if(is_array($rota))
        $row[] = array('html' => (isset($eval['rota']) ? $eval['rota'] : "-"),          'sort_val' => $eval['rota_val']+0);
        if(is_array($cbdev))
        $row[] = array('html' => (isset($eval['cbdev']) ? $eval['cbdev'] : "-"),        'sort_val' => $eval['cbdev_val']+0);
        if(is_array($pperp))
        $row[] = array('html' => (isset($eval['pperp']) ? $eval['pperp'] : "-"),        'sort_val' => $eval['pperp_val']+0);
        */
        $rows[] = $row;
      }
      $table['rows'] = $rows;
    }
    //}}} Table body
    //echo "Table body took ".(time() - $startTime)." seconds\n";
    
    $startTime = time();
    $out = fopen($outfile, 'wb');
    fwrite($out, mpSerialize($table));
    fclose($out);
    //echo "Serializing table took ".(time() - $startTime)." seconds\n";
    
    // serialize() and unserialize() screw up floating point numbers sometimes.
    // Not only is there a change in precision, but sometimes numbers become INF
    // in some versions of PHP 4, like those shipped with Mac OS X.
    //
    #$tmpfile = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA.'/table_dump';
    #$out = fopen($tmpfile.'1', 'wb');
    #fwrite($out, var_export($table, true));
    #fclose($out);
    #$time = time();
    #    $out = fopen($tmpfile.'2', 'wb');
    #    fwrite($out, var_export(unserialize(serialize($table)), true));
    #    fclose($out);
    #echo "Dump+load time for serialize: ".(time() - $time)." seconds\n";
    #$time = time();
    #    $out = fopen($tmpfile.'3', 'wb');
    #    fwrite($out, var_export(eval("return ".var_export($table, true).";"), true));
    #    fclose($out);
    #echo "Dump+load time for var_export: ".(time() - $time)." seconds\n";
    # WAY TOO SLOW (all in PHP, no C code):
    #$time = time();
    #    $json = new Services_JSON();
    #    $out = fopen($tmpfile.'4', 'wb');
    #    fwrite($out, var_export($json->decode($json->encode($table)), true));
    #    fclose($out);
    #echo "Dump+load time for JSON: ".(time() - $time)." seconds\n";
    
    if($snapfile)
    {
        $startTime = time();
        $out = fopen($snapfile, 'wb');
        //fwrite($out, formatSortableTable($table, 'DUMMY_URL'));
        fwrite($out, formatSortableTableJS($table));
        fclose($out);
        //echo "Formatting sortable table took ".(time() - $startTime)." seconds\n";
    }
}
#}}}########################################################################

#{{{ makeCootMulticritChart - Scheme script for Coot's interesting-things-gui
############################################################################
/**
* $outfile will be overwritten with a Scheme script for loading in Coot.
* $clash    is the data structure from loadClashlist()
* $rama     is the data structure from loadRamachandran()
* $rota     is the data structure from loadRotamer()
* $cbdev    is the data structure from loadCbetaDev()
* $pperp    is the data structure from loadBasePhosPerp()
* Any of them can be set to null if the data is unavailable.
*/
function makeCootMulticritChart($infile, $outfile, $clash, $rama, $rota, $cbdev, $pperp)
{
    $out = fopen($outfile, 'wb');
    fwrite($out, ";\n; Multicriterion chart for ".basename($infile).", generated by MolProbity.\n");
    fwrite($out, "; Open this in Coot using Calculate | Run Script...\n;\n");
    fwrite($out, "(interesting-things-gui \"MolProbity Multi-Chart\"\n (list\n");
    $resCenters = computeResCenters($infile);
    
    if(is_array($clash)) foreach($clash['clashes'] as $cnit => $worst)
    {
        $ctr = $resCenters[$cnit];
        fwrite($out, "  (list \"Clash at $cnit ($worst A)\" $ctr[x] $ctr[y] $ctr[z])\n");
    }
        
    if(is_array($rama)) foreach($rama as $item)
    {
        if($item['eval'] == "OUTLIER")
        {
            $cnit = $item['resName'];
            $ctr = $resCenters[$cnit];
            fwrite($out, "  (list \"Ramachandran outlier $cnit ($item[type] $item[scorePct]%)\" $ctr[x] $ctr[y] $ctr[z])\n");
        }
    }

    if(is_array($rota)) foreach($rota as $item)
    {
        if($item['scorePct'] <= 1.0)
        {
            $cnit = $item['resName'];
            $ctr = $resCenters[$cnit];
            fwrite($out, "  (list \"Bad rotamer $cnit ($item[scorePct]%)\" $ctr[x] $ctr[y] $ctr[z])\n");
        }
    }
    
    if(is_array($cbdev)) foreach($cbdev as $item)
    {
        if($item['dev'] >= 0.25)
        {
            $cnit = $item['resName'];
            $ctr = $resCenters[$cnit];
            fwrite($out, "  (list \"C-beta deviation $cnit ($item[dev] A)\" $ctr[x] $ctr[y] $ctr[z])\n");
        }
    }
    
    if(is_array($pperp)) foreach($pperp as $item)
    {
        if($item['outlier'])
        {
            $cnit = $item['resName'];
            $ctr = $resCenters[$cnit];
            fwrite($out, "  (list \"Base-phos. dist. $cnit (wrong pucker?)\" $ctr[x] $ctr[y] $ctr[z])\n");
        }
    }
    
    fwrite($out, " )\n)\n");
    fclose($out);
}
#}}}########################################################################

#{{{ makeCootClusteredChart - Scheme script for Coot's fascinating-things-gui
############################################################################
/**
* $outfile will be overwritten with a Scheme script for loading in Coot.
* $clash    is the data structure from loadClashlist()
* $rama     is the data structure from loadRamachandran()
* $rota     is the data structure from loadRotamer()
* $cbdev    is the data structure from loadCbetaDev()
* $pperp    is the data structure from loadBasePhosPerp()
* Any of them can be set to null if the data is unavailable.
*/
function makeCootClusteredChart($infile, $outfile, $outfile_py, $clash, $rama, $rota, $cbdev, $pperp)
{
    //$startTime1 = time();
    //{{{ 0. A lovely Scheme script written for us by Paul Emsley
    $schemeScript = <<<HEREDOC
; -*-scheme-*-

;;(molprobity-fascinating-clusters-things-gui
;;   dialog-name
;;   sorting-options
;;   list-of-clusters)
;; 
;; where a cluster is:
;;    (list 
;;     cluster-name-string
;;     cluster-center-go-button-label-string
;;     ccgb-x ccgb-y ccgb-z
;;     ; a list of specific items:
;;     (list 
;;         (list specific-button-label-string button-red button-green button-blue
;;               specific-x specific-y specific-z))))
;;
;; 
;;(molprobity-fascinating-clusters-things-gui
;; gui-name-string
;; (list
;;   (list "Active Site" (list 0 1 2 3 4))
;;   (list "Worst First" (list 3 4 2 1 0)))
;; ; now a list of clusters:
;; (list
;;    (list cluster-name-string
;;       cluster-center-go-button-label-string
;;       ccgb-x ccgb-y ccgb-z
;;       ; now a list of specific items
;;       (list
;;         (list specific-button-label-string button-red button-green button-blue
;;               specific-x specific-y specific-z)
;;         (list specific-button-label-string button-red button-green button-blue
;;               specific-x specific-y specific-z)))

;;    (list cluster-name-string
;;       cluster-center-go-button-label-string
;;       ccgb-x ccgb-y ccgb-z
;;       ; now a list of specific items
;;       (list
;;         (list specific-button-label-string button-red button-green button-blue
;;               specific-x specific-y specific-z)
;;         (list specific-button-label-string button-red button-green button-blue
;;               specific-x specific-y specific-z)))))
;; 
(define (molprobity-fascinating-clusters-things-gui window-name sorting-options cluster-list)

  (define ncluster-max 75)

  ;; utility function
  (define (add-feature-buttons feature-list cluster-vbox)
    (let ((frame (gtk-frame-new "Cluster Features"))
	  (vbox (gtk-vbox-new #f 0)))
      (gtk-box-pack-start cluster-vbox frame #f #f 2)
      (gtk-container-add frame vbox)

      ;; add buttons to vbox for each feature
      ;; 
      (map (lambda (feature)
	    ; (format #t "feature: ~s~%" feature)
	     (let ((button (gtk-button-new-with-label (car feature))))
	       (gtk-signal-connect button "clicked"
				   (lambda ()
				     (set-rotation-centre 
				      (list-ref feature 4)
				      (list-ref feature 5)
				      (list-ref feature 6))))
	       (gtk-box-pack-start vbox button #f #f 1)))
	     feature-list)))

  ;; main body
  (let* ((window (gtk-window-new 'toplevel))
	 (scrolled-win (gtk-scrolled-window-new))
	 (outside-vbox (gtk-vbox-new #f 2))
	 (inside-vbox (gtk-vbox-new #f 0)))

    (format #t "Maxiumum number of clusters displayed: ~s~%" ncluster-max)
    
    (gtk-window-set-default-size window  300 200)
    (gtk-window-set-title window window-name)
    (gtk-container-border-width inside-vbox 2)
    (gtk-container-add window outside-vbox)
    (gtk-box-pack-start outside-vbox scrolled-win #t #t 0) ; expand fill padding
    (gtk-scrolled-window-add-with-viewport scrolled-win inside-vbox)
    (gtk-scrolled-window-set-policy scrolled-win 'automatic 'always)
    
    (let loop ((cluster-list cluster-list)
	       (count 0))
      
      (cond
       ((null? cluster-list) 'done)
       ((= ncluster-max count) 'done)
       (else 

	(let ((cluster-info (car cluster-list)))

	   (let* ((frame (gtk-frame-new #f))
		  (vbox (gtk-vbox-new #f 2)))

	     (gtk-container-border-width frame 6)
	     (gtk-container-add frame vbox)
	     (gtk-box-pack-start inside-vbox frame #f #f 10)
	     (let ((go-to-cluster-button (gtk-button-new-with-label
					  (car cluster-info))))
	       (gtk-signal-connect go-to-cluster-button "clicked"
				   (lambda ()
				     (set-rotation-centre
				      (list-ref cluster-info 1)
				      (list-ref cluster-info 2)
				      (list-ref cluster-info 3))))
	       (gtk-box-pack-start vbox go-to-cluster-button #f #f 2)
	       
	       ;; now we have a list of individual features:
	       (let ((features (list-ref cluster-info 4)))
		 (if (> (length features) 0)
		     (add-feature-buttons features vbox)))
	       (loop (cdr cluster-list) (+ count 1))))))))
		   

    (gtk-container-border-width outside-vbox 2)
    (let ((ok-button (gtk-button-new-with-label "  Close  ")))
      (gtk-box-pack-end outside-vbox ok-button #f #f 0)
      (gtk-signal-connect ok-button "clicked"
			  (lambda args
			    (gtk-widget-destroy window))))
    (gtk-widget-show-all window)))



;; 
;;(molprobity-fascinating-clusters-things-gui
;; "Testing the GUI" 
;; (list 
;;  (list "Active Site" (list 0 1 2 3 4))
;;  (list "Worst First" (list 3 4 1 2 0)))
;; (list 
;;  (list "The first cluster"
;;	11 12 15
;;	(list 
;;	 (list "A bad thing" 0.4 0.6 0.7 10 13 16)	
;;	 (list "Another bad thing" 0.4 0.6 0.7 12 15 16)))
;;  (list "Another cluster of baddies"
;;	-11 12 15
;;	(list
;;	 (list "A quite bad thing" 0.4 0.6 0.7 -10 -13 16)	
;;	 (list "A not so bad thing" 0.4 0.6 0.7 -12 -15 16)))
;;  (list "A third cluster of baddies"
;;	11 12 -15
;;	(list
;;	 (list "A quite bad rotamer" 0.4 0.6 0.7 10 13 -16)	
;;	 (list "A hydrogen clash" 0.4 0.6 0.7 12 15 -16)
;;	 (list "A not so bad H-H clash" 0.4 0.6 0.7 12 15 -16)))))

HEREDOC;

  $schemeScript_py=<<<HEREDOC
def molprobity_fascinating_clusters_things_gui(window_name, sorting_option, cluster_list):

    ncluster_max = 75

    # a callback function
    def callback_recentre(widget, x, y, z):
        set_rotation_centre(x, y, z)

    # utility function
    def add_feature_buttons(feature_list, cluster_vbox):
        frame = gtk.Frame("Cluster Features")
        vbox = gtk.VBox(False, 0)
        cluster_vbox.pack_start(frame, False, False, 2)
        frame.add(vbox)

        # add buttons to vbox for each feature
        #
        for feature in feature_list:
            # print "feature: ", feature
            button = gtk.Button(feature[0])
            button.connect("clicked",
                           callback_recentre,
                           feature[4],
                           feature[5],
                           feature[6])
            vbox.pack_start(button, False, False, 1)

    # main body
    window = gtk.Window(gtk.WINDOW_TOPLEVEL)
    scrolled_win = gtk.ScrolledWindow()
    outside_vbox = gtk.VBox(False, 2)
    inside_vbox = gtk.VBox(False, 0)

    print "Maximum number of clusters displayed:  ", ncluster_max

    window.set_default_size(300, 200)
    window.set_title(window_name)
    inside_vbox.set_border_width(2)
    window.add(outside_vbox)
    outside_vbox.pack_start(scrolled_win, True, True, 0) # expand fill padding
    scrolled_win.add_with_viewport(inside_vbox)
    scrolled_win.set_policy(gtk.POLICY_AUTOMATIC, gtk.POLICY_ALWAYS)

    count = 0

    for cluster_info in cluster_list:

        if (count == ncluster_max):
            break
        else:
            frame = gtk.Frame()
            vbox = gtk.VBox(False, 2)

            frame.set_border_width(6)
            frame.add(vbox)
            inside_vbox.pack_start(frame, False, False, 10)
            go_to_cluster_button = gtk.Button(cluster_info[0])
            go_to_cluster_button.connect("clicked",
                                         callback_recentre,
                                         cluster_info[1],
                                         cluster_info[2],
                                         cluster_info[3])
            vbox.pack_start(go_to_cluster_button, False, False, 2)

            # now we have a list of individual features:
            features = cluster_info[4]
            if (len(features) > 0):
                add_feature_buttons(features, vbox)

    outside_vbox.set_border_width(2)
    ok_button = gtk.Button("  Close  ")
    outside_vbox.pack_end(ok_button, False, False, 0)
    ok_button.connect("clicked", lambda x: window.destroy())
    window.show_all()
HEREDOC;
    //}}} 0. A lovely Scheme script written for us by Paul Emsley
    
    //{{{ 1. For each outlier, create an array(cnit, description, r, g, b, x, y, z)
    //$res_xyz = computeResCenters($infile, true);
    $res_xyz = computeResCenters($infile);
    $self_bads = array();
    
    if(is_array($clash)) foreach($clash['clashes'] as $cnit => $worst)
    {
        $ctr = $res_xyz[$cnit];
        $self_bads[] = array($cnit, "Clash at $cnit ($worst A)", 1, 0, 0.5, $ctr['x'], $ctr['y'], $ctr['z']);
    }
        
    if(is_array($rama)) foreach($rama as $item)
    {
        if($item['eval'] == "OUTLIER")
        {
            $cnit = $item['resName'];
            $ctr = $res_xyz[$cnit];
            $self_bads[] = array($cnit, "Ramachandran outlier $cnit ($item[type] $item[scorePct]%)", 0, 1, 0, $ctr['x'], $ctr['y'], $ctr['z']);
        }
    }

    if(is_array($rota)) foreach($rota as $item)
    {
        if($item['scorePct'] <= 1.0)
        {
            $cnit = $item['resName'];
            $ctr = $res_xyz[$cnit];
            $self_bads[] = array($cnit, "Bad rotamer $cnit ($item[scorePct]%)", 1, 0.7, 0, $ctr['x'], $ctr['y'], $ctr['z']);
        }
    }
    
    if(is_array($cbdev)) foreach($cbdev as $item)
    {
        if($item['dev'] >= 0.25)
        {
            $cnit = $item['resName'];
            $ctr = $res_xyz[$cnit];
            $self_bads[] = array($cnit, "C-beta deviation $cnit ($item[dev] A)", 0.5, 0, 1, $ctr['x'], $ctr['y'], $ctr['z']);
        }
    }
    
    if(is_array($pperp)) foreach($pperp as $item)
    {
        if($item['outlier'])
        {
            $cnit = $item['resName'];
            $ctr = $res_xyz[$cnit];
            $reasons = array();
            if($item['deltaOut'])   $reasons[] = "base-phosphate distance";
            if($item['epsilonOut']) $reasons[] = "bad epsilon angle";
            $self_bads[] = array($cnit, "Wrong sugar pucker $cnit (".implode(', ', $reasons).")", 0.5, 0, 1, $ctr['x'], $ctr['y'], $ctr['z']);
        }
    }
    //}}} 1. For each outlier, create an array(cnit, description, r, g, b, x, y, z)
    
    //{{{ 2. Cluster the outliers, somehow
    //echo "self_bads has ".count($self_bads)." elements\n";
    $range = 12; // a fairly arbitrary value, in Angstroms.
    $range2 = $range * $range;
    $worst_res = array();
    // cnit => array( bad1, bad2, ... )
    $local_bads = array();
    $startTime = time();
    echo "starting cootclusteredchart\n";
    foreach($res_xyz as $cnit => $xyz)
    {
        #$local_bads[$cnit] = array();
        foreach($self_bads as $idx => $a_bad)
        {
            if(preg_match('/'.$a_bad[0].'/',$cnit)) //count bads for each $res
            {
                    $res_bads[$cnit]++;
            }
        }
        foreach($self_bads as $idx => $a_bad)
        {
            $cnit2 = $a_bad[0];
            $dx = $xyz['x'] - $a_bad[5];
            $dy = $xyz['y'] - $a_bad[6];
            $dz = $xyz['z'] - $a_bad[7];
            if($dx*$dx + $dy*$dy + $dz*$dz <= $range2 && $res_bads[$cnit]!=0)
            {
                if(preg_match('/(HOH|DOD|H20|D20|WAT|SOL|TIP|TP3|MTO|HOD|DOH)/',$cnit))
                {
                     if(preg_match('/(HOH|DOD|H20|D20|WAT|SOL|TIP|TP3|MTO|HOD|DOH)/',$a_bad[0]))
                        $local_bads[$cnit][$idx] = $a_bad;
                }
                else $local_bads[$cnit][$idx] = $a_bad;
            }
        }
    }
    echo "first foreach loop took ".(time() - $startTime)." seconds\nstarting whiletrue loop\ncycles:";
    while(true)
    {
        // Get worst residue from list and its count of bads
        //$startTime = time();
        uasort($local_bads, 'makeCootClusteredChart_cmp'); // put worst residue last
        //echo "Iteration number ".$cycles."\n";
        //foreach($self_bads as $key => $value)
        //{
        //        echo $self_bads[$key][0]."\n";
        //}
        #foreach($local_bads as $key => $value)
        #{
        #        echo $key." ".count($local_bads[$key])."\n";
        #}
        //echo "size of self_bads = ".count($self_bads)."\n";
        #var_export($local_bads); echo "\n==========\n";
        end($local_bads); // go to last element
        list($worst_cnit, $worst_bads) = each($local_bads); // get last element
        $bad_count = count($worst_bads);
        // Only singletons left (for efficiency)
        // Also ensures that singletons are listed under their "owner"
        //if($bad_count <= 1)
        //{
        //    foreach($self_bads as $idx => $a_bad)
        //        $worst_res[$a_bad[0]][$idx] = $a_bad;
        //    break;
        //}
        // else ...
        #var_export($local_bads);
        #echo "\nRemoving $worst_cnit with $bad_count bads...\n==========\n";
        $worst_res[$worst_cnit] = $worst_bads; // record it as the worst one this pass
        // Discard all bads that went to making the worst, the worst;
        // then re-run the algorithm to find the next worst, until no bads left.
        foreach($res_xyz as $cnit2 => $xyz) {
            foreach($worst_bads as $idx => $a_bad) {
                unset($local_bads[$cnit2][$idx]);
                //assure that once used, a residue can't be a new center
                foreach($self_bads as $idx2 => $a_bad2) {
                        unset($local_bads[$a_bad[0]][$idx2]);
                }
                unset($self_bads[$idx]);
            }
        }
        if(count($self_bads) == 0) break;
        $cycles++;
        echo $cycles." ";
        //echo "end of while loop took ".(time() - $startTime)." seconds\n";
        #if($cycles > 100) break;
    }
    //echo "number of cycles: ".$cycles."\n";
    #var_export($worst_res); echo "\n==========\n";
    //}}}

    $out = fopen($outfile, 'wb');
    $out_py = fopen($outfile_py, 'wb');
    
    //scheme file
    fwrite($out, ";\n; Multicriterion chart for ".basename($infile).", generated by MolProbity.\n");
    fwrite($out, "; Open this in Coot using Calculate | Run Script...\n;\n");
    fwrite($out, "\n\n".$schemeScript."\n\n");
    fwrite($out, "(molprobity-fascinating-clusters-things-gui\n \"MolProbity Multi-Chart\"\n (list\n");
    // this is where we write possible sort orders
    //fwrite($out, "  (list \"Worst First\" (list 0 1 2 3 4 5))\n");
    fwrite($out, " )\n (list\n");
    
    //python file
    fwrite($out_py, "#\n# Multicriterion chart for ".basename($infile).", generated by MolProbity.\n");
    fwrite($out_py, "# Open this in Coot using Calculate | Run Script...\n#\n");
    fwrite($out_py, "\n\n".$schemeScript_py."\n\n");
    fwrite($out_py, "molprobity_fascinating_clusters_things_gui(\n    \"MolProbity Multi-Chart\",\n    [],\n    [");
    // This is where we write clusters of outliers
    $outlier_ctr=0;
    $loop_ctr=0;
    foreach($worst_res as $cnit => $bads)
    {
        $max=0;
        foreach($bads as $b)
        {
             //identify which residue has the most outliers in this group, make header name
             if ($res_bads[$b[0]] > $max && !preg_match('/(HOH|DOD|H20|D20|WAT|SOL|TIP|TP3|MTO|HOD|DOH)/', $b[0])){
                $max=$res_bads[$b[0]];
                $max_header=$b[0];
             }
        }
        $xyz = $res_xyz[$cnit];
        if(count($bads) > 1) // a "real" cluster
        {
            fwrite($out, "  (list\n   \"problems near $max_header\"\n   $xyz[x] $xyz[y] $xyz[z]\n   (list\n");
            fwrite($out_py, "[\"problems near $max_header\",\n      $xyz[x], $xyz[y], $xyz[z],\n      [\n");
            foreach($bads as $b)
            {    
                fwrite($out, "    (list \"$b[1]\" $b[2] $b[3] $b[4] $b[5] $b[6] $b[7])\n");
                fwrite($out_py, "       [\"$b[1]\", $b[2], $b[3], $b[4], $b[5], $b[6], $b[7]]");
                #if($loop_ctr < count($worst_res)-1){
                    fwrite($out_py, ",\n");
                #}
                #else{
                #    fwrite($out_py, "\n");
                #}
                $outlier_ctr++;
            }
            fwrite($out, "   )\n  )\n");
            fwrite($out_py, "      ]\n     ],\n     ");
        }
        else // a singleton
        {
            $b = reset($bads);
            fwrite($out, "  (list\n   \"$b[1]\"\n   $xyz[x] $xyz[y] $xyz[z]\n   (list\n");
            fwrite($out, "   )\n  )\n");
            fwrite($out_py, "[\"$b[1]\", $xyz[x], $xyz[y], $xyz[z], []]");
            if($loop_ctr < count($worst_res)-1){
                    fwrite($out_py, ",\n     ");
            }
            else{
                fwrite($out_py, "\n    ");
            }
            
            $outlier_ctr++;
        }
        $loop_ctr++;
    }
    fwrite($out, " )\n)\n");
    fwrite($out_py, "])");
    fclose($out);
    fclose($out_py);
    //echo "Making coot clustered chart took ".(time() - $startTime1)." seconds\n";
    //echo "printed out ".$outlier_ctr." elements\n";
}

function makeCootClusteredChart_cmp($a, $b)
{ return count($a) - count($b); }
#}}}########################################################################

#{{{ formatChiAngles - utility for printing 1 - 4 chi angles in a list
############################################################################
/**
* item      an array with keys 'chi1' - 'chi4'
*/
function formatChiAngles($item)
{
    $ret = $item['chi1'];
    if($item['chi2'] !== '') $ret .= ',' . $item['chi2'];
    if($item['chi3'] !== '') $ret .= ',' . $item['chi3'];
    if($item['chi4'] !== '') $ret .= ',' . $item['chi4'];
    return $ret;
}
#}}}########################################################################

#{{{ makeRemark999 - format MolProbity summary for PDB inclusion DEPRECIATED
############################################################################
/**
* $clash    is the data structure from loadClashlist()
* $rama     is the data structure from loadRamachandran()
* $rota     is the data structure from loadRotamer()
*
* Returns a properly-formatted REMARK 999 string.
*/
function makeRemark999($clash, $rama, $rota)
{
    $s = 'REMARK 999                                                                      
REMARK 999 MOLPROBITY STRUCTURE VALIDATION                                      
REMARK 999  PROGRAMS    : MOLPROBITY  (KING, REDUCE, AND PROBE)                 
REMARK 999  AUTHORS     : I.W.DAVIS,V.B.CHEN,                                   
REMARK 999              : R.M.IMMORMINO,J.J.HEADD,W.B.ARENDALL,J.M.WORD         
REMARK 999  URL         : HTTP://KINEMAGE.BIOCHEM.DUKE.EDU/MOLPROBITY/          
REMARK 999  AUTHORS     : I.W.DAVIS,A.LEAVER-FAY,V.B.CHEN,J.N.BLOCK,            
REMARK 999              : G.J.KAPRAL,X.WANG,L.W.MURRAY,W.B.ARENDALL,            
REMARK 999              : J.SNOEYINK,J.S.RICHARDSON,D.C.RICHARDSON              
REMARK 999  REFERENCE   : MOLPROBITY: ALL-ATOM CONTACTS AND STRUCTURE           
REMARK 999              : VALIDATION FOR PROTEINS AND NUCLEIC ACIDS             
REMARK 999              : NUCLEIC ACIDS RESEARCH. 2007;35:W375-83.              
REMARK 999  MOLPROBITY OUTPUT SCORES:                                           
';
    // WARNING!
    // This code will perform correctly ONLY on PHP 4.3.7 and later.
    // Prior to that %6.2f meant up 6 characters before the decimal, and 2 after.
    // Afterwards, it means 6 characters total with 2 after the decimal,
    // and thus a maximum of 3 before (3 + 1 + 2 == 6).
    // The new meaning is consistent with the way printf() works in other languages (C, Perl, Python).
    // See PHP bugs #28633 and #29286 for more details.
    
    if(is_array($clash))
    {
        //$s .= str_pad(sprintf('REMARK 999  ALL-ATOM CLASHSCORE     : %6.2f  (%.2f B<40)', $clash['scoreAll'], $clash['scoreBlt40']), 80) . "\n";
        $s .= str_pad(sprintf('REMARK 999  ALL-ATOM CLASHSCORE     : %6.2f', $clash['scoreAll']), 80) . "\n";
    }
    if(is_array($rota))
    {
        $rotaOut = count(findRotaOutliers($rota));
        $rotaTot = count($rota);
        $rotaOutPct = (100.0 * $rotaOut / $rotaTot);
        $s .= str_pad(sprintf('REMARK 999  BAD ROTAMERS            : %5.1f%% %4d/%-5d  (TARGET  0-1%%)', $rotaOutPct, $rotaOut, $rotaTot), 80) . "\n";
    }
    if(is_array($rama))
    {
        $ramaOut = count(findRamaOutliers($rama));
        foreach($rama as $r) { if($r['eval'] == "Favored") $ramaFav++; }
        $ramaTot = count($rama);
        $ramaOutPct = (100.0 * $ramaOut / $ramaTot);
        $ramaFavPct = (100.0 * $ramaFav / $ramaTot);
        $s .= str_pad(sprintf('REMARK 999  RAMACHANDRAN OUTLIERS   : %5.1f%% %4d/%-5d  (TARGET  0.2%%)', $ramaOutPct, $ramaOut, $ramaTot), 80) . "\n";
        $s .= str_pad(sprintf('REMARK 999  RAMACHANDRAN FAVORED    : %5.1f%% %4d/%-5d  (TARGET 98.0%%)', $ramaFavPct, $ramaFav, $ramaTot), 80) . "\n";
    }
    
    return $s;         
}
#}}}########################################################################

#{{{ makeRemark40 - format MolProbity summary for PDB inclusion
############################################################################
/**
* $clash    is the data structure from loadClashlist()
* $rama     is the data structure from loadRamachandran()
* $rota     is the data structure from loadRotamer()
*
* Returns a properly-formatted REMARK  40 string.
*/
function makeRemark40($clash, $rama, $rota)
{
    $s = 'REMARK  40                                                                      
REMARK  40 MOLPROBITY STRUCTURE VALIDATION                                      
REMARK  40  PROGRAMS    : MOLPROBITY  (KING, REDUCE, AND PROBE)                 
REMARK  40  AUTHORS     : I.W.DAVIS,V.B.CHEN,                                   
REMARK  40              : R.M.IMMORMINO,J.J.HEADD,W.B.ARENDALL,J.M.WORD         
REMARK  40  URL         : HTTP://KINEMAGE.BIOCHEM.DUKE.EDU/MOLPROBITY/          
REMARK  40  AUTHORS     : I.W.DAVIS,A.LEAVER-FAY,V.B.CHEN,J.N.BLOCK,            
REMARK  40              : G.J.KAPRAL,X.WANG,L.W.MURRAY,W.B.ARENDALL,            
REMARK  40              : J.SNOEYINK,J.S.RICHARDSON,D.C.RICHARDSON              
REMARK  40  REFERENCE   : MOLPROBITY: ALL-ATOM CONTACTS AND STRUCTURE           
REMARK  40              : VALIDATION FOR PROTEINS AND NUCLEIC ACIDS             
REMARK  40              : NUCLEIC ACIDS RESEARCH. 2007;35:W375-83.              
REMARK  40  MOLPROBITY OUTPUT SCORES:                                           
';
    // WARNING!
    // This code will perform correctly ONLY on PHP 4.3.7 and later.
    // Prior to that %6.2f meant up 6 characters before the decimal, and 2 after.
    // Afterwards, it means 6 characters total with 2 after the decimal,
    // and thus a maximum of 3 before (3 + 1 + 2 == 6).
    // The new meaning is consistent with the way printf() works in other languages (C, Perl, Python).
    // See PHP bugs #28633 and #29286 for more details.
    
    if(is_array($clash))
    {
        //$s .= str_pad(sprintf('REMARK  40  ALL-ATOM CLASHSCORE     : %6.2f  (%.2f B<40)', $clash['scoreAll'], $clash['scoreBlt40']), 80) . "\n";
        $s .= str_pad(sprintf('REMARK  40  ALL-ATOM CLASHSCORE     : %6.2f', $clash['scoreAll']), 80) . "\n";
    }
    if(is_array($rota))
    {
        $rotaOut = count(findRotaOutliers($rota));
        $rotaTot = count($rota);
        $rotaOutPct = (100.0 * $rotaOut / $rotaTot);
        $s .= str_pad(sprintf('REMARK  40  BAD ROTAMERS            : %5.1f%% %4d/%-5d  (TARGET  0-1%%)', $rotaOutPct, $rotaOut, $rotaTot), 80) . "\n";
    }
    if(is_array($rama))
    {
        $ramaOut = count(findRamaOutliers($rama));
        foreach($rama as $r) { if($r['eval'] == "Favored") $ramaFav++; }
        $ramaTot = count($rama);
        $ramaOutPct = (100.0 * $ramaOut / $ramaTot);
        $ramaFavPct = (100.0 * $ramaFav / $ramaTot);
        $s .= str_pad(sprintf('REMARK  40  RAMACHANDRAN OUTLIERS   : %5.1f%% %4d/%-5d  (TARGET  0.2%%)', $ramaOutPct, $ramaOut, $ramaTot), 80) . "\n";
        $s .= str_pad(sprintf('REMARK  40  RAMACHANDRAN FAVORED    : %5.1f%% %4d/%-5d  (TARGET 98.0%%)', $ramaFavPct, $ramaFav, $ramaTot), 80) . "\n";
    } 
    
    return $s;         
}
#}}}########################################################################



#{{{ writeMultimodelChart - kinemage format multichart for NMR structures
############################################################################
/**
* $infiles      array of single model PDB files to process
* $outfile      will be overwritten with a kinemage
*/
function writeMultimodelChart($infiles, $outfile)
{
    $infiles    = array_values($infiles); // re-indexes from 0 ... n-1
    $clashes    = array();
    $clashOuts  = array();
    $clashCount = array();
    $rotas      = array();
    $rotaOuts   = array();
    $rotaCount  = array();
    $ramas      = array();
    $ramaOuts   = array();
    $ramaCount  = array();
    $tmpfile    = mpTempfile();
    
    for($i = 0; $i < count($infiles); $i++)
    {
        runClashlist($infiles[$i], $tmpfile);
        $clashes[$i] = loadClashlist($tmpfile);
        $clashOuts[$i] = findClashOutliers($clashes[$i]);
        foreach($clashOuts[$i] as $cnit => $junk) $clashCount[$cnit] += 1;
        runRotamer($infiles[$i], $tmpfile);
        $rotas[$i] = loadRotamer($tmpfile);
        $rotaOuts[$i] = findRotaOutliers($rotas[$i]);
        foreach($rotaOuts[$i] as $cnit => $junk) $rotaCount[$cnit] += 1;
        runRamachandran($infiles[$i], $tmpfile);
        $ramas[$i] = loadRamachandran($tmpfile);
        $ramaOuts[$i] = findRamaOutliers($ramas[$i]);
        foreach($ramaOuts[$i] as $cnit => $junk) $ramaCount[$cnit] += 1;
    }
    
    $allRes = array_values(listResidues($infiles[0])); // for now, assume all files have same res
    $out = fopen($outfile, 'wb');
    fwrite($out, "@kinemage 1\n");
    fwrite($out, "@flatland\n");

    fwrite($out, "@group {models} animate collapsable\n");
    fwrite($out, "@labellist {res names}\n");
    for($j = 0; $j < count($allRes); $j++)
        fwrite($out, "{".$allRes[$j]."} 0 -$j 0\n");
    fwrite($out, "@balllist {grid} radius= 0.02 nohighlight color= gray\n");
    for($j = 0; $j < count($allRes); $j++)
        for($i = 0; $i < count($infiles); $i++)
            fwrite($out, "{} ".(-$i-1)." -$j 0\n");

    for($i = 0; $i < count($infiles); $i++)
    {
        fwrite($out, "@subgroup {".basename($infiles[$i])."} dominant\n");
        
        fwrite($out, "@ringlist {clashes} master= {clashes} radius= 0.3 width= 2 color= hotpink\n");
        for($j = 0; $j < count($allRes); $j++)
            if($clashOuts[$i][ $allRes[$j] ]) fwrite($out, "{".$allRes[$j]."} ".(-$i-1)." -$j 0\n");
        
        fwrite($out, "@ringlist {rotamers} master= {rotamers} radius= 0.1 width= 2 color= gold\n");
        for($j = 0; $j < count($allRes); $j++)
            if($rotaOuts[$i][ $allRes[$j] ]) fwrite($out, "{".$allRes[$j]."} ".(-$i-1)." -$j 0\n");
        
        fwrite($out, "@ringlist {Ramachandran} master= {Ramachandran} radius= 0.2 width= 2 color= green\n");
        for($j = 0; $j < count($allRes); $j++)
            if($ramaOuts[$i][ $allRes[$j] ]) fwrite($out, "{".$allRes[$j]."} ".(-$i-1)." -$j 0\n");
    }

    
    fwrite($out, "@group {criteria (rings)} animate collapsable\n");
    fwrite($out, "@labellist {res names}\n");
    for($j = 0; $j < count($allRes); $j++)
        fwrite($out, "{".$allRes[$j]."} 0 -$j ".(-0.1*$i)."\n");

    for($i = 0; $i < count($infiles); $i++)
    {
        fwrite($out, "@subgroup {".basename($infiles[$i])."} dominant\n");
        $xpos = 0;
        
        fwrite($out, "@ringlist {clashes} radius= ".(0.50 * ($i+1)/count($infiles))." width= 1 color= hotpink\n");
        $xpos -= 1.5;
        for($j = 0; $j < count($allRes); $j++)
            if($clashOuts[$i][ $allRes[$j] ]) fwrite($out, "{".$allRes[$j]."} $xpos -$j ".(-0.1*$i)."\n");
        
        fwrite($out, "@ringlist {rotamers} radius= ".(0.50 * ($i+1)/count($infiles))." width= 1 color= gold\n");
        $xpos -= 1.5;
        for($j = 0; $j < count($allRes); $j++)
            if($rotaOuts[$i][ $allRes[$j] ]) fwrite($out, "{".$allRes[$j]."} $xpos -$j ".(-0.1*$i)."\n");
        
        fwrite($out, "@ringlist {Ramachandran} radius= ".(0.50 * ($i+1)/count($infiles))." width= 1 color= green\n");
        $xpos -= 1.5;
        for($j = 0; $j < count($allRes); $j++)
            if($ramaOuts[$i][ $allRes[$j] ]) fwrite($out, "{".$allRes[$j]."} $xpos -$j ".(-0.1*$i)."\n");
    }
    
    
    fwrite($out, "@group {criteria (lines)} animate collapsable\n");
    fwrite($out, "@labellist {res names}\n");
    for($j = 0; $j < count($allRes); $j++)
        fwrite($out, "{".$allRes[$j]."} 0 -$j ".(-0.1*$i)."\n");
    $xpos = 0;
    
    fwrite($out, "@vectorlist {clashes} color= hotpink\n");
    $xpos -= 1;
    writeMultimodelChart_bars($out, $allRes, $clashCount, count($infiles), $xpos);

    fwrite($out, "@vectorlist {rotamers} color= gold\n");
    $xpos -= 1;
    writeMultimodelChart_bars($out, $allRes, $rotaCount, count($infiles), $xpos);

    fwrite($out, "@vectorlist {Ramachandran} color= green\n");
    $xpos -= 1;
    writeMultimodelChart_bars($out, $allRes, $ramaCount, count($infiles), $xpos);
    
    
    fwrite($out, "@group {criteria (worms)} animate collapsable\n");
    fwrite($out, "@labellist {res names}\n");
    for($j = 0; $j < count($allRes); $j++)
        fwrite($out, "{".$allRes[$j]."} 0 -$j ".(-0.1*$i)."\n");
    $xpos = 0;
    
    fwrite($out, "@trianglelist {clashes} color= hotpink\n");
    $xpos -= 1;
    writeMultimodelChart_boxes($out, $allRes, $clashCount, count($infiles), $xpos);

    fwrite($out, "@trianglelist {rotamers} color= gold\n");
    $xpos -= 1;
    writeMultimodelChart_boxes($out, $allRes, $rotaCount, count($infiles), $xpos);

    fwrite($out, "@trianglelist {Ramachandran} color= green\n");
    $xpos -= 1;
    writeMultimodelChart_boxes($out, $allRes, $ramaCount, count($infiles), $xpos);

    fclose($out);
}

function writeMultimodelChart_boxes($out, $allRes, $outlierCounts, $numModels, $xpos)
{
    for($j = 0; $j < count($allRes); $j++)
    {
        $cnit = $allRes[$j];
        if(!$outlierCounts[$cnit]) continue;
        $x1 = $xpos - 0.5*($outlierCounts[$cnit] / $numModels);
        $x2 = $xpos + 0.5*($outlierCounts[$cnit] / $numModels);
        $y1 = -($j - 0.5);
        $y2 = -($j + 0.5);
        fwrite($out, "{{$cnit}}X $x1 $y1 0 {\"} $x2 $y1 0\n");
        fwrite($out, "{{$cnit}}P $x1 $y2 0 {\"} $x2 $y2 0\n");
    }
    fwrite($out, "@vectorlist {dividers} color= gray width= 1 nobutton master= {dividers}\n");
    fwrite($out, "{}P $xpos 0 0 {} $xpos ".(1-count($allRes))." -0.1\n");
}

function writeMultimodelChart_bars($out, $allRes, $outlierCounts, $numModels, $xpos)
{
    for($j = 0; $j < count($allRes); $j++)
    {
        $cnit = $allRes[$j];
        $x1 = $xpos - 0.5*($outlierCounts[$cnit] / $numModels);
        $x2 = $xpos + 0.5*($outlierCounts[$cnit] / $numModels);
        $y1 = -($j - 0.5);
        $y2 = -($j + 0.5);
        fwrite($out, "{{$cnit}}P $x1 $y1 0 {\"} $x2 $y1 0\n");
        fwrite($out, "{{$cnit}}P $x1 $y2 0 {\"} $x2 $y2 0\n");
    }
    $x1 = $xpos - 0.5;
    $x2 = $xpos + 0.5;
    fwrite($out, "@vectorlist {dividers} color= gray width= 1 nobutton master= {dividers}\n");
    fwrite($out, "{}P $x1 0 0 {} $x1 ".(1-count($allRes))." -0.1\n");
    fwrite($out, "{}P $x2 0 0 {} $x2 ".(1-count($allRes))." -0.1\n");
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
