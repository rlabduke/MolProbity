<?php # (jEdit options) :folding=explicit:collapseFolds=1:
require_once(MP_BASE_DIR.'/lib/strings.php');

#{{{ describePdbStats - human-readable list of derived properties
############################################################################
/**
* Takes the output of pdbstat() and formats it in a useful way.
* An array of human-readable descriptive strings are returned.
*/
function describePdbStats($pdbstats, $useHTML = true)
{
    $details = array();
    if($useHTML)
    {
        $b = "<b>";
        $unb = "</b>";
    }
    else
    {
        $b = "**";
        $unb = "**";
    }

    $compnd = trim($pdbstats['compnd']);
    if($compnd != "")
        $details[] = "This compound is identified as $b$compnd$unb";

    // # of models, if any
    if($pdbstats['models'] > 1) $details[] = "This is an multi-model structure, probably from NMR, with $b".$pdbstats['models']." distinct models$unb present.";
    if(isset($pdbstats['resolution'])) $details[] = "This is a crystal structure at ".$pdbstats['resolution']." A resolution.";

    // # of chains and residues
    $details[] = $pdbstats['chains']." chain(s) is/are present [".$pdbstats['unique_chains']." unique chain(s)]";
    $details[] = "A total of ".$pdbstats['residues']." residues are present.";

    // Types of residues
    if($pdbstats['residues'] > 0)
    {
        if($pdbstats['sidechains'] == 0 && $pdbstats['nucacids'] == 0)
            $details[] = "{$b}Only C-alphas{$unb} are present.";
        else
        {
            if($pdbstats['sidechains'] > 0)
                $details[] = "Protein mainchain and sidechains are present.";
            if($pdbstats['all_alts'] > 0)
                $details[] = $pdbstats['all_alts']." protein residues have alternate conformations (".$pdbstats['mc_alts']." in mc/CB).";
            if($pdbstats['nucacids'] > 0)
                $details[] = "".$pdbstats['nucacids']." nucleic acid residues are present.";

            if($pdbstats['originalInputH'])
            {
              if($pdbstats['non_ecloud_H'])
                $details[] = "Explicit hydrogens present in original input file at nuclear positions. Hydrogens have been removed.";
              else
                $details[] = "Explicit hydrogens present in original input file at electron cloud positions. Hydrogens have been removed.";
            }
            elseif($pdbstats['has_most_H'])
                $details[] = "Explicit hydrogens are present.";
            elseif($pdbstats['hydrogens'] > 0)
                $details[] = "{$b}Not all hydrogens{$unb} are explicitly included, although a few are.";
            else
                $details[] = "No explicit hydrogen atoms are included.";
            if($pdbstats['deuteriums'] > 0)
                $details[] = "Deuterium atoms present. Assuming neutron diffraction.";
        }

        if($pdbstats['hets'] > 0)
            $details[] = "".$pdbstats['hets']." hetero group(s) is/are present.";
    }

    // Crystallographic information
    if(isset($pdbstats['refiprog'])) $details[] = "Refinement was carried out in $pdbstats[refiprog].";
    if(isset($pdbstats['refitemp'])) $details[] = "Data was collected at $pdbstats[refitemp] K.";
    if(isset($pdbstats['rvalue']))
    {
        $note = "R = $pdbstats[rvalue]";
        if(isset($pdbstats['rfree'])) $note .= "; Rfree = $pdbstats[rfree]";
        $details[] = $note;
    }

    if ($pdbstats['non_ecloud_H'] > 0.1) {
      //$nonEcloudPct = sprintf("%d", 100.0 * $pdbstats['non_ecloud_H']);
      //$details[] = /*"<div class=alert>".*/$nonEcloudPct."% non-electron cloud length hydrogens were found."/*."</div>"*/;
      $details[] = "A high percentage of non-electron cloud length hydrogens were found.";
    }

    //echo $pdbstats['v2atoms']." many pdbv2.3 atoms were found\n";
    if($pdbstats['v2atoms'] > 0) $details[] = /*"<div class=alert>".*/$b.$pdbstats['v2atoms']." PDBv2.3 atoms were found.  They have been converted to PDBv3.".$unb/*."</div>"*/;
    else                         $details[] = "0 PDBv2.3 atoms were found.  Proceeding assuming PDBv3 formatted file.";
    return $details;

}
#}}}########################################################################

#{{{ pdbstat - a script to document the contents of a PDB file
############################################################################
# pdbstat - a script to document the contents of a PDB file
#
# Output: an associative array describing the model
#   compnd          a description of the compound, from the PDB file
#   models          number of models in the file
#   chains          number of chains in the file
#   chainids        ARRAY of all the unique chain IDs in the file
#   unique_chains   number of unique (not duplicated) chains
#   residues        total number of residues in 1st model (but not all-HETATM)
#   sidechains      are sidechains present? (0/>0)
#   all_alts        total number of residues with alt conf defined
#   mc_alts         number of protein residues with mc/CB alts
#   nucacids        are nucleic acids present? (0/>0)
#   heavyatoms      are "heavy" (non-H) atoms present? (0/>0)
#   hnonpolar       are non-polar hydrogens present? (0/>0)
#   hydrogens       are hydrogens of any kind present? (0/>0)
#   has_most_H      are "all" hydrogens present, based on heavy/H ratio? (boolean)
#   hets            number of non-water heterogens
#   waters          number of waters
#   fromcns         headers look like CNS output? (0 < n < 7)
#   v2atoms         number of PDBv2.3 atoms
#
# Optional keys:
#   resolution      the crystallographic resolution
#   refiprog        the refinement program
#   refitemp        the data-collection temperature
#   rvalue          the crystallographic R-value
#   rfree           the crystallographic Rfree value
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
    $heavyatoms = 0;        # number of non-H atoms
    $hnonpolar = 0;         # number of non-polar Hs
    $hydrogens = 0;         # number of hydrogens (possibly polar)
    $deuteriums = 0;        # number of deuteriums (to ID neutron structures)
    $hets = 0;              # number of non-water hets
    $waters = 0;            # number of water hets
    $hetcode = "";          # current het ID
    $fromCNS = 0;           # counter for CNS-style header records
    $v2format = 0;          # counter for PDBv2.3 type atoms.
    $mcAlts = array();      # mainchain/CB alternates (set of res)
    $allAlts = array();     # total number of alternates (set of res)
    $rValue = array();      # various kinds of R value
    $rFree = array();       # various kinds of Rfree

    // for testing whether there are any old format atom lines.
    $hashFile = file(MP_BASE_DIR."/lib/PDBv2toPDBv3.hashmap.txt");
    $hash = array();
    foreach($hashFile as $line) {
        if(!startsWith($line, "#")) {
            $expLine = explode(':', $line);
            //echo rtrim($expLine[1])."->".$expLine[0];
            $hash[rtrim($expLine[1])] = $expLine[0];
        }
    }
    //echo sizeof($hash);

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
        if(startsWith($s, "ENDMDL")) { $models++; }
        # Prefer TITLE records over COMPND records
        elseif(startsWith($s, "TITLE")) { $compnd  .= " " . trim(substr($s,10,60)); $hadtitle = TRUE; }
        elseif(startsWith($s, "COMPND") && !$hadtitle) { $compnd  .= " " . trim(substr($s,10,60)); }
        elseif(startsWith($s, "REMARK"))
        {
            if(startsWith($s, 'REMARK   3   PROGRAM')) { $refiProg = trim(substr($s, 26, 44)); }
            elseif(startsWith($s, 'REMARK   3   R VALUE'))
            {
                if(preg_match('/^REMARK   3   R VALUE +?\(WORKING SET, NO CUTOFF\) : (0?\.0*[1-9][0-9]+)/', $s, $match)) { $rValue['work_nocut'] = $match[1]; }
                elseif(preg_match('/^REMARK   3   R VALUE +?\(WORKING SET \+ TEST SET, NO CUTOFF\) : (0?\.0*[1-9][0-9]+)/', $s, $match)) { $rValue['cryst_nocut'] = $match[1]; }
                elseif(preg_match('/^REMARK   3   R VALUE +?\(WORKING SET, F>4SIG\(F\)\) : (0?\.0*[1-9][0-9]+)/', $s, $match)) { $rValue['work_4sig'] = $match[1]; }
                elseif(preg_match('/^REMARK   3   R VALUE +?\(WORKING SET \+ TEST SET, F>4SIG\(F\)\) : (0?\.0*[1-9][0-9]+)/', $s, $match)) { $rValue['cryst_4sig'] = $match[1]; }
                elseif(!$rValue['work'] && preg_match('/^REMARK   3   R VALUE +?\(WORKING SET\) : (0?\.0*[1-9][0-9]+)/', $s, $match)) { $rValue['work'] = $match[1]; }
                elseif(!$rValue['cryst'] && preg_match('/^REMARK   3   R VALUE +?\(WORKING SET \+ TEST SET\) : (0?\.0*[1-9][0-9]+)/', $s, $match)) { $rValue['cryst'] = $match[1]; }
                elseif(!$rValue['generic'] && preg_match('/^REMARK   3   R VALUE.*?[ :](0?\.0*[1-9][0-9]+)/', $s, $match)) { $rValue['generic'] = $match[1]; }
            }
            elseif(startsWith($s, 'REMARK   3   FREE R VALUE'))
            {
                if(preg_match('/^REMARK   3   FREE R VALUE +?\(NO CUTOFF\) : (0?\.0*[1-9][0-9]+)/', $s, $match)) { $rFree['nocut'] = $match[1]; }
                elseif(preg_match('/^REMARK   3   FREE R VALUE +?\(F>4SIG\(F\)\) : (0?\.0*[1-9][0-9]+)/', $s, $match)) { $rFree['4sig'] = $match[1]; }
                elseif(!$rFree['generic'] && !preg_match('/^REMARK   3   FREE R VALUE TEST SET/', $s) && preg_match('/^REMARK   3   FREE R VALUE .+?: *(0?\.0*[1-9][0-9]+)/', $s, $match)) { $rFree['generic'] = $match[1]; }
            }
            elseif(preg_match('/^REMARK 200  TEMPERATURE           \(KELVIN\) : +(\d+\.\d+)/', $s, $match)) { $refiTemp = $match[1]; }
            elseif(!isset($resolution) && preg_match("/^REMARK   2/", $s) && !preg_match('/NOT APPLICABLE/', $s)
                && preg_match('/ (\d+\.\d*|0?\.\d+)/', substr($s,10,60), $match)) { $resolution = $match[1]; }
            # Alternative location for resolution info:
            elseif(!isset($resolution) && preg_match('/^REMARK   3   RESOLUTION RANGE HIGH .+?: +(\d+\.\d+)/', $s, $match)) { $resolution = $match[1]; }
            # CNS-style resolution record:
            elseif(preg_match('/^REMARK (at|map|refinement) resolution: \d+(\.\d+)? - (\d+(\.\d+)?) A/', $s, $match))
            {
                $resolution = $match[3];
                $fromCNS++;
            }
            # CNS-style R record:
            elseif(preg_match('/^REMARK final +r *= *(0\.\d+)( +free_r *= *(0\.\d+))?/', $s, $match))
            {
                $rValue['generic'] = $match[1];
                if($match[3])
                    $rFree['generic'] = $match[3];
                $fromCNS++;
            }
            # Other CNS-type records (markers for being from CNS)
            elseif(preg_match("/^REMARK coordinates from /", $s))           { $fromCNS++; }
            elseif(preg_match("/^REMARK parameter file 1/", $s))            { $fromCNS++; }
            elseif(preg_match("/^REMARK molecular structure file/", $s))    { $fromCNS++; }
            elseif(preg_match("/^REMARK input coordinates/", $s))           { $fromCNS++; }
            elseif(preg_match('/^REMARK FILENAME="/', $s))                  { $fromCNS++; }
            elseif(preg_match("/^REMARK DATE:/", $s))                       { $fromCNS++; }
        }
        elseif($models == 0) // only look at ATOMs in the first MODEL
        {
            if(startsWith($s, "ATOM"))
            {
                $chainids[$chain] = $chain; # record chain IDs used
                # Start of a new residue?
                if($id != $rescode)
                {
                    $residues++;
                    $rescode = $id;
                    $chains[$chain] .= $restype.'/'; # need slashes for correct substring test, below
                }

                # Atom name == CB?
                if(preg_match("/ CB [ A1]/", $atom)) { $cbetas++; $heavyatoms++; }
                # Atom name == C5' or C5* ?
                elseif(preg_match("/ C5[*'][ A1]/", $atom)) { $nucacids++; $heavyatoms++; }
                # Atom is a beta hydrogen? Good flag for nonpolar H in proteins.
                elseif(preg_match("/[ 1-9][HDTZ]B [ A1]/", $atom)) {
                  $hnonpolar++;
                  if(preg_match("/[ 1-9][D]B [ A1]/", $atom)) {
                    $deuteriums++;
                  }
                }
                # Atom is a C5' hydrogen in RNA/DNA? Good flag for nonpolar H in nucleic acids.
                elseif(preg_match("/[ 1-9][HDTZ]5[*'][ A1]/", $atom)) {
                  $hnonpolar++;
                  if(preg_match("/[ 1-9][D]5[*'][ A1]/", $atom)) {
                    $deuteriums++;
                  }
                }
                # Atom is a hydrogen?
                //elseif(preg_match("/[ 1-9][HDTZ][ A-Z][ 1-9*'][ A1]/", $atom)) { $hydrogens++; }
                elseif(preg_match("/[ 1-9][HDTZ]..[ A1]/", $atom)) {
                  $hydrogens++;
                  if(preg_match("/[ 1-9][D]..[ A1]/", $atom)) {
                    $deuteriums++;
                  }
                }
                # Atom is non-descript
                else { $heavyatoms++; }

                # Does this residue have alternate conformations?
                if($atom{4} != ' ')
                {
                    $allAlts[$id] = $id;
                    # TODO: This is still very protein-centric
                    if(preg_match("/( N  | CA  | C  | O  | CB )./", $atom)) $mcAlts[$id] = $id;
                }
            }
            elseif(startsWith($s, "HETATM"))
            {
                $chainids[$chain] = $chain; # record chain IDs used
                # Start of a new residue?
                if($id != $hetcode)
                {
                    if(preg_match("/HOH|DOD|H20|D20|WAT|SOL|TIP|TP3|MTO|HOD|DOH/", $restype))
                        { $waters++; }
                    if(preg_match("/YG |OMG|H2U|7MG|5MU|A2M|2MG|5FU|G7M|OMU|PR5|FHU|1MA|OMC|5MC|XUG|A23|UMS|FMU|UR3|YYG|CFL|UD5|CSL|PSU|UFT|5IC|5BU|M2G|BGM|CBR|U34|CCC|AVC|TM2|AET|IU |1MG/", $restype)) # hopefully catches modified bases without counting separate ligands too much as nucacids
                        { $nucacids++; $residues++; }
                    else
                        { $hets++; }
                    $hetcode = $id;
                }

            }

            //return $hash;
            if(startsWith($s, "ATOM")||startsWith($s, "HETATM")||startsWith($s, "TER")||startsWith($s, "ANISOU")||startsWith($s, "SIGATM")||startsWith($s, "SUIUIJ")) {
                $atom_name = substr($s, 12, 4);
                $resn = substr($s, 17, 3);
                #pre-screen for CNS Xplor RNA base names and Coot RNA base names
                if($resn == "GUA" || $resn == "ADE" || $resn == " CYT" || $resn == "THY" || $resn == "URI"){
                    $resn = "  ".substr($resn,0,1);
                }
                elseif($resn == " Ar" || $resn == " Gr" || $resn == " Cr" || $resn == " Ur" || $resn == "OIP"){
                    $resn = "  ".substr($resn,1,1);
                }
                $key = $atom_name." ".$resn;
                //echo "|".$key."|\n";
                if ($key !== " HA2 GLY") { // TEMP FIX: because HA2 GLY is both old AND new format,
                                           // Remediator messes it up.
                  if(array_key_exists($key, $hash)) {
                    $v2format++;
                  }
                }
            }
        }
    }
    fclose($file);
    //echo $v2format."\n";
    # Determine number of entries in $chains that are unique
    if(count($chains) > 1)
    {
        $ch = array_values($chains);
        $unique_chains = 0;

        for($i = 0; $i < count($ch); $i++)
        {
            for($j = $i+1; $j < count($ch); $j++)
            {
                #if($ch[$i] == $ch[$j]) { continue 2; }
                # Chains are "identical" if either is a substring of the other.
                if(strlen($ch[$i]) < strlen($ch[$j]))
                {
                    if(strpos($ch[$i], $ch[$j]) !== FALSE) { continue 2; }
                }
                else
                {
                    if(strpos($ch[$j], $ch[$i]) !== FALSE) { continue 2; }
                }
            }
            $unique_chains++;
        }
    }
    else $unique_chains = 1;

    if ($hydrogens+$hnonpolar > 0) {
      $nonECloudH = analyzeHydrogens($pdbfilename);
    }

    # Output
    $ret['compnd']          = $compnd;
    $ret['models']          = $models;
    $ret['chains']          = count($chains);
    $ret['chainids']        = $chainids;
    $ret['unique_chains']   = $unique_chains;
    $ret['residues']        = $residues;
    $ret['sidechains']      = $cbetas;
    $ret['nucacids']        = $nucacids;
    $ret['heavyatoms']      = $heavyatoms;
    $ret['hnonpolar']       = $hnonpolar;
    $ret['hydrogens']       = $hydrogens;
    $ret['deuteriums']      = $deuteriums;
    // Doesn't work for RNA -- too few H.  New criteria:  3+ per residue.
    //$ret['has_most_H']      = ($heavyatoms < 2*($hydrogens+$hnonpolar));
    $ret['has_most_H']      = (3*$residues < ($hydrogens+$hnonpolar));
    $ret['non_ecloud_H']   = $nonECloudH;
    $ret['originalInputH']  = $ret['has_most_H'];
    $ret['hets']            = $hets;
    $ret['waters']          = $waters;
    $ret['fromcns']         = $fromCNS;
    $ret['v2atoms']         = $v2format;
    $ret['all_alts']        = count($allAlts);
    $ret['mc_alts']         = count($mcAlts);

    if(isset($resolution))  $ret['resolution']  = $resolution;
    if(isset($refiProg))    $ret['refiprog']    = $refiProg;
    if(isset($refiTemp))    $ret['refitemp']    = $refiTemp;

    if($rValue['work_nocut'] && $rFree['nocut']) { $ret['rvalue'] = $rValue['work_nocut']; $ret['rfree'] = $rFree['nocut']; }
    elseif($rValue['cryst_nocut'] && $rFree['nocut']) { $ret['rvalue'] = $rValue['cryst_nocut']; $ret['rfree'] = $rFree['nocut']; }
    elseif($rValue['work_4sig'] && $rFree['4sig']) { $ret['rvalue'] = $rValue['work_4sig']; $ret['rfree'] = $rFree['4sig']; }
    elseif($rValue['cryst_4sig'] && $rFree['4sig']) { $ret['rvalue'] = $rValue['cryst_4sig']; $ret['rfree'] = $rFree['4sig']; }
    else
    {
        if(count($rValue) > 0)  $ret['rvalue'] = min($rValue);
        if(count($rFree) > 0)   $ret['rfree'] = min($rFree);
    }

    return $ret;
}
#}}}########################################################################

#{{{ analyzeHydrogens - checks lengths of Hs
function analyzeHydrogens($infile) {
    #$tmp1   = mpTempfile("tmp_dangle");
    echo "Analyzing Hydrogens\n";

    exec("java -Xmx512m -cp ".MP_BASE_DIR."/lib/dangle.jar dangle.Dangle -validate -hydrogens -outliers -sigma=0 $infile | wc -l", $allHs);
    exec("java -Xmx512m -cp ".MP_BASE_DIR."/lib/dangle.jar dangle.Dangle -validate -hydrogens -outliers $infile | wc -l", $outliers);

    if ($allHs > 0) $fract = ($outliers[0]-1)/($allHs[0]-1);
    else            $fract = 0;

    return $fract;
    #copy($tmp1, $outpath);

    #unlink($tmp1);
}
#}}}

?>
