<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Provides kinemage-creation functions for visualizing various
    aspects of the analysis.
*****************************************************************************/

#{{{ makeSidechainDots - appends sc Probe dots
############################################################################
function makeSidechainDots($infile, $outfile)
{
    exec("probe 'alta' $infile >> $outfile");
}
#}}}########################################################################

#{{{ makeMainchainDots - appends mc Probe dots
############################################################################
function makeMainchainDots($infile, $outfile)
{
    exec("probe -mc 'mc alta' $infile >> $outfile");
}
#}}}########################################################################

#{{{ makeRamachandranKin - creates a kinemage-format Ramachandran plot
############################################################################
function makeRamachandranKin($infile, $outfile)
{
    exec("java -cp ".MP_BASE_DIR."/lib/hless.jar hless.Ramachandran -nosummary $infile > $outfile");
}
#}}}########################################################################

#{{{ makeRamachandranImage - creates a bitmapped Ramachandran plot
############################################################################
/**
* outfile should end in ".png" or possibly ".jpg"
* Other extensions will likely break the program.
*/
function makeRamachandranImage($infile, $outfile)
{
    exec("java -cp ".MP_BASE_DIR."/lib/hless.jar hless.Ramachandran $infile -nosummary -nokin -img $outfile");
}
#}}}########################################################################

#{{{ convertKinToPostscript - uses Mage to do EPS output
############################################################################
/**
* Outputs are named $infile.1.eps, $infile.2.eps, etc.
* One page is generated per frame of animation.
*/
function convertKinToPostscript($infile)
{
    exec("mage -postscript $infile");
}
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

    exec("probe $options -noticks -mc -self 'alta' $infile | gawk '$dots_off_script' >> $outfile");
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################
?>
