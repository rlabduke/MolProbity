<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Provides functions for running SSWING.
*****************************************************************************/

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
    $out = explode("\n", shell_exec($cmd));
    
    $swap = array();
    foreach($out as $line)
    {
        if(preg_match('/^[ 0-9]{3}[0-9] [^/]/', $line))
        {
            $al = substr($line, 7, 5);
            $coords = substr($line, 25, 24);
            $swap[$cnit.$al] = $coords;
        }
    }
    
    chdir($oldwd);
    return $swap;
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
