<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Provides kinemage-creation functions for visualizing various
    aspects of the analysis.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/pdbstat.php');
require_once(MP_BASE_DIR.'/lib/analyze.php');

#{{{ makeRamachandranKin - creates a kinemage-format Ramachandran plot
############################################################################
function makeRamachandranKin($infile, $outfile)
{
    exec("java -cp ".MP_BASE_DIR."/lib/hless.jar hless.Ramachandran -nosummary $infile > $outfile");
}
#}}}########################################################################

#{{{ makeRamachandranPDF - creates a multi-page PDF-format Ramachandran plot
############################################################################
function makeRamachandranPDF($infile, $outfile)
{
    exec("java -cp ".MP_BASE_DIR."/lib/chiropraxis.jar chiropraxis.rotarama.Ramalyze -pdf $infile $outfile");
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

#{{{ makeFlipkin - runs Flipkin to generate a summary of the Reduce -build changes
############################################################################
/**
*/
function makeFlipkin($inpath, $outpathAsnGln, $outpathHis)
{
    // $_SESSION[hetdict] is used to set REDUCE_HET_DICT environment variable,
    // so it doesn't need to appear on the command line here.
    exec("flipkin -limit" . MP_REDUCE_LIMIT . " $inpath > $outpathAsnGln");
    exec("flipkin -limit" . MP_REDUCE_LIMIT . " -h $inpath > $outpathHis");
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



#{{{ makeMulticritKin - display all quality metrics at once in 3-D
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
    // Jane still likes this best.
    exec("java -cp ".MP_BASE_DIR."/lib/hless.jar hless.Ramachandran -nosummary -outliers $color < $infile >> $outfile");
    
    // This uses Prekin, but just produces chunks of mainchain. Hard to see.
    /*if(!$rama)
    {
        $tmp = tempnam(MP_BASE_DIR."/tmp", "tmp_rama_");
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
        $tmp = tempnam(MP_BASE_DIR."/tmp", "tmp_rota_");
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
    
    foreach($sc as $scRange)
    {
        //echo("prekin -quiet -append -nogroup -listmaster 'rotamer outliers' -bval -scope $scRange -show 'sc($color)' $infile >> $outfile\n");
        exec("prekin -quiet -append -nogroup -listmaster 'rotamer outliers' -bval -scope $scRange -show 'sc($color)' $infile >> $outfile");
    }
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
    
    exec("prekin -quiet -append -nogroup -pperpoutliers $infile >> $outfile");
}
#}}}########################################################################

#{{{ makeProbeDots - appends mc and sc Probe dots
############################################################################
/**
* Documentation for this function.
*/
function makeProbeDots($infile, $outfile, $hbDots = false, $vdwDots = false)
{
    $options = "";
    if(!$hbDots)    $options .= " -nohbout";
    if(!$vdwDots)   $options .= " -novdwout";
    
    exec("probe $options -4H -quiet -noticks -nogroup -mc -self 'alta' $infile >> $outfile");
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

    $tmp = tempnam(MP_BASE_DIR."/tmp", "tmp_kin_");
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
function makeSummaryStatsTable($resolution, $clash, $rama, $rota, $cbdev, $pperp)
{
    $entry = "";
    $bgPoor = '#ff9999';
    $bgFair = '#ffff99';
    $bgGood = '#99ff99';
    
    $entry .= "<p><table border='1' width='100%'>\n";
    if(is_array($clash))
    {
        $clashPct = runClashStats($resolution, $clash['scoreAll'], $clash['scoreBlt40']);
        if($clashPct['pct_rank'] <= 33)     $bg = $bgPoor;
        elseif($clashPct['pct_rank'] <= 66) $bg = $bgFair;
        else                                $bg = $bgGood;
        $entry .= "<tr><td rowspan='2' align='center'>All-Atom<br>Contacts</td>\n";
        $entry .= "<td>Clashscore, all atoms:</td><td bgcolor='$bg'>$clash[scoreAll]</td>\n";
        $entry .= "<td>$clashPct[pct_rank]<sup>".ordinalSuffix($clashPct['pct_rank'])."</sup> percentile<sup>*</sup> (N=$clashPct[n_samples])</td></tr>\n";
        
        if($clashPct['pct_rank40'] <= 33)       $bg = $bgPoor;
        elseif($clashPct['pct_rank40'] <= 66)   $bg = $bgFair;
        else                                    $bg = $bgGood;
        $entry .= "<tr><td>Clashscore, B&lt;40:</td><td bgcolor='$bg'>$clash[scoreBlt40]</td>\n";
        $entry .= "<td>$clashPct[pct_rank40]<sup>".ordinalSuffix($clashPct['pct_rank40'])."</sup> percentile<sup>*</sup> (N=$clashPct[n_samples])</td></tr>\n";
    }
    $proteinRows = 0;
    if(is_array($rama))    $proteinRows += 2;
    if(is_array($rota))    $proteinRows += 1;
    if(is_array($cbdev))   $proteinRows += 1;
    if($proteinRows > 0)
    {
        $entry .= "<tr><td rowspan='$proteinRows' align='center'>Protein<br>Geometry</td>\n";
        $firstRow = true;
        if(is_array($rama))
        {
            $ramaOut = count(findRamaOutliers($rama));
            foreach($rama as $r) { if($r['eval'] == "Favored") $ramaFav++; }
            $ramaTot = count($rama);
            $ramaOutPct = sprintf("%.2f", 100.0 * $ramaOut / $ramaTot);
            $ramaFavPct = sprintf("%.2f", 100.0 * $ramaFav / $ramaTot);
            
            if($firstRow) $firstRow = false;
            else $entry .= "<tr>";

            if($ramaOut == 0) $bg = $bgGood;
            elseif($ramaOut == 1 || $ramaOutPct+0 <= 0.5) $bg = $bgFair;
            else $bg = $bgPoor;
            $entry .= "<td>Ramachandran outliers</td><td bgcolor='$bg'>$ramaOutPct%</td>\n";
            $entry .= "<td>Goal: &lt;0.05%</td></tr>\n";
            if($ramaFavPct+0 >= 98)     $bg = $bgGood;
            elseif($ramaFavPct+0 >= 95) $bg = $bgFair;
            else                        $bg = $bgPoor;
            $entry .= "<tr><td>Ramachandran favored</td><td bgcolor='$bg'>$ramaFavPct%</td>\n";
            $entry .= "<td>Goal: &gt;98%</td></tr>\n";
        }
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
            $entry .= "<td>Rotamer outliers</td><td bgcolor='$bg'>$rotaOutPct%</td>\n";
            $entry .= "<td>Goal: &lt;1%</td></tr>\n";
        }
        if(is_array($cbdev))
        {
            $cbOut = count(findCbetaOutliers($cbdev));
            if($firstRow) $firstRow = false;
            else $entry .= "<tr>";
            
            if($cbOut == 0) $bg = $bgGood;
            else            $bg = $bgFair;
            $entry .= "<td>C&beta; deviations &gt;0.25&Aring;</td><td bgcolor='$bg'>$cbOut</td>\n";
            $entry .= "<td>Goal: 0</td></tr>\n";
        }
    }// end of protein-specific stats
    if(is_array($pperp))
    {
        $pperpOut = count(findBasePhosPerpOutliers($pperp));
        $pperpTot = count($pperp);
        if($pperpOut == 0)  $bg = $bgGood;
        else                $bg = $bgFair;
        $entry .= "<tr><td rowspan='1' align='center'>Nucleic Acid<br>Geometry</td>\n";
        $entry .= "<td>Base-P dist./pucker disagreement:</td><td bgcolor='$bg'>$pperpOut</td>\n";
        $entry .= "<td>Goal: 0</td></tr>\n";
        $entry .= "</tr>\n";
    }
    $entry .= "</table>\n";
    if(is_array($clash)) $entry .= "<small>* 100<sup>th</sup> percentile is the best among structures between $clashPct[minresol]&Aring; and $clashPct[maxresol]&Aring;; 0<sup>th</sup> percentile is the worst.</small>\n";
    $entry .= "</p>\n"; // end of summary stats table
    return $entry;
}
#}}}########################################################################

#{{{ makeMulticritChart - display all quality metrics at once in 2-D
############################################################################
/**
* $outfile will be overwritten with an HTML table.
* $clash    is the data structure from loadClashlist()
* $rama     is the data structure from loadRamachandran()
* $rota     is the data structure from loadRotamer()
* $cbdev    is the data structure from loadCbetaDev()
* $pperp    is the data structure from loadBasePhosPerp()
* $sortBy   can be 'natural', 'bad', 'clash', 'rama', 'rota', 'cbdev', 'pperp'
* Any of them can be set to null if the data is unavailable.
*/
function makeMulticritChart($infile, $outfile, $clash, $rama, $rota, $cbdev, $pperp, $sortBy = 'natural')
{
    // Make sure all residues are represented, and in the right order.
    $res = listResidues($infile);
    $Bfact = listResidueBfactors($infile);
    $Bfact = $Bfact['res'];
    
    $orderIndex = 0; // used to maintain original PDB order on sorting.
    foreach($res as $k => $v)
    {
        $res[$k] = array('cnit' => $v, 'order' => $orderIndex++);
    }
    
    if(is_array($clash))
    {
        foreach($clash['clashes'] as $cnit => $worst)
        {
            $res[$cnit]['clash_val'] = $worst;
            $res[$cnit]['clash'] = "<td bgcolor='#ff6699'>$worst&Aring;</td>";
            $res[$cnit]['isbad'] = true;
        }
    }
    if(is_array($rama))
    {
        foreach($rama as $item)
        {
            $res[$item['resName']]['rama_val'] = $item['scorePct'];
            $phipsi = sprintf("%.1f,%.1f", $item['phi'], $item['psi']);
            if($item['eval'] == "OUTLIER")
            {
                $res[$item['resName']]['rama'] = "<td bgcolor='#ff6699'>$item[eval] ($item[scorePct]%)<br><small>$item[type] - $phipsi</small></td>";
                $res[$item['resName']]['isbad'] = true;
            }
            else
                $res[$item['resName']]['rama'] = "<td>$item[eval] ($item[scorePct]%)<br><small>$item[type] / $phipsi</small></td>";
        }
    }
    if(is_array($rota))
    {
        foreach($rota as $item)
        {
            $res[$item['resName']]['rota_val'] = $item['scorePct'];
            if($item['scorePct'] <= 1.0)
            {
                $res[$item['resName']]['rota'] = "<td bgcolor='#ff6699'>$item[scorePct]%<br><small>angles: $item[chi1],$item[chi2],$item[chi3],$item[chi4]</small></td>";
                $res[$item['resName']]['isbad'] = true;
            }
            else
                $res[$item['resName']]['rota'] = "<td>$item[scorePct]%<br><small>angles: $item[chi1],$item[chi2],$item[chi3],$item[chi4]</small></td>";
        }
    }
    if(is_array($cbdev))
    {
        foreach($cbdev as $item)
        {
            $res[$item['resName']]['cbdev_val'] = $item['dev'];
            if($item['dev'] >= 0.25)
            {
                $res[$item['resName']]['cbdev'] = "<td bgcolor='#ff6699'>$item[dev]A</small></td>";
                $res[$item['resName']]['isbad'] = true;
            }
            else
                $res[$item['resName']]['cbdev'] = "<td>$item[dev]&Aring;</small></td>";
        }
    }
    if(is_array($pperp))
    {
        foreach($pperp as $item)
        {
            if($item['outlier'])
            {
                $res[$item['resName']]['pperp_val'] = 1; // no way to quantify this
                $res[$item['resName']]['pperp'] = "<td bgcolor='#ff6699'>wrong pucker?</td>";
                $res[$item['resName']]['isbad'] = true;
            }
        }
    }
    
    // Sort into user-defined order
    if($sortBy == 'natural')        {} // don't change order
    elseif($sortBy == 'bad')        uasort($res, 'mcSortBad');
    elseif($sortBy == 'clash')      uasort($res, 'mcSortClash');
    elseif($sortBy == 'rama')       uasort($res, 'mcSortRama');
    elseif($sortBy == 'rota')       uasort($res, 'mcSortRota');
    elseif($sortBy == 'cbdev')      uasort($res, 'mcSortCbDev');
    elseif($sortBy == 'pperp')      uasort($res, 'mcSortPPerp');
    
    // Do summary chart
    $out = fopen($outfile, 'wb');
    $pdbstats = pdbstat($infile);
    fwrite($out, makeSummaryStatsTable($pdbstats['resolution'], $clash, $rama, $rota, $cbdev, $pperp));
    
    fwrite($out, "<table width='100%' cellspacing='1' border='0'>\n");
    fwrite($out, "<tr align='center' bgcolor='".MP_TABLE_HIGHLIGHT."'>\n");
    fwrite($out, "<td><b>Res</b></td>\n");
    fwrite($out, "<td><b>High B</b></td>\n");
    if(is_array($clash))  fwrite($out, "<td><b>Clash &gt; 0.4&Aring;</b></td>\n");
    if(is_array($rama))   fwrite($out, "<td><b>Ramachandran</b></td>\n");
    if(is_array($rota))   fwrite($out, "<td><b>Rotamer</b></td>\n");
    if(is_array($cbdev))  fwrite($out, "<td><b>C&beta; deviation</b></td>\n");
    if(is_array($pperp))  fwrite($out, "<td><b>Base-P perp. dist.</b></td>\n");
    fwrite($out, "</tr>\n");
    
    fwrite($out, "<tr align='center' bgcolor='".MP_TABLE_HIGHLIGHT."'>\n");
    fwrite($out, "<td></td>\n");
    fwrite($out, sprintf("<td>Avg: %.2f</td>\n", array_sum($Bfact)/count($Bfact)));
    if(is_array($clash))  fwrite($out, "<td>Clashscore: $clash[scoreAll]</td>\n");
    if(is_array($rama))   fwrite($out, "<td>Outliers: ".count(findRamaOutliers($rama))." of ".count($rama)."</td>\n");
    if(is_array($rota))   fwrite($out, "<td>Outliers: ".count(findRotaOutliers($rota))." of ".count($rota)."</td>\n");
    if(is_array($cbdev))  fwrite($out, "<td>Outliers: ".count(findCbetaOutliers($cbdev))." of ".count($cbdev)."</td>\n");
    if(is_array($pperp))  fwrite($out, "<td>Outliers: ".count(findBasePhosPerpOutliers($pperp))." of ".count($pperp)."</td>\n");
    fwrite($out, "</tr>\n");

    $color = MP_TABLE_ALT1;
    foreach($res as $cnit => $eval)
    {
        fwrite($out, "<tr align='center' bgcolor='$color'>");
        fwrite($out, "<td align='left'>$cnit</td>");
        fwrite($out, "<td>".$Bfact[$cnit]."</td>");
        if(is_array($clash))
        {
            if(isset($eval['clash']))   fwrite($out, $eval['clash']);
            else                        fwrite($out, "<td>-</td>");
        }
        if(is_array($rama))
        {
            if(isset($eval['rama']))    fwrite($out, $eval['rama']);
            else                        fwrite($out, "<td>-</td>");
        }
        if(is_array($rota))
        {
            if(isset($eval['rota']))    fwrite($out, $eval['rota']);
            else                        fwrite($out, "<td>-</td>");
        }
        if(is_array($cbdev))
        {
            if(isset($eval['cbdev']))   fwrite($out, $eval['cbdev']);
            else                        fwrite($out, "<td>-</td>");
        }
        if(is_array($pperp))
        {
            if(isset($eval['pperp']))   fwrite($out, $eval['pperp']);
            else                        fwrite($out, "<td>-</td>");
        }
        fwrite($out, "</tr>\n");
        $color == MP_TABLE_ALT1 ? $color = MP_TABLE_ALT2 : $color = MP_TABLE_ALT1;
    }
    fwrite($out, "</table>\n");
    fclose($out);
}
#}}}########################################################################

#{{{ mcSortXXX - sort functions for multicriterion chart
############################################################################
// We need this b/c sort is not guaranteed to preserve starting order
// for elements that compare as equal.
function mcSortNatural($a, $b)
{
    // Alphabetical sort moves _ chain IDs before A, which can sometimes
    // end up putting hets at the beginning of the file. D'oh!
    //if($a['cnit'] < $b['cnit'])     return -1;
    //elseif($a['cnit'] > $b['cnit']) return 1;
    //else                            return 0;
    return ($a['order'] - $b['order']); // original order from the PDB file
}

function mcSortBad($a, $b)
{
    if($a['isbad'])
    {
        if($b['isbad']) return mcSortNatural($a, $b);
        else            return -1;
    }
    elseif($b['isbad']) return 1;
    else                return mcSortNatural($a, $b);
}

function mcSortClash($a, $b)
{
    if($a['clash_val'] < $b['clash_val'])       return 1;
    elseif($a['clash_val'] > $b['clash_val'])   return -1;
    else                                        return mcSortNatural($a, $b);
}

function mcSortRama($a, $b)
{
    // unset values compare as zero and sort to top otherwise
    if(!isset($a['rama_val']))
    {
        if(!isset($b['rama_val']))          return mcSortNatural($a, $b);
        else                                return 1;
    }
    elseif(!isset($b['rama_val']))          return -1;
    elseif($a['rama_val'] < $b['rama_val']) return -1;
    elseif($a['rama_val'] > $b['rama_val']) return 1;
    else                                    return mcSortNatural($a, $b);
}

function mcSortRota($a, $b)
{
    // unset values compare as zero and sort to top otherwise
    if(!isset($a['rota_val']))
    {
        if(!isset($b['rota_val']))          return mcSortNatural($a, $b);
        else                                return 1;
    }
    elseif(!isset($b['rota_val']))          return -1;
    elseif($a['rota_val'] < $b['rota_val']) return -1;
    elseif($a['rota_val'] > $b['rota_val']) return 1;
    else                                    return mcSortNatural($a, $b);
}

function mcSortCbDev($a, $b)
{
    if($a['cbdev_val'] < $b['cbdev_val'])       return 1;
    elseif($a['cbdev_val'] > $b['cbdev_val'])   return -1;
    else                                        return mcSortNatural($a, $b);
}

function mcSortPPerp($a, $b)
{
    if($a['pperp_val'] < $b['pperp_val'])       return 1;
    elseif($a['pperp_val'] > $b['pperp_val'])   return -1;
    else                                        return mcSortNatural($a, $b);
}
#}}}########################################################################

#{{{ makeRemark42 - format MolProbity summary for PDB inclusion
############################################################################
/**
* $clash    is the data structure from loadClashlist()
* $rama     is the data structure from loadRamachandran()
* $rota     is the data structure from loadRotamer()
*
* Returns a properly-formatted REMARK 42 string.
*/
function makeRemark42($clash, $rama, $rota)
{
    $s = 'REMARK  42                                                                      
REMARK  42 MOLPROBITY STRUCTURE VALIDATION                                      
REMARK  42  PROGRAMS    : MOLPROBITY  (KING, REDUCE, AND PROBE)                 
REMARK  42  AUTHORS     : I.W.DAVIS,J.M.WORD                                    
REMARK  42  URL         : HTTP://KINEMAGE.BIOCHEM.DUKE.EDU/MOLPROBITY/          
REMARK  42  AUTHORS     : J.S.RICHARDSON,W.B.ARENDALL,D.C.RICHARDSON            
REMARK  42  REFERENCE   : NEW TOOLS AND DATA FOR IMPROVING                      
REMARK  42              : STRUCTURES, USING ALL-ATOM CONTACTS                   
REMARK  42              : METHODS IN ENZYMOLOGY. 2003;374:385-412.              
REMARK  42  MOLPROBITY OUTPUT SCORES:                                           
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
        $s .= str_pad(sprintf('REMARK  42  ALL-ATOM CLASHSCORE     : %6.2f  (%.2f B<40)', $clash['scoreAll'], $clash['scoreBlt40']), 80) . "\n";
    }
    if(is_array($rota))
    {
        $rotaOut = count(findRotaOutliers($rota));
        $rotaTot = count($rota);
        $rotaOutPct = (100.0 * $rotaOut / $rotaTot);
        $s .= str_pad(sprintf('REMARK  42  BAD ROTAMERS            : %5.1f%% %4d/%-5d  (TARGET  0-1%%)', $rotaOutPct, $rotaOut, $rotaTot), 80) . "\n";
    }
    if(is_array($rama))
    {
        $ramaOut = count(findRamaOutliers($rama));
        foreach($rama as $r) { if($r['eval'] == "Favored") $ramaFav++; }
        $ramaTot = count($rama);
        $ramaOutPct = (100.0 * $ramaOut / $ramaTot);
        $ramaFavPct = (100.0 * $ramaFav / $ramaTot);
        $s .= str_pad(sprintf('REMARK  42  RAMACHANDRAN OUTLIERS   : %5.1f%% %4d/%-5d  (TARGET  0.2%%)', $ramaOutPct, $ramaOut, $ramaTot), 80) . "\n";
        $s .= str_pad(sprintf('REMARK  42  RAMACHANDRAN FAVORED    : %5.1f%% %4d/%-5d  (TARGET 98.0%%)', $ramaFavPct, $ramaFav, $ramaTot), 80) . "\n";
    }
    
    return $s;         
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
