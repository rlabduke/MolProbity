<?php
# pdbstat - a script to document the contents of a PDB file
#
# Output: an associative array describing the model
#   compnd          a description of the compound, from the PDB file
#   models          number of models in the file
#   chains          number of chains in the file
#   chainids        ARRAY of all the unique chain IDs in the file
#   unique_chains   number of unique (not duplicated) chains
#   residues        total number of residues (in 1st model)
#   sidechains      are sidechains present? (0/>0)
#   nucacids        are nucleic acids present? (0/>0)
#   hnonpolar       are non-polar hydrogens present? (0/>0)
#   hydrogens       are hydrogens of any kind present? (0/>0)
#   hets            number of non-water heterogens
#   fromcns         headers look like CNS output? (0 < n < 7)
#
# Optional keys:
#   resolution      the crystallographic resolution
#   refiprog        the refinement program
#   refitemp        the data-collection temperature
#
# IWD 1/28/03
#   adapted from IWD's pdbstat.pl
#   builds on IWD's pdbstat and MGP's pdbstat.awk
#
function pdbstat($pdbfilename)
{
    # Variables for tracking data
    $models = 0;            # number of ENDMDL records encountered
    $residues = 0;          # number of distinct residues (changes of res. ID)
    $rescode = "";          # current res. ID
    $cbetas = 0;            # number of C-betas (for sidechains)
    $nucacids = 0;          # number of nucleic acids
    $hnonpolar = 0;         # number of non-polar Hs
    $hydrogens = 0;         # number of hydrogens (possibly polar)
    $hets = 0;              # number of non-water hets
    $hetcode = "";          # current het ID
    $fromCNS = 0;           # counter for CNS-style header records
    
    $file = fopen($pdbfilename, "r");
    while(!feof($file))
    {
        $s = rtrim(fgets($file, 1024));
        
        # These will be meaningless for some lines, but that's OK
        $chain   = substr($s,21,1); # Chain ID (one letter)
            if($chain == " ") $chain = "_";
        $resno   = substr($s,22,4); # Residue number (four chars)
        $icode   = substr($s,26,1); # Insertion code (one char)
        $restype = substr($s,17,3); # Residue type (three chars)
        $atom    = substr($s,12,5); # Atom (four) + alt (one)
        $id = $chain.$resno.$icode.$restype; # 9-character residue ID
    
        # Switch on record type
        if(preg_match("/^ENDMDL/", $s)) { $models++; }
        # Prefer TITLE records over COMPND records
        elseif(preg_match("/^TITLE /", $s)) { $compnd  .= " " . trim(substr($s,10,60)); $hadtitle = TRUE; }
        elseif(preg_match("/^COMPND/", $s) && !$hadtitle) { $compnd  .= " " . trim(substr($s,10,60)); }
        elseif(preg_match('/^REMARK   3   PROGRAM     : (.+)$/', $s, $match)) { $refiProg = $match[1]; }
        elseif(preg_match('/^REMARK 200  TEMPERATURE           (KELVIN) : (.+)$/', $s, $match)) { $refiTemp = $match[1]; }
        elseif(preg_match("/^REMARK   2/", $s) && preg_match("/ (\\d+\\.\\d+)/", substr($s,10,60), $match)) { $resolution = $match[1]; }
        # CNS-style resolution record:
        elseif(preg_match("/^REMARK refinement resolution: \\d+\\.\\d+ - (\\d+\\.\\d+) A/", $s, $match))
        {
            $resolution = $match[1];
            $fromCNS++;
        }
        # Other CNS-type records (markers for being from CNS)
        elseif(preg_match("/^REMARK coordinates from /", $s))           { $fromCNS++; }
        elseif(preg_match("/^REMARK parameter file 1/", $s))            { $fromCNS++; }
        elseif(preg_match("/^REMARK molecular structure file/", $s))    { $fromCNS++; }
        elseif(preg_match("/^REMARK input coordinates/", $s))           { $fromCNS++; }
        elseif(preg_match('/^REMARK FILENAME="/', $s))                  { $fromCNS++; }
        elseif(preg_match("/^REMARK DATE:/", $s))                       { $fromCNS++; }
        elseif($models == 0)
        {
            if(preg_match("/^ATOM  /", $s))
            {
                $chainids[$chain] = $chain; # record chain IDs used
                # Start of a new residue?
                if($id != $rescode)
                {
                    $residues++;
                    $rescode = $id;
                    $chains[$chain] .= $restype;
                }
                
                # Atom name == CB?
                if(preg_match("/ CB [ A1]/", $atom)) { $cbetas++; }
                # Atom name == C5' or C5* ?
                elseif(preg_match("/ C5[*'][ A1]/", $atom)) { $nucacids++; }
                # Atom is a beta hydrogen? Good flag for nonpolar H in proteins.
                elseif(preg_match("/[ 1-9][HDTZ]B [ A1]/", $atom)) { $hnonpolar++; }
                # Atom is a C5' hydrogen in RNA/DNA? Good flag for nonpolar H in nucleic acids.
                elseif(preg_match("/[ 1-9][HDTZ]5[*'][ A1]/", $atom)) { $hnonpolar++; }
                # Atom is a hydrogen?
                elseif(preg_match("/[ 1-9][HDTZ][ A-Z][ 1-9][ A1]/", $atom)) { $hydrogens++; }
            }
            elseif(preg_match("/^HETATM/", $s))
            {
                $chainids[$chain] = $chain; # record chain IDs used
                # Start of a new residue?
                if($id != $hetcode and !preg_match("/HOH|DOD|H20|D20|WAT|SOL|TIP|TP3|MTO|HOD|DOH/", $restype))
                {
                    $hets++;
                    $hetcode = $id;
                }
            }
        }
    }
    fclose($file);
    
    # Determine number of entries in $chains that are unique
    if(count($chains) > 1)
    {
        $ch = array_values($chains);
        $unique_chains = 0;
        
        for($i = 0; $i < count($ch); $i++)
        {
            for($j = $i+1; $j < count($ch); $j++)
            {
                if($ch[$i] == $ch[$j]) { continue 2; }
            }
            $unique_chains++;
        }
    }
    else $unique_chains = 1;
    
    # Output
    $ret['compnd']          = $compnd;
    $ret['models']          = $models;
    $ret['chains']          = count($chains);
    $ret['chainids']        = $chainids;
    $ret['unique_chains']   = $unique_chains;
    $ret['residues']        = $residues;
    $ret['sidechains']      = $cbetas;
    $ret['nucacids']        = $nucacids;
    $ret['hnonpolar']       = $hnonpolar;
    $ret['hydrogens']       = $hydrogens;
    $ret['hets']            = $hets;
    $ret['fromcns']         = $fromCNS;

    if(isset($resolution))  $ret['resolution']  = $resolution;
    if(isset($refiProg))    $ret['refiprog']    = $refiProg;
    if(isset($refiTemp))    $ret['refitemp']    = $refiTemp;
    
    return $ret;
}
?>
