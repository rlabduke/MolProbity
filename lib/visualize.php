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

#{{{ makeMulticritKin - display all quality metrics at once in 3-D
############################################################################
/**
* $infiles is an array of one or more PDB files to process
* $outfile will be overwritten.
* $opt controls what will be output. Each key below maps to a boolean:
*   Bscale              color scale by B-factor
*   Qscale              color scale by occupancy
*   ribbons             ribbons rainbow colored N to C
*   altconf             alternate conformations
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
    foreach($stats as $stat) fwrite($h, "[+]   $stat\n");
    if(count($infiles) > 0) fwrite($h, "Statistics for first file only; ".count($infiles)." total files included in kinemage.\n");
    fwrite($h, "@kinemage 1\n");
    fwrite($h, "@onewidth\n");
    fclose($h);
    
    foreach($infiles as $infile)
    {
        exec("prekin -quiet -mchb -lots -append -animate -onegroup -show 'mc(white),sc(blue)' $infile >> $outfile");
        
        if($opt['ribbons'])         makeRainbowRibbons($infile, $outfile);
        if($opt['altconf'])         makeAltConfKin($infile, $outfile);
        if($opt['rama'])            makeBadRamachandranKin($infile, $outfile);
        if($opt['rota'])            makeBadRotamerKin($infile, $outfile);
        if($opt['cbdev'])           makeBadCbetaBalls($infile, $outfile);
        if($opt['pperp'])           makeBadPPerpKin($infile, $outfile);
        if($opt['Bscale'])          makeBfactorScale($infile, $outfile);
        if($opt['Qscale'])          makeOccupancyScale($infile, $outfile);
        if($opt['dots'])            makeProbeDots($infile, $outfile, $opt['hbdots'], $opt['vdwdots']);
        if($nmrConstraints)
            exec("noe-display -cv -s viol -ds+ -fs -k $infile $nmrConstraints < /dev/null >> $outfile");
    }

    // KiNG allows us to do this to control what things are visible
    $h = fopen($outfile, 'a');
    fwrite($h, "@master {mainchain} off\n");
    fwrite($h, "@master {H's} off\n");
    fwrite($h, "@master {water} off\n");
    fwrite($h, "@master {Calphas} on\n");
    if($opt['ribbons'])     fwrite($h, "@master {ribbon} off\n");
    if($opt['dots'])        fwrite($h, "@master {wide contact} off\n");
    if($opt['dots'])        fwrite($h, "@master {close contact} off\n");
    if($opt['dots'])        fwrite($h, "@master {small overlap} off\n");
    if($opt['dots'])        fwrite($h, "@master {H-bonds} off\n");
    if($opt['Bscale'])      fwrite($h, "@master {B factors} off\n");
    if($opt['Qscale'])      fwrite($h, "@master {occupancy} off\n");
    fclose($h);
}
#}}}########################################################################

#{{{ makeAltConfKin - appends mc and sc alts
############################################################################
function makeAltConfKin($infile, $outfile, $mcColor = 'yellow', $scColor = 'cyan')
{
    $alts   = findAltConfs($infile);
    $mcGrp  = groupAdjacentRes(array_keys($alts['mc']));
    $scGrp  = groupAdjacentRes(array_keys($alts['sc']));
    $mc     = resGroupsForPrekin($mcGrp);
    $sc     = resGroupsForPrekin($scGrp);
    
    foreach($mc as $mcRange)
        exec("prekin -quiet -append -nogroup -listmaster 'mc alts' -bval -scope $mcRange -show 'mc($mcColor)' $infile >> $outfile");

    foreach($sc as $scRange)
        exec("prekin -quiet -append -nogroup -listmaster 'sc alts' -bval -scope $scRange -show 'sc($scColor)' $infile >> $outfile");
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

#{{{ makeBadRamachandranKin - appends mc of Ramachandran outliers
############################################################################
/**
* rama is the data from loadRamachandran(),
* or null to have the data generated automatically.
*/
function makeBadRamachandranKin($infile, $outfile, $rama = null, $color = 'red')
{
    if(!$rama)
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
        exec("prekin -append -nogroup -listmaster 'Rama Outliers' -scope $mcRange -show 'mc($color)' $infile >> $outfile");
}
#}}}########################################################################

#{{{ makeBadRotamerKin - appends sc of bad rotamers
############################################################################
/**
* rota is the data from loadRotamer(),
* or null to have it generated on the fly.
*/
function makeBadRotamerKin($infile, $outfile, $rota = null, $color = 'orange', $cutoff = 1.0)
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
        exec("prekin -quiet -append -nogroup -listmaster 'rotamer outliers' -bval -scope $scRange -show 'sc($color)' $infile >> $outfile");
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
    fwrite($h, "@subgroup {base-phos perp} dominant master={base-P outliers}\n");
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

#{{{ makeRainbowRibbons - make ribbon kin color-coded by C-alpha temp. factor
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
    $colors = "-colorscale 'white,0.02,lilactint,0.33,lilac,0.66,purple,0.99,gray'";
    exec("prekin -quiet -append -nogroup -listmaster 'occupancy' -bval -scope -show 'mc,sc' -ocol $colors $infile >> $outfile");
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
    foreach($res as $k => $v) $res[$k] = array('cnit' => $v);
    
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
            if($item['eval'] == "OUTLIER")
            {
                $res[$item['resName']]['rama'] = "<td bgcolor='#ff6699'>$item[eval] ($item[scorePct]%)<br><small>$item[type] - $item[phi],$item[psi]</small></td>";
                $res[$item['resName']]['isbad'] = true;
            }
            else
                $res[$item['resName']]['rama'] = "<td>$item[eval] ($item[scorePct]%)<br><small>$item[type] / $item[phi],$item[psi]</small></td>";
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
    
    $out = fopen($outfile, 'wb');
    fwrite($out, "<table width='100%' cellspacing='1' border='0'>\n");
    fwrite($out, "<tr align='center' bgcolor='".MP_TABLE_HIGHLIGHT."'>\n");
    fwrite($out, "<td><b>Res</b></td>\n");
    if(is_array($clash))  fwrite($out, "<td><b>Clash &gt; 0.4&Aring;</b></td>\n");
    if(is_array($rama))   fwrite($out, "<td><b>Ramachandran</b></td>\n");
    if(is_array($rota))   fwrite($out, "<td><b>Rotamer</b></td>\n");
    if(is_array($cbdev))  fwrite($out, "<td><b>C&beta; deviation</b></td>\n");
    if(is_array($pperp))  fwrite($out, "<td><b>Base-P perp. dist.</b></td>\n");
    fwrite($out, "</tr>\n");
    
    $color = MP_TABLE_ALT1;
    foreach($res as $cnit => $eval)
    {
        fwrite($out, "<tr align='center' bgcolor='$color'>");
        fwrite($out, "<td align='left'>$cnit</td>");
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
    if($a['cnit'] < $b['cnit'])     return -1;
    elseif($a['cnit'] > $b['cnit']) return 1;
    else                            return 0;
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

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################
?>
