#!/usr/bin/perl 
# (jEdit options) :folding=explicit:collapseFolds=1:

use File::Basename;
$start = time();
#TODO  add proper handling of alt, ins, and chain breaks
#      split buttons to zoom, fix, undo DONE
#      change clash metric to probe score i.e. include H-bonding
#    
#       Write raido-button handling for Molprobity DONE
#       Write a routine for 're-calculating if the default fixes aren't accepted DONE
#       Write a routine for generating the 'flip' kins DONE
#
#       Include Args
#       Add flag so only Args are attempted (not VLTSC)
#
#temp fix
$alt=" "; 

#{{{ version - sub-routine for outputing the RLab version
############################################################################
sub version{
        print "\n*******************************************************************
vtlr_fixes_auto.pl: version 1.01.090801    08/01/09
Copyright 2009, Daniel Keedy, Jeff Headd and Robert Immormino \n\n";
}
#}}}########################################################################

#{{{ help - sub-routine for outputing the Usage and Help
############################################################################
sub help{
   &version; 
   print "
USAGE: vtlr_fixes_auto.pl [-options] input_pdb_file (input_ccp4_map input_mtz) 
            tempFile_path MP_BASE_DIR autoFix_log modelOutpath

options:
  -Help                 outputs this help message
  -Changes              outputs a changelog
  -Version              outputs the Version information
  -Decoy-only           only try to fix known decoys
  -RotamericityCutoff   Cutoff for trying to fix toramers if Rotamericity is 
                          less than the RotamericityCutoff and in VTSCRIL an 
                          attempt to fix the rotamer will be made.
                          
vtlr_fixes_auto.pl is generally inteded to detect and suggest fixes 
via coot for Val, Thr, Leu and Arg residues with Chi angles consist with a 
'flipped' terminal branch... 
Output is directed to input_pdb_file_mod

EXAMPLES: vtlr_fixes_auto.pl  [flags] 1A0F.pdb 1a0f.map 1aof_temp 
                                /Users/Bob_Immormino/Sites/molprobity3/trunk/molprobity3 
          vtlr_fixes_auto.pl  [flags] 1A0F.pdb 1a0f_sigmaa.mtz 1aof_temp 
                                /Users/Bob_Immormino/Sites/molprobity3/trunk/molprobity3 
                                
DEFAULT
   -Decoy-only           False
   -RotamericityCutoff    6%  

      the resulting output is: 
                1A0F_mod.pdb            Modified PDB
                1A0F_autoFlip.kin       FlipKin
                1A0F_coot_fix_VTLR.scm  Coot script
                1AOF_button.scm         Interactive coot script with buttons
                1A0F_stats              Full statistics
                \n\n";
        exit(0);
}
#}}}########################################################################

#{{{ changes - sub-routine for outputing the Changelog
############################################################################
sub changes{
   &version;
        print "    ***** Val/Thr/Leu/Ser/Cys Fixes *****
  
  Intention:
  To find and write a coot script for fixing poor Val, Thr, Ser, Cys, 
  and Leu sidechain rotamers in particular the focus is on \"flipped\" 
  non-rotameric conformations i.e. Chi1 near 0, 120, -120 for 
  Val, Thr, Ser, Cys and Chi2 near 0, 120, -120 for Leu.
  
  ***** Arg Flips *****
  
  Intention:
  to find best rotamer for all 
  sufficiently non-rotameric Args provided
  that the guanidium group is flipped by ~180
  degrees (i.e. it's a flip).
  The goal is to identify common mistakes 
  crystallographers make when fitting Args
  that flip them 180 degrees.
  
  We'll try the following:
  1) re-fit all Args to the best
  rotamer as Bob did for Val, Thr, & Leu
  below
  2) filter the  new coordinates by
  rota/rama scores (bad --> ignore flip)
  3) accept if the final 'composite score'
  is greater than some threshold and if 
  the change in 'composite score' is > 0.
  4) compare the new and old dihedrals.
  
  Hopefully some patterns will emerge with
  regards to initial or final rota, 
  some subset of initial or final chis,
  initial or final or change in PROBE score,
  or some scoring function/combo of these
  and/or other parameters.
  
  We can also look at chi1-4 diff vectors (or 
  pre- and post-flip chi1-4) simultaneously
  in 4D graphics a la 7D RNA clustering,
  perhaps with a different color/icon if a 
  backrub is involved.
  
  We might also try re-fitting a set of
  'good flip examples' (from mine + Gary's
  + Molly's + struc genomics ex. + ribosome),
  including backrubs, BY HAND and then feeding
  them into Coot for refining via the process
  outlined above and see how the results 
  differ...
  
  070821 rmi     - added SER and CYS to the target residues
  070826 rmi     - added -Version and -Changes support 
  070827 rmi     - added the buttons routine for outputing 
                    the scheme for the buttons GUI in coot
                 - added Jedit folds around sub-routines
  070828 dak     - added support for Arg Flips
  070910 dak     - added chi2-4 to 
  070911 rmi     - Paul Emsley sent new scheme for making buttons
                 - Now there are three columns
                 - The old buttons subroutine was renamed buttons_OLD
  070912         - Re-formatted the buttons GUI now there are two lines
                   the first line gives information on the \"problem\"
                   and the second will center, fix, or undo
                 - Replaced (tabs) with (three spaces)
                 - Made changes to allow run without opening coot graphics
                 - Made a new directory to store temp files
  071120 rmi     - Making code workable in Molprobity
  071121 rmi     - Directed temp files to $temp_data for more uniform behavior
                 - Useful files (like _mod.pdb and the flipkin) are directed 
                   to the whole path of the originating .pdb 
 090801 rmi      - Re-write to remove excess code
                    Store quality stats in a single multidimensional array
                    Add rsc stats using iotbx.python
                   Get code to work with MolProbity
                   Change call to flipkin_auto_fixes to use pdbtmp1 to see
                    all the attempted flips 
                   Change strategy for picking residues to try to fix from
                    rotamericity < 6 to *decoys* 
                    where decoys are computationally defined as having 
                    a rotamericy <= 1 and chis:
   For LEU
      (chi1 > 230  &&  chi1 < 300)   &&  (chi2 > 300  ||  chi2 < 60)    Likely decoy for mt
      (chi1 > 120  &&  chi1 < 180)   &&  (chi2 > 300  ||  chi2 < 60)    Likely decoy for tt
      (chi1 > 180  &&  chi1 < 250)   &&  (chi2 > 150  &&  chi2 < 270)   Likely decoy for tp
      (chi1 > 260  &&  chi1 < 360)   &&  (chi2 > 180  &&  chi2 < 300)   Likely decoy for mp
   For VAL and THR
      chi1 > 330  ||  chi1 <  30    Likely decoy for t
      chi1 >  90  &&  chi1 < 150    Likely decoy for m
      chi1 > 210  &&  chi1 < 270    Likely decoy for p

                   Accepts an argument for the output model necessary
                    for flow control in MolProbity
 090804 rmi      - Change quick_clashlist to quick_clashscore to get the 
                    probe scores for the individual residues 
                   Try to use this to look at hydrogen-bonding

\n"; 
        exit(0);
}
#}}}########################################################################

#{{{ buttons - routine for writing the Scheme for Coot
############################################################################
#
# Coot can use both python and guile as the main scripting languages
# The guile default scripting language looks and seemingly acts quite
# like scheme in that all commands are surrounded in parenthesis
# The following code writes scheme commands interpretable by coot for 
# making a 'buttons' GUI.  The current buttons on the gui are:
#       Fix-all 
#       Zoom, Fix, and Undo a Single Residue
#  

sub buttons {
        $coot_button_commands = $_[0];
        $buttonsout = $temp_data."/".$modelID."_button.scm";

        open BUTTONSOUT, ">$buttonsout";

        print BUTTONSOUT "
;; geometry is an improper list of ints (e.g. (cons 300 400))
;; buttons is a list of: (list (list button-1-label button-1-action
;;                                   button-2-label button-2-action
;;                                   button-3-label button-3-action))
;;
;; The button-1-action, button-2-action and button-3-action functions
;; takes as an argument the imol.
;; 


(copy-molecule 0)
(define (dialog-box-of-button-triples imol window-name geometry buttons close-button-label)

  (let* ((window (gtk-window-new 'toplevel))
    (scrolled-win (gtk-scrolled-window-new))
    (outside-vbox (gtk-vbox-new #f 2))
    (inside-vbox (gtk-vbox-new #f 0)))
    
    (gtk-window-set-default-size window (car geometry) (cdr geometry))
    (gtk-window-set-title window window-name)
    (gtk-container-border-width inside-vbox 2)
    (gtk-container-add window outside-vbox)
    (gtk-box-pack-start outside-vbox scrolled-win #t #t 0) ; expand fill padding
    (gtk-scrolled-window-add-with-viewport scrolled-win inside-vbox)
    (gtk-scrolled-window-set-policy scrolled-win 'automatic 'always)

    (map (lambda (buttons-info)
      (if (list? buttons-info)
          (let* ((button-label-1 (car buttons-info))
            (callback-1  (car (cdr buttons-info)))

            (button-label-2 (car (cdr (cdr buttons-info))))
            (callback-2  (car (cdr (cdr (cdr buttons-info)))))

            (button-label-3 (car (cdr (cdr (cdr (cdr buttons-info))))))
            (callback-3  (car (cdr (cdr (cdr (cdr (cdr buttons-info)))))))

            (button-1 (gtk-button-new-with-label button-label-1))
            (h-box (gtk-hbox-new #f 2)))

       (gtk-signal-connect button-1 \"clicked\" 
                 (lambda ()
                   (callback-1 imol)))
       (gtk-box-pack-start h-box button-1 #f #f 2)

       (if callback-2 
           (let ((button-2 (gtk-button-new-with-label button-label-2)))
             (gtk-signal-connect button-2 \"clicked\" 
                  (lambda ()
                    (callback-2 imol)))
             (gtk-box-pack-start h-box button-2 #f #f 2)))

       (if callback-3
           (let ((button-3 (gtk-button-new-with-label button-label-3)))
             (gtk-signal-connect button-3 \"clicked\" 
                  (lambda ()
                    (callback-3 imol)))
             (gtk-box-pack-start h-box button-3 #f #f 2)))


       (gtk-box-pack-start inside-vbox h-box #f #f 2))))
    buttons)

    (gtk-container-border-width outside-vbox 2)
    (let ((ok-button (gtk-button-new-with-label close-button-label)))
      (gtk-box-pack-end outside-vbox ok-button #f #f 0)
      (gtk-signal-connect ok-button \"clicked\"
           (lambda args
             (gtk-widget-destroy window))))
    
    
    (let ((fixall-button (gtk-button-new-with-label \"Fix All\")))
      (gtk-box-pack-end outside-vbox fixall-button #f #f 0)
      (gtk-signal-connect fixall-button \"clicked\"
           (lambda ()

;; input coot style commands \n\n"; 

   print BUTTONSOUT "(set-refinement-immediate-replacement 1)\n\n";
   open ALLFIXES, "<$coot_script"; 
   $before_first_button=1; 
   while ($line =<ALLFIXES>) {
      while ($before_first_button==1) {
         if ($line !~ m/;;/) { 
            $line =<ALLFIXES>; 
	    if (eof(ALLFIXES)) { last; }
         }
         else { $before_first_button=0; }
      }    
      if($line =~ m/save-coordinates/) {
         last;
      }
      print BUTTONSOUT $line; 
   }
   close ALLFIXES;  

   print BUTTONSOUT "
                        )) )

   (gtk-widget-show-all window)))

   (let* ((imol 0)
          (action-func (lambda (imol chain-id resno inscode x y z info)
               (let ((backup-mode (backup-state imol))
               (replacement-state (refinement-immediate-replacement-state)))
             ; (turn-off-backup imol) for now
               (set-refinement-immediate-replacement 1)
               (set-rotation-centre x y z)
               (refine-zone imol chain-id resno resno inscode)
               (accept-regularizement)
               (auto-fit-best-rotamer resno \"\" inscode chain-id imol (imol-refinement-map) 1 0.1)
               (refine-zone imol chain-id resno resno inscode)
               (accept-regularizement)
               (info-dialog info)
            (if (= replacement-state 0)
                (set-refinement-immediate-replacement 0))
            (if (= backup-mode 1)
                (turn-on-backup imol))
            ; (make-backup imol) ; for now
            )))
          (buttons 
      (list
   
       ;; Generate this code/text
   ";
   print BUTTONSOUT $coot_button_commands;

   print BUTTONSOUT "))";
   print BUTTONSOUT " \n
          ;; end of generated code/text (back to boiler plate)
          )
     
     (dialog-box-of-button-triples 0 \"Val, Thr, Ser, Cys, Leu, and Arg Fixes\" (cons 355 550) buttons \"  Close \"))
   
   
   ;; Has the map to refine against been set? (We put it here to make it happen last and appear \"on top\"). 
   (let ((imol-map (imol-refinement-map)))
     (if (= -1 imol-map)
         (show-select-map-dialog)))
   ";
   
   close BUTTONSOUT;
   open BUTTONSOUT, "<$buttonsout";
   open TEMPBUTTONSOUT, ">$temp_data/tempbuttonsout";
   while ($line = <BUTTONSOUT>) {
      if ($line =~ m/Rotamer/ || $line =~ m/CHI/) {
         ($substr) = $line =~ m/(\".*\")/;
         $substr =~ s/ /\_/g;
         $line="\t\t".$substr."\n";
      }
      print TEMPBUTTONSOUT $line;
   }    
   close TEMPBUTTONSOUT;
   system ("mv $temp_data/tempbuttonsout ".$buttonsout);
}       
#}}}########################################################################
        
#{{{ Handle command line arguments
############################################################################
        
if($#ARGV < 4) {  
   &help;
   exit(0);
}       

$nflag = 0;
$decoy_only = 0;
$rotamericityCutoff = 6; 
for ($i=0; $i<=(scalar(@ARGV)-3); $i++) {
  if ($ARGV[$i] =~ m/^-h/i) {
      &help;
      exit(0);
  }
  elsif ($ARGV[$i] =~ m/^-c/i) {
      &changes;
      exit(0);
  }
  elsif ($ARGV[$i] =~ m/^-v/i) {
      &version;
      exit(0);
  }  
  elsif ($ARGV[$i] =~ m /^-d/i) { 
     $decoy_only = 1;
     $nflag++;
  }
  elsif ($ARGV[$i] =~ m /^-r/i) { 
     $rotamericityCutoff = $ARGV[$i+1];
     $nflag += 2;
  }  
}
        
$initial_model = $ARGV[0+$nflag];
chomp($initial_model); 

$modelID = substr (basename($initial_model), 0, length(basename($initial_model))-4);

if ($ARGV[1+$nflag] =~ m/mtz/) { $mtz=$ARGV[1+$nflag]; }
if ($ARGV[1+$nflag] =~ m/map/) { $map=$ARGV[1+$nflag]; }

$temp_data    = $ARGV[2+$nflag];
$MP_BASE_DIR  = $ARGV[3+$nflag];
$modelOutpath = $ARGV[4+$nflag];
chomp($modelOutpath); 

#}}}########################################################################
        
#{{{ sequence-map - Creating a table of adjecent residues stuff TODO
############################################################################
        
# create a sequence map
# use this to make a hash of adjacent neighbors for RAMA analysis
# TODO deal with alts, ins, chain breaks, etc.... 
        
open FORMAT_SEQ, ">$temp_data/temp.seq";
open IN, "<$initial_model"; 
$count=1;
while ($pdb_line=<IN>) {
    if (substr($pdb_line, 0,4) eq ATOM || substr($pdb_line, 0,6) eq HETATM) {
    $alt=substr($pdb_line, 16,1);
        $resn=substr($pdb_line, 17,3);
        $chain=substr($pdb_line,21,1);
        $resid=substr($pdb_line,22,4);
        $ins=substr($pdb_line,26,1);
        $key="$chain$resid$ins$resn";
        if (!defined $pdb_hash{$key}) {
            $pdb_hash{$key}=$count;
            $new_seq=$count;
            write FORMAT_SEQ;
            $count++;
        }
    }   
}       
close FORMAT_SEQ;
        
open SEQ, "<$temp_data/temp.seq";
%seq_hash=();
while ($seq_line=<SEQ>) {
        ($index, $linear_seq)=split(":", $seq_line);
   $linear_seq += 0; 
        $seq_hash{$linear_seq}= $index;
}       
close SEQ;
        
%neighbor_hash=();
for ($i=0; $i <= scalar keys (%seq_hash); $i++) {
   $index = $seq_hash{$i};
   $neighbor_hash{$index} = $seq_hash{$i-1}.":".$seq_hash{$i+1}; 
}       
#}}}########################################################################
        
#{{{ Prep for Coot FIRST PASS
############################################################################
        
# run commands to get stats for the initial .pdb 
#-----------------------------------------------------------------------
system ("java -Xmx256m -cp ".$MP_BASE_DIR."/lib/chiropraxis.jar chiropraxis.dangle.Dangle \"dist dist1 i _CA_, i _CA_\" ".$initial_model." > $temp_data/temp.dngl"); 
system ("java -Xmx256m -cp ".$MP_BASE_DIR."/lib/hless.jar hless.Ramachandran -raw -nokin ".$initial_model." > $temp_data/temp.rama"); 
system ("java -Xmx256m -cp ".$MP_BASE_DIR."/lib/chiropraxis.jar chiropraxis.rotarama.Rotalyze  ".$initial_model." > $temp_data/temp.rota");
system ($MP_BASE_DIR."/bin/macosx/prekin -cbdevdump ".$initial_model." > $temp_data/temp.cb");
system ($MP_BASE_DIR."/bin/quick_clashscore.pl ".$initial_model." 0.4 > $temp_data/temp.clash"); 
# system ("phenix.elbow --do-all ".$initial_model);
# $cif = $temp_data."/".$modelID.".cif"; 
# $phenix_mtz1 = $temp_data."/".$modelID."_phenix1.mtz";
# system ("mv elbow.".$modelID."_pdb.all.001.cif ".$cif); 
# system ("rm elbow.*"); 
# system ("phenix.refine --overwrite ".$mtz." ".$initial_model." ".$cif." main.number_of_macro_cycles=0 main.bulk_solvent_and_scale=false export_final_f_model=mtz refinement.input.xray_data.r_free_flags.generate=True"); 
# system ("mv ".$modelID."_refine_001_map_coeffs.mtz ".$phenix_mtz1); 
# system ("rm *_refine_00* *_refine_data.mtz"); 
# system ("iotbx.python ".$MP_BASE_DIR."/bin/pdb_map_correlation.py ".$phenix_mtz1." ".$initial_model." > $temp_data/temp.rsc");
system ($MP_BASE_DIR."/bin/iotbx.python ".$MP_BASE_DIR."/bin/pdb_map_correlation.py ".$mtz." ".$initial_model." > $temp_data/temp.rsc");

        
# sort stats for the initial .pdb 
#-----------------------------------------------------------------------
        
# Use Dangle output to put residue names into a hashes
%quality_score_hash=();
open DNGL, "<$temp_data/temp.dngl";
while ($line =<DNGL>) {
   if ($line !~ m/^#/) {
      # label:model:chain:number:ins:type:dist1
      ($label, $model, $chain, $resid, $ins, $resn, $d1) = split(":", $line); 
      $cnit = $chain.$resid.$ins.$resn;
      $quality_score_hash{$cnit};
      $quality_score_hash{$cnit}->{orig_clash}  =" 0.000";
      $quality_score_hash{$cnit}->{fixed_clash} =" 0.000";
      $quality_score_hash{$cnit}->{diff_rama}  = "   0.0";
      $quality_score_hash{$cnit}->{diff_rota}  = "   0.0";
      $quality_score_hash{$cnit}->{diff_cb}    = "      ";
      $quality_score_hash{$cnit}->{diff_clash} = "      ";
      $quality_score_hash{$cnit}->{diff_chis}  = "      :      :      :      ";
      $quality_score_hash{$cnit}->{diff_guans} = "      ";
   }    
}       
close DNGL; 
        
# Use chiropraxis.rotarama.Ramachandran output to put original Rama scores into the hash
open RAMA, "<$temp_data/temp.rama";
while ($line = <RAMA>) {
    if ($line !~ m/^#/) {
        #residue:score%:phi:psi:evaluation:type
        ($cnit, $score, $phi, $psi, $eval, $type)=split(":", $line);
        $quality_score_hash{$cnit}->{orig_rama} = $score + 0;
    }   
}       
close RAMA;
        
# Use chiropraxis.rotarama(or hless?).Rotalyze output to put rotamericity scores into the hash
open ROTA, "<$temp_data/temp.rota";
while ($line = <ROTA>) {
    if ($line !~ m/^#/) {
        #residue:score%:chi1:chi2:chi3:chi4:rotamer
        ($cnit, $score, $chi1, $chi2, $chi3, $chi4, $rotamer)=split(":", $line);
        $resn = substr($cnit, 6, 3);
        $orig_chis = sprintf ("%6.1f:%6.1f:%6.1f:%6.1f", $chi1, $chi2, $chi3, $chi4);
        $orig_chis =~ s/   0.0/      /g; 
        $quality_score_hash{$cnit}->{orig_chis} = $orig_chis;
        $score+=0;
        chomp($rotamer); 
        $rota_score = $score."(".$rotamer.")";
        $quality_score_hash{$cnit}->{orig_rota} = $rota_score;

        if($score <= 1.0)
        {
           if ($resn =~ m/LEU/)
           {
              if ( ($chi1 > 230  &&  $chi1 < 300)  &&
                   ($chi2 > 300  ||  $chi2 < 60) )
              {
                 $quality_score_hash{$cnit}->{decoy} = "Likely decoy try mt";
              }
              if ( ($chi1 > 120  &&  $chi1 < 180)  &&
                   ($chi2 > 300  ||  $chi2 < 60) )
              {
                 $quality_score_hash{$cnit}->{decoy} = "Likely decoy try tt";
              }
              if ( ($chi1 > 180  &&  $chi1 < 250)  &&
                   ($chi2 > 150  &&  $chi2 < 270) )
              {
                 $quality_score_hash{$cnit}->{decoy} = "Likely decoy try tp";
              }
              if ( ($chi1 > 260  &&  $chi1 < 360)  &&
                   ($chi2 > 180  &&  $chi2 < 300) )
              {
                 $quality_score_hash{$cnit}->{decoy} = "Likely decoy try mp";
              }
           }
           elsif ($resn =~ m/VAL/ || $resn =~ m/THR/ || $resn =~ m/SER/ || $resn =~ m/CYS/)
           {
              if ($chi1 > 330  ||  $chi1 <  30)  { $quality_score_hash{$cnit}->{decoy} = "Likely decoy try t"; }
              if ($chi1 >  90  &&  $chi1 < 150)  { $quality_score_hash{$cnit}->{decoy} = "Likely decoy try m"; }
              if ($chi1 > 210  &&  $chi1 < 270)  { $quality_score_hash{$cnit}->{decoy} = "Likely decoy try p"; }
           }
        }
    }   
}       
close ROTA;
        
# Use prekin output to put Cbeta dev scores into the hash
open CBETA, "<$temp_data/temp.cb"; 
$line = <CBETA>;
while ($line = <CBETA>) {
   if ($line !~ m/^#/) {
      #pdb:alt:res:chainID:resnum:dev:dihedralNABB:Occ:ALT:
        ($pdb, $alt, $resn, $tmp_chain, $tmp_resid, $dev, $dihedral, $occ, $ALT) = split(":", $line);
        chomp($alt); 
        $resid  = substr($tmp_resid, 0,4); 
        $ins    = substr($tmp_resid, 4,1);
        $chain  = substr($tmp_chain, 1,1); 
        $cnit = $chain.$resid.$ins.$resn;
        $cnit =~ tr/[a-z]/[A-Z]/;  
        if ($dev > 0.25) { $score=0; } # this CB is an outlier
        else { $score = 1; }
        $cb_score = $score;
        $quality_score_hash{$cnit}->{orig_cb} = $cb_score;        
    }   
}       
close CBETA; 
        
# Use clashscore.pl(?) output to put clash scores into the hash
open CLASH, "<$temp_data/temp.clash";
$line = <CLASH>;
while ($line = <CLASH>) {
    if ($line !~ m/^#/) {
        # residue   :clash     :score :mc_wc:mc_cc:mc_so:mc_bo:mc_hb:sc_wc:sc_cc:sc_so:sc_bo:sc_hb:
        # A   2 LYS:    -0.521  two character chain ids are causing a problem here
        ($tmp_cnit, $clashscore, $probescore, $mc_wc, $mc_cc, $mc_so, $mc_bo, $mc_hb, $sc_wc, $sc_cc, $sc_so, $sc_bo, $sc_hb) = split(":", $line);
        $cnit = substr($tmp_cnit, 1, length($tmp_cnit)-1); 
        chomp($sc_hb);
        $clashscore += 0;
        $probescore += 0;
        $sc_hb      += 0;
        $quality_score_hash{$cnit}->{orig_clash} = $clashscore;        
        $quality_score_hash{$cnit}->{orig_probe} = $probescore;
        $quality_score_hash{$cnit}->{orig_hb}    = $sc_hb;
    }   
}       
close CLASH;

# Use iotbx.python and pdb_map_correlation.py to get Real-space CC put these into the hash
open RSC, "<$temp_data/temp.rsc";
$line = <RSC>;
$in_header = 1; 
while ($line = <RSC>) {
    if ($in_header) {
       if ($line !~ m/^chain/) { next; }
       else { $in_header = 0; }
    }
    elsif ($line =~ m/^time/) {
       while ($line = <RSC>) {
          if ($line =~ m/mean/) {
            #  mean: 0.823939
             $mean = substr($line, 8,8); 
             $quality_score_hash{orig_rsc_mean} = $mean +0; 
             break; 
          }
       }
       break; 
    }
    #chain, residue, correlation, number of contributing grid points
    #A MET   1  0.8876  1503

    $chain = substr($line, 0,1);
    $resid = substr($line, 5,4); 
    $ins   = substr($line, 9,1);
    $resn  = substr($line, 2,3);
    $rsc   = substr($line, 11,6);
    $cnit  = $chain.$resid.$ins.$resn; 
    if (defined $quality_score_hash{$cnit}) {
       $rsc += 0;
       $quality_score_hash{$cnit}->{orig_rsc} = $rsc;
    }
}
close RSC;
        
# Set up coot script
        
# Set up hash of to get x,y,z coords of each potentially flipped residue's "terminal" atom 
open PDBIN, "<$initial_model";
%c_bgz_hash=(); 
while ($line = <PDBIN> ) {
   if (substr($line, 0,6) eq "ATOM  " || substr($line, 0,6) eq "HETATM"){
      $atom_name = substr($line, 12, 4);
      $resn      = substr($line, 17, 3);
      $chain     = substr($line, 21, 1);
      $resid     = substr($line, 22, 4);
      $ins       = substr($line, 26, 1);
      $x_coor    = substr($line, 30, 8);  
      $y_coor    = substr($line, 38, 8); 
      $z_coor    = substr($line, 46, 8);
    # if ((($resn eq "VAL" || $resn eq "THR" || $resn eq "SER" || $resn eq "CYS") && $atom_name eq " CB ") || 
      if ((($resn eq "VAL" || $resn eq "THR" || $resn eq "SER" || $resn eq "CYS" || $resn eq "ILE") && $atom_name eq " CB ") ||
            (($resn eq "LEU") && $atom_name eq " CG ") || ($resn eq "ARG" && $atom_name eq " NE ")) {
         $c_bgz_key=$chain.$resid.$ins; 
         $c_bgz_hash{$c_bgz_key}=$x_coor.":".$y_coor.":".$z_coor;
      } 
   }    
}       
close PDBIN;
        
# Start writing Coot commands to a Coot script file
# Do button script body here also
open ROTA, "<$temp_data/temp.rota";                                        
        
        
# within molprobity coot has problems reading the .scm script
# to circumvent this problem we put our scheme file into the startup 
# directory for coot 
        
$coot_script = "/sw/share/coot/scheme/bob_molprobity.scm"; 
$button_script_text = "";
        
open COOTSCRIPT1, ">$coot_script";
        
print COOTSCRIPT1 "(handle-read-draw-molecule-with-recenter \"".$initial_model."\" 0 )\n";
        
if    (defined $initial_model && defined $map) { print COOTSCRIPT1 "(auto-read-make-and-draw-maps \"".$map."\")\n"; }
elsif (defined $initial_model && defined $mtz) { print COOTSCRIPT1 "(auto-read-make-and-draw-maps \"".$mtz."\")\n"; }
print COOTSCRIPT1 "(copy-molecule 0)\n";
print COOTSCRIPT1 "(set-imol-refinement-map 1)\n";
print COOTSCRIPT1 "(set-refinement-immediate-replacement 1)\n\n"; 
        
if (!$decoy_only) {
   while ($line=<ROTA>) {
      if ($line =~ m/^#/) {
         next;
      }        
      if ($line =~ m/VAL/ || $line =~ m/THR/ || $line =~ m/SER/ || $line =~ m/CYS/ ||
          $line =~ m/LEU/ || $line =~ m/ILE/ || $line =~m/ARG/) {         
         #residue:score%:chi1:chi2:chi3:chi4:rotamer
         ($cnit, $score, $chi1, $chi2, $chi3, $chi4, $rotamer)=split(":", $line);
         $chain = substr($cnit, 0,1); 
         $resid = substr($cnit, 1,4);
         $ins   = substr($cnit, 5,1);
         $resn  = substr($cnit, 6,3); 
         $score+=0;
         chomp($rotamer);
            
         $c_bgz_key=$chain.$resid.$ins;
         $coords=$c_bgz_hash{$c_bgz_key};
         ($x, $y, $z)=split(":", $coords);
         if ($alt eq " ") { $alt=""; }
         if ($ins eq " ") { $inscoot=""; } 
           
         $coot_script_commands  = "(set-rotation-centre ".$x." ".$y." ".$z.")\n"; 
         $coot_script_commands .= "(refine-zone 0 \"".$chain."\" ".$resid." ".$resid." \"".$inscoot."\")\n"; 
         $coot_script_commands .= "(accept-regularizement)\n"; 
         $coot_script_commands .= "(auto-fit-best-rotamer ".$resid." \"".$alt."\" \"".$inscoot."\" \"".$chain."\" 0 1 1 0.1)\n"; 
         $coot_script_commands .= "(refine-zone 0 \"".$chain."\" ".$resid." ".$resid." \"".$inscoot."\")\n"; 
         $coot_script_commands .= "(accept-regularizement)\n\n";   
              
         $info="\"You have fixed ".$resn." ".$chain.$resid.$ins."\\nif you like this fix GREAT\! \\notherwise click Undo Fix\"";
      
         $button_label1_tmp = "BAD Rotamer ".$score." in ".$chain." ".$resn.$resid.$ins;
         $padding1          = int((41-length($button_label1_tmp))/2);
         $space1            = sprintf("%".$padding1."s");
         $button_label1_pad = $space1.$button_label1_tmp.$space1; 
         $button_label1     = sprintf("%41s", $button_label1_pad); 
         $button_label1     =~ s/\s/_/g;
      
         $button_label1_formatted = "\t\t\"".$button_label1."\"\n";
      
         $button_label2_tmp = "BORDERLINE Rotamer ".$score." in ".$chain." ".$resn.$resid.$ins; 
         $padding2          = int((41-length($button_label2_tmp))/2);
         $space2            = sprintf("%".$padding2."s");
         $button_label2_pad = $space2.$button_label2_tmp.$space2;    
         $button_label2     = sprintf("%41s", $button_label2_pad);
         $button_label2     =~ s/\s/_/g;
      
         $button_label2_formatted = "\t\t\"".$button_label2."\"\n";
           
         $button_command1  = "\t(list  \"\"\n"; 
         $button_command1 .= "\t\t(lambda (imol)\n";
         $button_command1 .= "\t\t(set-rotation-centre ".$x." ".$y." ".$z."))\n";
            
         $button_command2  = "\t\t(lambda (imol)\n";
         $button_command2 .= "\t\t(set-rotation-centre ".$x." ".$y." ".$z."))\n";
         $button_command2 .= "\t\t\"\"\n";
         $button_command2 .= "\t\t(lambda (imol)\n";
         $button_command2 .= "\t\t(set-rotation-centre ".$x." ".$y." ".$z.")))\n\n";
         $button_command2 .= "\t(list  \"   Center At Above   \"\n";
         $button_command2 .= "\t\t(lambda (imol)\n";
         $button_command2 .= "\t\t(set-rotation-centre ".$x." ".$y." ".$z."))\n";
         $button_command2 .= "\t\t\"    Fix Above   \"\n";
         $button_command2 .= "\t\t(lambda (imol)\n";
         $button_command2 .= "\t\t(action-func imol \"$chain\" $resid \"$inscoot\" $x $y $z $info))\n";
         $button_command2 .= "\t\t\"   Undo  Fix    \"\n";
         $button_command2 .= "\t\t(lambda (imol)\n";
         $button_command2 .= "\t\t(set-undo-molecule imol)\n\t\t(apply-undo)\n\t\t(apply-undo)\n\t\t(apply-undo)))\n\n";
           
         if ($score < $rotamericityCutoff) {
            $button_script_text .= $button_command1; 
            if ( $score <= 1.0 ) {
               print COOTSCRIPT1 "   ;;BAD Rotamer ".$score." in ".$chain." ".$resn.$resid.$ins."\n";
               $button_script_text .= $button_label1_formatted;
            }
            else {     
               print COOTSCRIPT1 "   ;;BORDERLINE Rotamer ".$score." in ".$chain." ".$resn.$resid.$ins."\n";
               $button_script_text .= $button_label2_formatted;
            }                     
            print COOTSCRIPT1 $coot_script_commands;
            $button_script_text .= $button_command2;
         }                                                                                                         
                                                   
         elsif ($line =~ m/VAL/ || $line =~ m/THR/ || $line =~ m/SER/ || $line =~ m/CYS/ || $line =~ m/ILE/) {
            
            if ( (80 < $chi1 && $chi1 < 160) || (-160 < $chi1 && $chi1 < -80) || (-40 < $chi1 && $chi1 < 40) ) {
               $button_script_text .= $button_command1;
               if ( (100 < $chi1 && $chi1 < 140) || (-140 < $chi1 && $chi1 < -100) || (-20 < $chi1 && $chi1 < 20) ) {
                  print COOTSCRIPT1 "   ;;BAD CHI1 ".$chi1." in ".$chain." ".$resn.$resid.$ins."\n"; 
                  $button_script_text .= $button_label1_formatted; 
               }
               else {    
                  print COOTSCRIPT1 "   ;;BORDERLINE CHI1 ".$chi1." in ".$chain." ".$resn.$resid.$ins."\n";
                  $button_script_text .= $button_label2_formatted; 
           
               }
               print COOTSCRIPT1 $coot_script_commands;
               $button_script_text .= $button_command2;
            }
         } 
           
         elsif ($line =~ m/LEU/) {
              
            if ( (80 < $chi2 && $chi2 < 160) || (-160 < $chi2 && $chi2 < -80) || (-40 < $chi2 && $chi2 < 40) ) {
               $button_script_text .= $button_command1;
               if ( (100 < $chi2 && $chi2 < 140) || (-140 < $chi2 && $chi2 < -100) || (-20 < $chi2 && $chi2 < 20) ) {
                  print COOTSCRIPT1 "   ;;BAD CHI2 ".$chi2." in ".$chain." ".$resn.$resid.$ins."\n";
                  $button_script_text .= $button_label1_formatted; 
               }
               else {
                  print COOTSCRIPT1 "   ;;BORDERLINE CHI2 ".$chi2." in ".$chain." ".$resn.$resid.$ins."\n";
                  $button_script_text .= $button_label2_formatted; 
               }
               print COOTSCRIPT1 $coot_script_commands;
               $button_script_text .= $button_command2;
            }
         } 
           
         elsif ($line =~ m/ARG/) {
            #elsif ( (80 < $chi1 && $chi1 < 160) || (-160 < $chi1 && $chi1 < -80) || (-40 < $chi1 && $chi1 < 40) ) {
            #   if ( (100 < $chi1 && $chi1 < 140) || (-140 < $chi1 && $chi1 < -100) || (-20 < $chi1 && $chi1 < 20) ) {
            #      print COOTSCRIPT1 "   ;;BAD CHI1 ".$chi1." in ".$chain." ".$resn.$resid.$ins."\n"; 
            #   }
            #   else { 
            #      print COOTSCRIPT1 "   ;;BORDERLINE CHI1 ".$chi1." in ".$chain." ".$resn.$resid.$ins."\n";
            #   }
            #}
         } 
      }    
   }
}
        
elsif($decoy_only) { 
   while ($line=<ROTA>) {
      if ($line =~ m/^#/) {
         next;
      }    
      if ($line =~ m/VAL/ || $line =~ m/THR/ || $line =~ m/SER/ || $line =~ m/CYS/ ||
          $line =~ m/LEU/ || $line =~ m/ILE/ || $line =~m/ARG/) {  # no ARG decoys yet
         #residue:score%:chi1:chi2:chi3:chi4:rotamer
         ($cnit, $score, $chi1, $chi2, $chi3, $chi4, $rotamer)=split(":", $line);
         $chain = substr($cnit, 0,1); 
         $resid = substr($cnit, 1,4);
         $ins   = substr($cnit, 5,1);
         $resn  = substr($cnit, 6,3); 
         $score+=0;
         chomp($rotamer);
            
         $c_bgz_key=$chain.$resid.$ins;
         $coords=$c_bgz_hash{$c_bgz_key};
         ($x, $y, $z)=split(":", $coords);
         if ($alt eq " ") { $alt=""; }
         if ($ins eq " ") { $inscoot=""; } 
   
         $coot_script_commands  = ";;DECOY Rotamer ".$score." in ".$chain." ".$resn.$resid.$ins."\n";
         $coot_script_commands .= "(set-rotation-centre ".$x." ".$y." ".$z.")\n"; 
         $coot_script_commands .= "(refine-zone 0 \"".$chain."\" ".$resid." ".$resid." \"".$inscoot."\")\n"; 
         $coot_script_commands .= "(accept-regularizement)\n"; 
         $coot_script_commands .= "(auto-fit-best-rotamer ".$resid." \"".$alt."\" \"".$inscoot."\" \"".$chain."\" 0 1 1 0.1)\n"; 
         $coot_script_commands .= "(refine-zone 0 \"".$chain."\" ".$resid." ".$resid." \"".$inscoot."\")\n"; 
         $coot_script_commands .= "(accept-regularizement)\n\n";   
              
         $info="\"You have fixed ".$resn." ".$chain.$resid.$ins."\\nif you like this fix GREAT\! \\notherwise click Undo Fix\"";
   
         $button_label_tmp = "DECOY Rotamer ".$score." in ".$chain." ".$resn.$resid.$ins;
         $padding          = int((41-length($button_label_tmp))/2);
         $space            = sprintf("%".$padding."s");
         $button_label_pad = $space.$button_label_tmp.$space;
         $button_label     = sprintf("%41s", $button_label_pad);
         $button_label     =~ s/\s/_/g;
   
         $button_label_formatted = "\t\t\"".$button_label."\"\n";
   
         $button_command  = "\t(list  \"\"\n"; 
         $button_command .= "\t\t(lambda (imol)\n";
         $button_command .= "\t\t(set-rotation-centre ".$x." ".$y." ".$z."))\n";
         $button_command .= $button_label_formatted; 
         $button_command .= "\t\t(lambda (imol)\n";
         $button_command .= "\t\t(set-rotation-centre ".$x." ".$y." ".$z."))\n";
         $button_command .= "\t\t\"\"\n";
         $button_command .= "\t\t(lambda (imol)\n";
         $button_command .= "\t\t(set-rotation-centre ".$x." ".$y." ".$z.")))\n\n";
         $button_command .= "\t(list  \"   Center At Above   \"\n";
         $button_command .= "\t\t(lambda (imol)\n";
         $button_command .= "\t\t(set-rotation-centre ".$x." ".$y." ".$z."))\n";
         $button_command .= "\t\t\"    Fix Above   \"\n";
         $button_command .= "\t\t(lambda (imol)\n";
         $button_command .= "\t\t(action-func imol \"$chain\" $resid \"$inscoot\" $x $y $z $info))\n";
         $button_command .= "\t\t\"   Undo  Fix    \"\n";
         $button_command .= "\t\t(lambda (imol)\n";
         $button_command .= "\t\t(set-undo-molecule imol)\n\t\t(apply-undo)\n\t\t(apply-undo)\n\t\t(apply-undo)))\n\n";
   
         if (defined($quality_score_hash{$cnit}->{decoy})) {
            print COOTSCRIPT1 $coot_script_commands;
            $button_script_text .= $button_command;
         }
      }    
   }       
}        
close ROTA; 
        
print COOTSCRIPT1 "(save-coordinates 0 \"$temp_data/pdbtmp1.pdb\")\n"; 
print COOTSCRIPT1 "(coot-real-exit 1)\n"; 
        
close COOTSCRIPT1; 
        
# Call the buttons sub-routine and make the buttons GUI
&buttons($button_script_text);
#}}}#########################################################################--------------------------------------------------------------------------------------
        
#{{{ Run Coot and store results for the FIRST PASS
############################################################################
        
# run coot to try all the fixes
#-----------------------------------------------------------------------
system ("coot --no-guano -s ".$coot_script." --no-graphics");            
            
open SCRIPT, "<$coot_script"; 
%script_hash=(); 
while ($line=<SCRIPT>) {
   # ;;DECOY Rotamer 0.3 in B LEU 175
   if ($line =~ m/DECOY/ || $line =~ m/BAD/ || $line =~ m/BORDERLINE/) { 
      chomp($line);
      $key = substr($line, length($line)-10, 10); 
      $line=$line."\n"; 
      for ($i=0; $i<7; $i++) {
         $hash_val=$hash_val.$line; 
         $line=<SCRIPT>; 
      } 
   }    
   else { next; }
   $script_hash{$key}=$hash_val."\n"; 
   $hash_val=""; 
}       
close SCRIPT; 
        
#}}}########################################################################
        
#{{{ Analysis of structure SECOND PASS
############################################################################
        
# run commands to get stats for the "fixed" .pdb
#-----------------------------------------------------------------------
$initial_model1 = "$temp_data/pdbtmp1.pdb"; 
system ("java -Xmx256m -cp ".$MP_BASE_DIR."/lib/hless.jar hless.Ramachandran -raw -nokin ".$initial_model1." > $temp_data/temp.rama");
system ("java -Xmx256m -cp ".$MP_BASE_DIR."/lib/chiropraxis.jar chiropraxis.rotarama.Rotalyze  ".$initial_model1." > $temp_data/temp.rota2"); 
system ($MP_BASE_DIR."/bin/macosx/prekin -cbdevdump ".$initial_model1." > $temp_data/temp.cb");
system ("quick_clashscore.pl ".$initial_model1." 0.4 > $temp_data/temp.clash");
system ("diff -y --suppress-common-lines $temp_data/temp.rota $temp_data/temp.rota2 > $temp_data/temp.rotated");
# $phenix_mtz2 = $temp_data."/".$modelID."_phenix2.mtz";
# system ("phenix.refine --overwrite ".$mtz." ".$initial_model1." ".$cif." main.number_of_macro_cycles=0 main.bulk_solvent_and_scale=false export_final_f_model=mtz refinement.input.xray_data.r_free_flags.generate=True");
# system ("mv pdbtmp1_refine_001_map_coeffs.mtz ".$phenix_mtz2);
# system ("rm *_refine_00* *_refine_data.mtz");  
# system ("iotbx.python ".$MP_BASE_DIR."/bin/pdb_map_correlation.py ".$phenix_mtz2." ".$initial_model1." > $temp_data/temp.rsc1");
system ($MP_BASE_DIR."/bin/iotbx.python ".$MP_BASE_DIR."/bin/pdb_map_correlation.py ".$mtz." ".$initial_model1." > $temp_data/temp.rsc1");

# then run Java program to make $temp_data/temp.guans
system ("echo 'Running Java flip-confirming program...'");
system ("java -Xmx256m -cp ".$MP_BASE_DIR."/lib/cmdline.jar cmdline.ArgFlipConfirmer ".$initial_model." ".$initial_model1." > $temp_data/temp.guans");
        
# sort stats for the "fixed" .pdb
#-----------------------------------------------------------------------
        
%rama_hash=(); 
open RAMA, "<$temp_data/temp.rama";
while ($line = <RAMA>) {
   if ($line !~ m/^#/) {   
      #residue:score%:phi:psi:evaluation:type
      ($cnit, $score, $phi, $psi, $eval, $type)=split(":", $line);
      $quality_score_hash{$cnit}->{fixed_rama} = $score+=0; 
      $rama_hash{$cnit} = $score-$quality_score_hash{$cnit}->{orig_rama};
   }   
}       
close RAMA;
        
# run the RAMA file again to get information about the neighboring residues
#-----------------------------------------------------------------------
open RAMA, "<$temp_data/temp.rama";
while ($line = <RAMA>) {
   if ($line !~ m/^#/) {
      #residue:score%:phi:psi:evaluation:type
        ($cnit, $score, $phi, $psi, $eval, $type)=split(":", $line);
        ($i_minus_res, $i_plus_res)=split(":", $neighbor_hash{$cnit});
        $rama_diff_score_tmp = $rama_hash{$cnit} + $rama_hash{$i_minus_res} + $rama_hash{$i_plus_res};
        $quality_score_hash{$cnit}->{diff_rama} = sprintf("%6.1f", $rama_diff_score_tmp);
   }    
}       
close RAMA; 
#-----------------------------------------------------------------------
        
open ROTA, "<$temp_data/temp.rota2";
while ($line = <ROTA>) {
    if ($line !~ m/^#/) {
       #residue:score%:chi1:chi2:chi3:chi4:rotamer
       ($cnit, $score, $chi1, $chi2, $chi3, $chi4, $rotamer)=split(":", $line);
       $fixed_chis = sprintf ("%6.1f:%6.1f:%6.1f:%6.1f", $chi1, $chi2, $chi3, $chi4);
       $fixed_chis =~ s/   0.0/      /g;
       $quality_score_hash{$cnit}->{fixed_chis} = $fixed_chis;
       $score+=0;
       chomp($rotamer); 
       $rota_score_fixed = $score."(".$rotamer.")";
       $quality_score_hash{$cnit}->{fixed_rota} = $rota_score_fixed;        
       ($orig_score, $orig_type) = split(/\(/, $quality_score_hash{$cnit}->{orig_rota}); 
       #$rota_diff_score = sprintf("%6.1f", ($rota_score-$score)); # straight difference
       if ($orig_score eq 0.0) {  # avoid divide by zero
          $rota_diff_score = sprintf("%6.1f", ($score-$orig_score)); 
       }
       else {   # fractional change
          $rota_diff_score = sprintf("%6.1f", (($score-$orig_score)/$orig_score)); 
       }
       $quality_score_hash{$cnit}->{diff_rota} =$rota_diff_score;
    }   
}                                                                                                            
close ROTA;
        
open CBETA, "<$temp_data/temp.cb";
$line = <CBETA>;
while ($line = <CBETA>) {
    if ($line !~ m/^#/) {
        #pdb:alt:res:chainID:resnum:dev:dihedralNABB:Occ:ALT:
        ($pdb, $alt, $resn, $tmp_chain, $tmp_resid, $dev, $dihedral, $occ, $ALT) = split(":", $line);
        chomp($alt);
        $resid = substr($tmp_resid, 0,4);
        $ins   = substr($tmp_resid, 4,1);
        $chain = substr($tmp_chain, 1,1); 
        $cnit = $chain.$resid.$ins.$resn;
        $cnit =~ tr/[a-z]/[A-Z]/;
        if ($dev > 0.25) { $score=0; } # this CB is an outlier
        else { $score = 1; }                 
        $quality_score_hash{$cnit}->{fixed_cb} = $score;
        $quality_score_hash{$cnit}->{diff_cb}  = sprintf("%6.1f", ($score-$quality_score_hash{$cnit}->{orig_cb}));
    }   
}       
close CBETA;
        
open CLASH, "<$temp_data/temp.clash";
$line = <CLASH>;
while ($line = <CLASH>) {
    if ($line !~ m/^#/) {
       # residue   :clash     :score :mc_wc:mc_cc:mc_so:mc_bo:mc_hb:sc_wc:sc_cc:sc_so:sc_bo:sc_hb:
       # A   2 LYS:    -0.521  two character chain ids are causing a problem here
       ($tmp_cnit, $clashscore, $probescore, $mc_wc, $mc_cc, $mc_so, $mc_bo, $mc_hb, $sc_wc, $sc_cc, $sc_so, $sc_bo, $sc_hb) = split(":", $line);
       $cnit = substr($tmp_cnit, 1, length($tmp_cnit)-1);
       chomp($sc_hb);
       $clashscore += 0;
       $probescore += 0;
       $sc_hb      += 0;
       $quality_score_hash{$cnit}->{fixed_clash} = $clashscore; 
       $quality_score_hash{$cnit}->{fixed_probe} = $probescore;
       $quality_score_hash{$cnit}->{fixed_hb}    = $sc_hb;
       $orig_clash_score = $quality_score_hash{$cnit}->{orig_clash};
       $orig_probe_score = $quality_score_hash{$cnit}->{orig_probe};
       $orig_hb_score    = $quality_score_hash{$cnit}->{orig_hb};
       if ($orig_clash_score =~ m/0.000/) { $orig_clash_score -= 0.4; }
       if ($clashscore == 0) { $score -= 0.4; }                                        
       $quality_score_hash{$cnit}->{diff_clash} = sprintf("%6.3f", ($clashscore-$orig_clash_score));
       $quality_score_hash{$cnit}->{diff_probe} = sprintf("%5.1f", ($probescore-$orig_probe_score)); 
       $quality_score_hash{$cnit}->{diff_hb}    = sprintf("%5.1f", ($sc_hb-$orig_hb_score)); 
    }   
}       
close CLASH;

open RSC, "<$temp_data/temp.rsc1";
$line = <RSC>;
$in_header = 1;
while ($line = <RSC>) {
    if ($in_header) {
       if ($line !~ m/^chain/) { next; }
       else { $in_header = 0; }
    }
    elsif ($line =~ m/^time/) {
       while ($line = <RSC>) {
          if ($line =~ m/mean/) {
            #  mean: 0.823939
             $mean = substr($line, 8,8);
             $quality_score_hash{fixed_rsc_mean} = $mean +0;
             break;
          }
       }
       break;
    }
    #chain, residue, correlation, number of contributing grid points
    #A MET   1  0.8876  1503

    $chain = substr($line, 0,1);
    $resid = substr($line, 5,4);
    $ins   = substr($line, 9,1);
    $resn  = substr($line, 2,3);
    $rsc   = substr($line, 11,6);
    $cnit  = $chain.$resid.$ins.$resn;
    if (defined $quality_score_hash{$cnit}) {
       $rsc += 0;
       $quality_score_hash{$cnit}->{fixed_rsc} = $rsc;
       $diff_rsc = $rsc - $quality_score_hash{$cnit}->{orig_rsc};
       $quality_score_hash{$cnit}->{diff_rsc}  = sprintf("%7.4f", $diff_rsc);
    }
}
close RSC;
        
%guan_angle_hash=(); 
open GUANS, "<$temp_data/temp.guans";
while ($line = <GUANS>) {
    if ($line !~ m/^#/) {   
       # cnit:guan_angle
       # A  19 ARG:0
       ($cnit, $guan_angle)=split(":", $line);
       $guan_angle += 0;
       $quality_score_hash{$cnit}->{diff_guans} = sprintf("%6.1f", $guan_angle);
    }                                               
}       
close GUANS;
        
open ROTATED, "<$temp_data/temp.rotated";
while ($line = <ROTATED>) {
    if ($line !~ m/^#/) {
        #A  27 LEU:0.2:-128.8:24.2:::OUTLIER                           | A  27 LEU:35.9:281.1:170.0:::mt
        ($old_rota, $new_rota) = split(/\|\t/, $line);
        ($cnit, $score, $chi1, $chi2, $chi3, $chi4, $rotamer)=split(":", $old_rota);
        ($cnit_1, $score_1, $chi1_1, $chi2_1, $chi3_1, $chi4_1, $rotamer_1)=split(":", $new_rota);
        
        $resn=substr($cnit, 6,3);        
        $score1 = $chi1_1-$chi1; 
        if ($score1 < 0 ) { $score1 *= -1;}
        if ($score1 > 180) { $score1=($score1%-360)*-1 };
        
        $score2 = $chi2_1-$chi2;
        if ($score2 < 0 ) { $score2 *= -1;}
        if ($score2 > 180) { $score2=($score2%-360)*-1};
        
        $score3 = $chi3_1-$chi3;
        if ($score3 < 0 ) { $score3 *= -1;}
        if ($score3 > 180) { $score3=($score3%-360)*-1};
        
        $score4 = $chi4_1-$chi4;
        if ($score4 < 0 ) { $score4 *= -1;}
        if ($score4 > 180) { $score4=($score4%-360)*-1};

        $diff_chis = sprintf ("%6.1f:%6.1f:%6.1f:%6.1f", $score1, $score2, $score3, $score4);
        $diff_chis =~ s/   0.0/      /g;
        $quality_score_hash{$cnit}->{diff_chis} = $diff_chis;        
        
        if ($line =~ m/VAL/ || $line =~ m/THR/ || $line =~ m/SER/ || $line =~ m/CYS/ || $line =~ m/ILE/) {
           $quality_score_hash{$cnit}->{diff_rotated} = sprintf("%6.1f", $score1);
        }
        elsif ($line =~ m/LEU/) { # should ILE be here?
           $quality_score_hash{$cnit}->{diff_rotated} = sprintf("%6.1f", $score2);
        }
        else {
           $quality_score_hash{$cnit}->{diff_rotated} = sprintf("%6.1f", 0);
        }
    }   
}       
close ROTATED; 
        
open ROTA, "<$temp_data/temp.rota";
        
while ($line=<ROTA>) {
   if ($line !~ m/^#/) {
      if ($line =~ m/VAL/ || $line =~ m/THR/ || $line =~ m/SER/ || $line =~ m/CYS/ || 
          $line =~ m/ARG/ || $line =~ m/LEU/ || $line =~ m/ILE/) {
#      if ($line =~ m/VAL/ || $line =~ m/THR/ || $line =~ m/LEU/ || $line =~ m/ARG/) {
         #residue:score%:chi1:chi2:chi3:chi4:rotamer
         ($cnit, $score, $chi1, $chi2, $chi3, $chi4, $rotamer)=split(":", $line);
         $chain = substr($cnit, 0, 1);
         $resid = substr($cnit, 1, 4);
         $ins   = substr($cnit, 5, 1);
         $resn  = substr($cnit, 6, 3);
         $score+=0;
         chomp($rotamer);
        
         # Criteria for not accepting flip
         $fixed_rama       = $quality_score_hash{$cnit}->{fixed_rama};
         $fixed_rota       = $quality_score_hash{$cnit}->{fixed_rota};
         $fixed_cb         = $quality_score_hash{$cnit}->{fixed_cb};
         $fixed_clash      = $quality_score_hash{$cnit}->{fixed_clash};
         
         $rama_diff_score       = $quality_score_hash{$cnit}->{diff_rama};
         $rota_diff_score       = $quality_score_hash{$cnit}->{diff_rota};
         $cb_diff_score         = $quality_score_hash{$cnit}->{diff_cb};
         $clash_diff_score      = $quality_score_hash{$cnit}->{diff_clash};
         $rotated_diff_score    = $quality_score_hash{$cnit}->{diff_rotated};
         $guan_angle_diff_score = $quality_score_hash{$cnit}->{diff_guans};
         $rsc_diff_score        = $quality_score_hash{$cnit}->{diff_rsc}; 

         $key = $chain." ".$resn.$resid.$ins;

         if ($fixed_rota =~ m/OUTLIER/ || $fixed_rama < 1 || $fixed_cb < 1) {
            if ($fixed_rota =~ m/OUTLIER/ && defined $script_hash{$key}) {
               $script_hash{$key}="  ;;could not fix ".$key." *fix* is a rotamer outlier!\n\n";
            }
            elsif ($fixed_rama < 1 && defined $script_hash{$key}) {
               $script_hash{$key}="  ;;could not fix ".$key." *fix* is a ramachandran outlier!\n\n";
            }
            elsif ($fixed_cb < 1 && defined $script_hash{$key}) {
               $script_hash{$key}="  ;;could not fix ".$key." *fix* is a C(beta) outlier!\n\n";
            }
         }
         
         elsif ($rota_diff_score < -0.1 || $rama_diff_score < -30 || $cb_diff_score < 0 || 
            $clash_diff_score < -0.1 || $rotated_diff_score < 10 || $rsc_diff_score < -0.0005 ||
            (($resn =~ m/ARG/) && ($guan_angle_diff_score > -150 && $guan_angle_diff_score <150)) ) {
         
            if ($rota_diff_score < -0.1 && defined $script_hash{$key}) {
               $script_hash{$key}="  ;;could not fix ".$key." rotamer got worse!\n\n";
            }
            elsif ($rama_diff_score < -30 && defined $script_hash{$key}) {
               $script_hash{$key}="  ;;could not fix ".$key." ramachandran got worse!\n\n";
            }
            elsif ($cb_diff_score < 0 && defined $script_hash{$key}) {
               $script_hash{$key}="  ;;could not fix ".$key." C(beta) deviation got worse!\n\n";
            }
            elsif ($clash_diff_score < -0.1 && defined $script_hash{$key}) {
               $script_hash{$key}="  ;;could not fix ".$key." clash score got worse!\n\n";
            }
            elsif ($rsc_diff_score < 0 && defined $script_hash{$key}) {
               $script_hash{$key}="  ;;could not fix ".$key." rsc score got worse!\n\n";
            }
            elsif ((($resn =~ m/ARG/) && ($guan_angle_diff_score > -150 && $guan_angle_diff_score < 150)) && defined $script_hash{$key}) {
               $script_hash{$key}="  ;;could not fix ".$key."Arg did not flip by 180+/-30deg!\n\n";
            }
            elsif ( $rotated_diff_score < 10 && defined $script_hash{$key} && $resn !~ m/ARG/) {
               if ($rota_diff_score < -0.1 || $rama_diff_score < 10) { # allows for density based bb fixes
                  $script_hash{$key}="  ;;".$key." did not significantly move \n\n";
               }
            }
         }
         elsif ($resn !~ m/LEU/ && defined $script_hash{$key}) {      # rmi 090724 only do Leu for first pass in Molprobity
            $script_hash{$key}="  ;;".$key." passed criteria but is not a Leucine. \n\n";
         }
      } 
   }    
}       
        
close ROTA; 
        
system ("cp ".$coot_script." ".$temp_data."/".$modelID."_coot_fix_VTLR.scm_all");
        
open COOTSCRIPT2, ">$coot_script"; 
#print COOTSCRIPT2 "(show-select-map-dialog)\n";
print COOTSCRIPT2 "(handle-read-draw-molecule-with-recenter \"".$initial_model."\" 0 )\n";

if (defined $initial_model && defined $map) {
   print COOTSCRIPT2 "(auto-read-make-and-draw-maps \"".$map."\")\n";
}           
elsif (defined $initial_model && defined $mtz) {
   print COOTSCRIPT2 "(auto-read-make-and-draw-maps \"".$mtz."\")\n";
}       

print COOTSCRIPT2 "(copy-molecule 0)\n";
print COOTSCRIPT2 "(set-imol-refinement-map 1)\n";
print COOTSCRIPT2 "(set-refinement-immediate-replacement 1)\n\n";
        
foreach $key (keys %script_hash) {
   $value = $script_hash{$key}; 
   print COOTSCRIPT2 $value; 
}       
        
print COOTSCRIPT2 "(save-coordinates 0 \"$temp_data/pdbtmp2.pdb\")\n";
print COOTSCRIPT2 "(coot-real-exit 1)\n";
        
close COOTSCRIPT2;
        
system ("cp ".$coot_script." ".$temp_data."/".$modelID."_coot_fix_VTLR.scm");

# run coot to with fixes that improve one or more quality score
#-----------------------------------------------------------------------
system ("coot --no-guano -s ".$coot_script." --no-graphics");                    
        
#finished second pass in coot 
#}}}########################################################################
        
#{{{ print various output
############################################################################
        
$stats=$temp_data."/".$modelID."_stats";
open STATS, ">$stats";
print STATS "#index:diff_rama:diff_rota:diff_cb:diff_clash:diff_rsc:diff_rotated:\t|:orig_rama:orig_rota:orig_cb:orig_clash:orig_rsc:place_holder:\t|:fixed_rama:fixed_rota:fixed_cb:fixed_clash:fixed_rsc:place_holder
# diff_rama is the sum of the ramachandran score differences for residues n-1, n and n+1   current cutoff is  > -30      is accepted
# diff_rota is the fractional change (old_rota_score-new_rota_score)/(old_rota_score)      current cutoff is  >  -0.1    is accepted
# diff_cb   is (new_cb-old_cb); cb is binned outliers ( >= 0.25 ) are designated as 0      current cutoff is  <   0      is rejected 
# diff_clash is the straight difference in clashscore score (greatest overlap per residue) current cutoff is  >  -0.1    is rejected
# diff_rsc  is the straight difference in rsc scores                                       current cutoff is  >  -0.0005 is accepted
# diff_rotated is the difference in CHI1 for V,T,S,C and CHI2 for L                        current cutoff is  >  10      is accepted 
#   smaller rotations are only excepted if diff_rama > 10 and diff_rota > -0.1 \n"; 
print STATS "#now, positive differences in rota and rama are good  * are fixed residues ^ are attempted\n"; 
print STATS "#
#----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------#
# DIFF    : rama : rota :  cb  : clash:probe:  hb :   rsc :rot'd1:rot'd2:rot'd3:rot'd4:guan_a:         FLIP DECISION          :   ORIG  : rama bin:  rota   (type) :  cb  : clash:probe:  hb :  rsc :chi1  :chi2  :chi3  :chi4  :     FIXED     : rama bin:  rota   (type) :  cb  : clash:probe:  hb :  rsc :chi1  :chi2  :chi3  :chi4  : #
#----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------#
";      
        
foreach $key (sort keys %quality_score_hash) {
   $cnit  = $key; 
   $chain = substr($cnit, 0,1);
   $resid = substr($cnit, 1,4);
   $ins   = substr($cnit, 5,1);
   $resn  = substr($cnit, 6,3);
   $script_hash_key = $chain." ".$resn.$resid.$ins;
   if ($script_hash{$script_hash_key} =~ m/DECOY/ || 
       $script_hash{$script_hash_key} =~ m/BAD/   || $script_hash{$script_hash_key} =~ m/BORDERLINE/) {
     $marked_cnit=$cnit."*";
     $quality_score_hash{$cnit}->{reject_reason} = "         FLIP ACCEPTED          ";
   }    
   elsif ($script_hash{$script_hash_key} =~ m/;;/) {
      ($place_holder, $tmp_reject_reason) = split("$script_hash_key", $script_hash{$script_hash_key});
      chomp($tmp_reject_reason);
      chomp($tmp_reject_reason);
      if ($tmp_reject_reason =~ m/passed criteria/) {
         $marked_cnit=$cnit."*";
         $quality_score_hash{$cnit}->{reject_reason} = "     [ALPHA TEST] ACCEPTED      ";
      } 
      else {
         $marked_cnit=$cnit."^";
         $s = length($tmp_reject_reason);
         $padding = int((32-$s)/2);
         $space = sprintf("%".$padding."s");
         $padded_reason = $space.$tmp_reject_reason.$space; 
         $quality_score_hash{$cnit}->{reject_reason} = sprintf("%32s",$padded_reason); 
      } 
   }    
   else { 
      $marked_cnit=$cnit." "; 
      $quality_score_hash{$cnit}->{reject_reason} = "                                ";
   }    
                
   $orig_rama  = $quality_score_hash{$cnit}->{orig_rama};
   $orig_rota  = $quality_score_hash{$cnit}->{orig_rota};
   $orig_cb    = $quality_score_hash{$cnit}->{orig_cb};
   $orig_clash = $quality_score_hash{$cnit}->{orig_clash};
   $orig_probe = $quality_score_hash{$cnit}->{orig_probe};
   $orig_hb    = $quality_score_hash{$cnit}->{orig_hb};
   $orig_rsc   = $quality_score_hash{$cnit}->{orig_rsc}; 
   $orig_chis  = $quality_score_hash{$cnit}->{orig_chis};
   if ($orig_rama < 0.2) { $binned_rama = 0; }
   elsif ($orig_rama < 2.0) { $binned_rama = 1; }
   else { $binned_rama = 2; }
   ($orig_rota_score, $orig_rota_type_tmp)=split(/\(/, $orig_rota); 
   $orig_rota_type="(".$orig_rota_type_tmp;
   if ($orig_rota_type eq "(") { $orig_rota_type=""; }
   $orig_values=sprintf("%6.1f(%d):%6.1f%10s:%6.1f:%6.3f:%5.1f:%5.1f:%6.4f:%27s", $orig_rama, $binned_rama,$orig_rota_score, $orig_rota_type, $orig_cb, $orig_clash, $orig_probe, $orig_hb, $orig_rsc, $orig_chis); 
       
   $fixed_rama  = $quality_score_hash{$cnit}->{fixed_rama};
   $fixed_rota  = $quality_score_hash{$cnit}->{fixed_rota};
   $fixed_cb    = $quality_score_hash{$cnit}->{fixed_cb};
   $fixed_clash = $quality_score_hash{$cnit}->{fixed_clash};
   $fixed_probe = $quality_score_hash{$cnit}->{fixed_probe};
   $fixed_hb    = $quality_score_hash{$cnit}->{fixed_hb};
   $fixed_rsc   = $quality_score_hash{$cnit}->{fixed_rsc}; 
   $fixed_chis  = $quality_score_hash{$cnit}->{fixed_chis};  
   if ($fixed_rama < 0.2) { $binned_rama = 0; }                             
   elsif ($fixed_rama < 2.0) { $binned_rama = 1; }
   else { $binned_rama = 2; }
   ($fixed_rota_score, $fixed_rota_type_tmp)=split(/\(/, $fixed_rota);
   $fixed_rota_type="(".$fixed_rota_type_tmp;
   if ($fixed_rota_type eq "(") { $fixed_rota_type=""; }
   $fixed_values=sprintf("%6.1f(%d):%6.1f%10s:%6.1f:%6.3f:%5.1f:%5.1f:%6.4f:%27s", $fixed_rama, $binned_rama, $fixed_rota_score, $fixed_rota_type, $fixed_cb, $fixed_clash, $fixed_probe, $fixed_hb, $fixed_rsc, $fixed_chis);
       
   if ( ($resn =~ m/GLY/ && defined($quality_score_hash{$cnit}->{diff_rota}) &&
      ( $quality_score_hash{$cnit}->{diff_rota}  !~ m/0.0/ || $quality_score_hash{$cnit}->{diff_rama}  !~ m/0.0/ ||
        $quality_score_hash{$cnit}->{diff_clash} !~ m/0.000/) ) ||
        ($resn !~ m/GLY/ && defined($quality_score_hash{$cnit}->{diff_rota}) && 
      ( $quality_score_hash{$cnit}->{diff_rota}  !~ m/0.0/ || $quality_score_hash{$cnit}->{diff_rama}  !~ m/0.0/ ||
        $quality_score_hash{$cnit}->{diff_cb} !~ m/0.0/ || $quality_score_hash{$cnit}->{diff_clash} !~ m/0.000/) ) ) {
      $diff_rama     = $quality_score_hash{$cnit}->{diff_rama};
      $diff_rota     = $quality_score_hash{$cnit}->{diff_rota};
      $diff_cb       = $quality_score_hash{$cnit}->{diff_cb};
      $diff_clash    = $quality_score_hash{$cnit}->{diff_clash};
      $diff_probe    = $quality_score_hash{$cnit}->{diff_probe};
      $diff_hb       = $quality_score_hash{$cnit}->{diff_hb};
      $diff_rsc      = $quality_score_hash{$cnit}->{diff_rsc}; 
      $diff_rotated  = $quality_score_hash{$cnit}->{diff_rotated}; # not output here
      $diff_chis     = $quality_score_hash{$cnit}->{diff_chis};  
      $diff_guans    = $quality_score_hash{$cnit}->{diff_guans};     
      $reject_reason = $quality_score_hash{$cnit}->{reject_reason};
      $diff_values = $diff_rama.":".$diff_rota.":".$diff_cb.":".$diff_clash.":".$diff_probe.":".$diff_hb.":".$diff_rsc.":".$diff_chis.":".$diff_guans.":".$reject_reason;
      print STATS $marked_cnit.":".$diff_values.":\t\t:".$orig_values.":\t\t:".$fixed_values.":\n";
   }                                          
}       
close STATS;                                                                  
                                                                            
# Clean up temporary files
#system ("mkdir coot-auto-fix_temp");
#system ("cp $temp_data/pdbtmp2.pdb ".$initial_model."_mod");
#system ("mv -t coot-auto-fix_temp $temp_data/temp.cb $temp_data/temp.clash $temp_data/temp.dngl $temp_data/temp.rama $temp_data/temp.rota $temp_data/temp.rota2 $temp_data/temp.rotated $temp_data/temp.seq $temp_data/pdbtmp1.pdb $temp_data/pdbtmp2.pdb $temp_data/temp.guans");
#system ("rm -rf 0-coot* molprobity-tmp* coot-backup "); 
 
open IN, "<$temp_data/pdbtmp2.pdb";
$modelPath  = substr($temp_data, 0, length($temp_data)-9);
$modelPath .= "/coordinates";
#$model_out = $modelPath."/".$modelID."_mod.pdb"; 
open FINAL_OUT, ">$modelOutpath";
open STATS, "<$stats";
print FINAL_OUT "USER  MOD FIX:CNNNNITTT*: Orig RotaScore :Fixed RotaScore :         FLIP DECISION          :\n";
while ($line =<STATS>) {
   if (($line =~ m/\*/ || $line =~ m/\^/) && $line !~ m/positive/) {
      ($diff, $orig, $fixed)=split(/:\t\t:/, $line);
      ($cnit, $diff_rama, $diff_rota, $diff_cb, $diff_clash, $diff_probe, $diff_hb, $diff_rsc, $diff_chi1, $diff_chi2, $diff_chi3, $diff_chi4, $diff_guan_angle, $flip_decision) = split(/:/, $diff);          
      ($orig_rama, $orig_rota, $orig_cb, $orig_clash, $orig_probe, $orig_hb, $orig_rsc, $chi1, $chi2, $chi3, $chi4) = split(/:/, $orig);
      ($fixed_rama, $fixed_rota, $fixed_cb, $fixed_clash, $fixed_probe, $fixed_hb, $fixed_rsc, $chi1, $chi2, $chi3, $chi4) = split(/:/, $fixed);
      # rmi 090729 changed USER  MOD  to:
      # USER  MOD FIX:A  27 LEU*:   0.2 (OUTLIER):  38.7      (mt):         FLIP ACCEPTED
      print FINAL_OUT "USER  MOD FIX:".$cnit.":".$orig_rota.":".$fixed_rota.":".$flip_decision.":\n"; 
   }
}
$diff_mean_rsc_tmp = $quality_score_hash{fixed_rsc_mean}- $quality_score_hash{orig_rsc_mean};
$diff_mean_rsc     = sprintf("%7.4f", $diff_mean_rsc_tmp); 
if ($diff_mean_rsc > 0) {
   print FINAL_OUT "USER  MOD      Improved MEAN RSC by ".$diff_mean_rsc." (".
       $quality_score_hash{fixed_rsc_mean}."-".$quality_score_hash{orig_rsc_mean}.")\n"; 
}
else {
   print FINAL_OUT "USER  MOD      ERROR MEAN RSC by ".$diff_mean_rsc." (".
       $quality_score_hash{fixed_rsc_mean}."-".$quality_score_hash{orig_rsc_mean}.")\n"; 
}
close STATS; 
$mod_all = $modelOutpath;
$mod_all =~ s /\.pdb$/_all\.pdb/; 
system ("cp ".$modelOutpath." ".$mod_all);

system ("cat $temp_data/pdbtmp2.pdb >> $modelOutpath");

system ("cat $temp_data/pdbtmp1.pdb >> $mod_all"); 
$kinPath  = substr($temp_data, 0, length($temp_data)-9);
$kinPath .= "/kinemages";
system ($MP_BASE_DIR."/bin/flipkin_auto_fixes ".$initial_model." ".$mod_all." > ".$kinPath."/".$modelID."_autoFlip.kin");  
$end = time();

print "Time taken was ".($end-$start)." seconds";

#}}}######################################################################## 

format FORMAT_SEQ =
@@>>>@@<<:@>>>
$chain, $resid, $ins, $resn, $new_seq
.
