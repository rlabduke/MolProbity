<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Provides functions for running SSWING.
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/analyze.php');

#{{{ pdbSwapCoords - updates coordinates of a PDB file
############################################################################
/**
* A simple facility for changing ATOM/HETATM coordinates in a PDB file.
* The keys of $swap are 14-character codes 'CNNNNITTTAAAAL' -- chain, number,
* insertion code, type, atom, alt conf.
* The values of $swap are 24-character strings to replace cols 31 - 54 of the
* appropriate ATOM/HETATM records. The string consists of three numbers in
* Fortran 8.3 format, right justified and space padded.
*/
function pdbSwapCoords($inpath, $outpath, $swap)
{
    $in = fopen($inpath, 'rb');
    $out = fopen($outpath, 'wb');
    while(!feof($in))
    {
        $s = fgets($in, 4096);
        if(startsWith($s, 'ATOM  ') || startsWith($s, 'HETATM'))
        {
            $cnit = substr($s, 21, 1) . substr($s, 22, 4) . substr($s, 26, 1)
                . substr($s, 17, 3) . substr($s, 12, 5);
            if(isset($swap[$cnit]))
            {
                $s = substr($s, 0, 30) . $swap[$cnit] . substr($s, 54);
            }
        }
        fwrite($out, $s);
    }
    fclose($out);
    fclose($in);
}
#}}}########################################################################

#{{{ runSswing - runs SSWING and returns the "best" conformation
############################################################################
/**
* Runs SSWING and removes the files it creates. Returns new coords in same
* format as for pdbSwapCoords(), above.
* 
* pdbfile       full path to the input PDB file
* mapfile       a CCP4-format electron density map file
* workdir       the directory to work in, where tmp files are created
* cnit          the CNNNNITTT code of the residue to try refitting
*/
function runSswing($pdbfile, $mapfile, $workdir, $cnit)
{
    $oldwd = getcwd();
    chdir($workdir);
    
    $cmd = "sswing $pdbfile ".trim(substr($cnit,1,4))." ".trim(substr($cnit,6,3))." $mapfile";
    if(substr($cnit,0,1) != ' ') $cmd .= " ".substr($cnit,0,1);
    //echo("\n\n".$cmd."\n\n"); //XXX-TMP
    $out = shell_exec($cmd);
    //echo($out."\n\n"); //XXX-TMP
    
    $swap = array();
    $h = fopen('sidechainPDB.pdb', 'rb');
    if($h)
    {
        while(!feof($h))
        {
            $line = fgets($h, 4096);
            if(startsWith($line, "ATOM  ") || startsWith($line, "HETATM"))
            {
                $al = substr($line, 12, 5);
                $coords = substr($line, 30, 24);
                $swap[$cnit.$al] = $coords;
            }
        }
        fclose($h);
        unlink('sidechainPDB.pdb');
    }
    else echo "*** Unable to open sidechainPDB.pdb from SSWING run\n";
    
    chdir($oldwd);
    return $swap;
}
#}}}########################################################################

#{{{ makeSswingKin - display all changes
############################################################################
/**
* $outfile will be overwritten.
* $cnit is an array of CNIT codes for the residues that were processed.
*/
function makeSswingKin($pdb1, $pdb2, $outfile, $cnit)
{
        if(file_exists($outfile)) unlink($outfile);
        
        $stats = describePdbStats( pdbstat($pdb1), false );
        $h = fopen($outfile, 'a');
        fwrite($h, "@text\n");
        fwrite($h, "Sidechains have been refit by SSWING. Details of the input file:\n\n");
        foreach($stats as $stat) fwrite($h, "[+]   $stat\n");
        fwrite($h, "@kinemage 1\n");
        
        // Calculate views for each residue in CNIT
        $ctr = computeResCenters($pdb1);
        foreach($cnit as $res)
        {
            $i++;
            $c = $ctr[$res];
            fwrite($h, "@{$i}viewid {{$res}}\n@{$i}span 12\n@{$i}zslab 100\n@{$i}center $c[x] $c[y] $c[z]\n");
        }
        
        fclose($h);
        exec("prekin -quiet -append -animate -onegroup -show 'mc,sc(peach),ca,hy,ht,wa' $pdb1 >> $outfile");
        exec("probe -quiet -noticks -nogroup -self 'alta' $pdb1 >> $outfile");
        exec("prekin -quiet -append -animate -onegroup -show 'mc,sc(sky),ca,hy,ht,wa' $pdb2 >> $outfile");
        exec("probe -quiet -noticks -nogroup -self 'alta' $pdb2 >> $outfile");
        
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
