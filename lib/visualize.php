<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Provides kinemage-creation functions for visualizing various
    aspects of the analysis.
*****************************************************************************/

#{{{ makeSidechainDots - appends sc Probe dots
############################################################################
function makeSidechainDots($infile, $outfile)
{
    exec("probe -quiet -noticks -name 'sc-x dots' -self 'alta' $infile >> $outfile");
}
#}}}########################################################################

#{{{ makeMainchainDots - appends mc Probe dots
############################################################################
function makeMainchainDots($infile, $outfile)
{
    exec("probe -quiet -noticks -name 'mc-mc dots' -mc -self 'mc alta' $infile >> $outfile");
}
#}}}########################################################################

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

#{{{ makeCbetaDevBalls - plots CB dev in 3-D, appending to the given file
############################################################################
function makeCbetaDevBalls($infile, $outfile)
{
    exec("prekin -append -cbetadev $infile >> $outfile");
}
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
* $outfile will be overwritten.
* $rama is the data structure from loadRamachandran()
* $rota is the data structure from loadRotamer()
*/
function makeMulticritKin($infile, $outfile, $rama, $rota)
{
        if(file_exists($outfile)) unlink($outfile);
        
        $h = fopen($outfile, 'a');
        fwrite($h, "@kinemage 1\n@group {macromol.} dominant off\n");
        fclose($h);
        exec("prekin -append -nogroup -scope -show 'mc(white),sc(brown),hy(gray),ht(sky)' $infile >> $outfile");
        
        $h = fopen($outfile, 'a');
        fwrite($h, "@group {waters} dominant off\n");
        fclose($h);
        exec("prekin -append -nogroup -scope -show 'wa(bluetint)' $infile >> $outfile");
        
        $h = fopen($outfile, 'a');
        fwrite($h, "@group {B ribbons} dominant off\n");
        fclose($h);
        makeBfactorRibbons($infile, $outfile);
        
        $h = fopen($outfile, 'a');
        fwrite($h, "@group {Ca trace} dominant\n");
        fclose($h);
        exec("prekin -append -nogroup -scope -show 'ca(gray)' $infile >> $outfile");
        
        makeAltConfKin($infile, $outfile);
        makeBadRamachandranKin($infile, $outfile, $rama);
        makeBadRotamerKin($infile, $outfile, $rota);
        makeBadCbetaBalls($infile, $outfile);
        makeBadDotsVisible($infile, $outfile, true); // if false, don't write hb, vdw
}
#}}}########################################################################

#{{{ makeAltConfKin - appends mc and sc alts
############################################################################
function makeAltConfKin($infile, $outfile, $mcColor = 'yellow', $scColor = 'gold', $off = 'off')
{
    $alts   = findAltConfs($infile);
    $mcGrp  = groupAdjacentRes(array_keys($alts['mc']));
    $scGrp  = groupAdjacentRes(array_keys($alts['sc']));
    $mc     = resGroupsForPrekin($mcGrp);
    $sc     = resGroupsForPrekin($scGrp);
    
    $h = fopen($outfile, 'a');
    fwrite($h, "@group {mc alts} dominant $off\n");
    fclose($h);
    foreach($mc as $mcRange)
        exec("prekin -append -nogroup -scope $mcRange -show 'mc($mcColor)' $infile >> $outfile");

    $h = fopen($outfile, 'a');
    fwrite($h, "@group {sc alts} dominant $off\n");
    fclose($h);
    foreach($sc as $scRange)
        exec("prekin -append -nogroup -scope $scRange -show 'sc($scColor)' $infile >> $outfile");
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
* rama is the data from loadRamachandran()
*/
function makeBadRamachandranKin($infile, $outfile, $rama, $color = 'green')
{
    foreach($rama as $res)
    {
        if($res['eval'] == 'OUTLIER')
            $worst[] = $res['resName'];
    }
    $mc = resGroupsForPrekin(groupAdjacentRes($worst));
    
    $h = fopen($outfile, 'a');
    fwrite($h, "@group {Rama outliers} dominant\n");
    fclose($h);
    foreach($mc as $mcRange)
        exec("prekin -append -nogroup -scope $mcRange -show 'mc($color)' $infile >> $outfile");
}
#}}}########################################################################

#{{{ makeBadRotamerKin - appends sc of bad rotamers
############################################################################
/**
* rota is the data from loadRotamer()
*/
function makeBadRotamerKin($infile, $outfile, $rota, $color = 'sea', $cutoff = 1.0)
{
    foreach($rota as $res)
    {
        if($res['scorePct'] <= $cutoff)
        $worst[] = $res['resName'];
    }
    $sc = resGroupsForPrekin(groupAdjacentRes($worst));
    
    $h = fopen($outfile, 'a');
    fwrite($h, "@group {bad rotamers} dominant\n");
    fclose($h);
    foreach($sc as $scRange)
        exec("prekin -append -nogroup -scope $scRange -show 'sc($color)' $infile >> $outfile");
}
#}}}########################################################################

#{{{ makeBadCbetaBalls - plots CB dev in 3-D, appending to the given file
############################################################################
function makeBadCbetaBalls($infile, $outfile)
{
    $h = fopen($outfile, 'a');
    fwrite($h, "@group {CB deviation} dominant\n");
    fclose($h);

    // C-beta deviation balls >= 0.25A
    $cbeta_dev_script = 
'BEGIN { doprint = 0; bigbeta = 0; }
$0 ~ /^@/ { doprint = 0; }
$0 ~ /^@(point)?master/ { print $0 }
$0 ~ /^@balllist/ { doprint = 1; print $0; }
match($0, /^\{ cb .+ r=([0-9]\.[0-9]+) /, frag) { gsub(/gold|pink/, "magenta"); bigbeta = (frag[1]+0 >= 0.25); }
doprint && bigbeta';
    
    exec("prekin -append -quiet -cbetadev $infile | gawk '$cbeta_dev_script' >> $outfile");
}
#}}}########################################################################

#{{{ makeBadDotsVisible - appends Probe dots with only bad clashes visible
############################################################################
/**
* Documentation for this function.
*/
function makeBadDotsVisible($infile, $outfile, $allDots = false)
{
    if($allDots)    $options = "";
    else            $options = "-nohbout -novdwout";
    
    $dots_off_script =
'$0 ~ /^@(dot|vector)list .* master=\{wide contact\}/ { $0 = $0 " off" }
$0 ~ /^@(dot|vector)list .* master=\{close contact\}/ { $0 = $0 " off" }
$0 ~ /^@(dot|vector)list .* master=\{small overlap\}/ { $0 = $0 " off" }
$0 ~ /^@(dot|vector)list .* master=\{H-bonds\}/ { $0 = $0 " off" }
{print $0}';

    //exec("probe $options -quiet -noticks -mc -self 'alta' $infile | gawk '$dots_off_script' >> $outfile");
    exec("probe $options -quiet -noticks -name 'sc-x dots' -self 'alta' $infile | gawk '$dots_off_script' >> $outfile");
    exec("probe $options -quiet -noticks -name 'mc-mc dots' -mc -self 'mc alta' $infile | gawk '$dots_off_script' >> $outfile");
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

#{{{ makeFlipkin - runs Flipkin to generate a summary of the Reduce -build changes
############################################################################
/**
*/
function makeFlipkin($inpath, $outpathAsnGln, $outpathHis)
{
    exec("flipkin -limit" . MP_REDUCE_LIMIT . " $inpath > $outpathAsnGln");
    exec("flipkin -limit" . MP_REDUCE_LIMIT . " -h $inpath > $outpathHis");
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
