#!/usr/bin/perl
##############################################################################################################################################
#Quick Clashlist 
#RMI 6/21/07
#For use with PROBE unformatted data
#The variables are as follows:
#$name = title of probe data   $pat = interaction type e.g., "1-->2"   $type = type of interaction e.g., hb or "H-bond"
#$srcAtom = source atom   $targAtom = target atom   $mingap = minimum gap b/t atoms = maximum overlap
#$gap = distance at dot/spike postions   $spX, $spY, $spZ = coordinates in space of spike   $spikLen = length of spike
#$score = score assigned by probe   $stype = starting atom type   $ttype = target atom type
#$x, $y, $z = coordinates in space of spike   $sBval, $tBval = values attached to starting and target atoms
#This program calls reduce and probe then finds the largest clash per residue and prints it if the clash is greater than .4 Angstroms 
#RMI 6/27/07 modified to allow probe run without first using reduce by invoking with -nobuild flag
##############################################################################################################################################

# Unique ids for temp  files based on flipkins unique ids

   @now = localtime(time);
   $year = $now[5];
   $mon  = $now[4];
   $day  = $now[3];
   $uniquid = "${$}${year}${mon}${day}";

if(!$ARGV[0]){
        &help;
        exit(0);
}

#-----------------------------------------------------------------------
sub help{
        print "\n*******************************************************************
quick_clashscore.pl: version 0.01 9/12/07\nCopyright 2007, Jeffrey J. Headd and Robert Immormino
For a log of changes, view quick_clashlist.pl in your favorite text editor

USAGE: quick_clashscore.pl [-options] input_file threshold

options:
  -h            outputs this help message
  -nobuild      expects a .pdb file with hydrogens present (will not run reduce)
  -verbose      outputs more details about the clashing atoms
  -mcsc         split scores into main chain and sidechain contributions

EXAMPLE:   quick_clashscore.pl 404D.pdb 0.4 > 404Dr.clashlist
";
        exit(0);
}
#-----------------------------------------------------------------------

$nobuild=0;
$verbose=0;
$mcsc   =0;

if (!($ARGV[scalar(@ARGV)-1] =~ m/[0-9]/ && $ARGV[scalar(@ARGV)-1]!~ m/[a-z]/i)){
  &help;
  exit(0);
}

if (!(-e $ARGV[scalar(@ARGV)-2])){
  &help;
  exit(0);
}

for ($i=0;$i<=(scalar(@ARGV)-3); $i++) {
  if ($ARGV[$i] =~ m/-h/) {
      &help;
      exit(0);
  }
  elsif ($ARGV[$i] =~ m /-n/i) { $nobuild=1; }
  elsif ($ARGV[$i] =~ m /-v/i) { $verbose=1; }
  elsif ($ARGV[$i] =~ m /-m/i) { $mcsc=1; }
}

$filename=$ARGV[scalar(@ARGV)-2];
$threshold=$ARGV[scalar(@ARGV)-1];

$total_score = 0.0; 
open IN, "<$filename"; 
open OUT, ">/tmp/temp.list_".$uniquid;

# create a hash to use later to sort the residues
%pdb_hash=();
%probe_score_hash=();
$count=1; 
while ($pdb_line=<IN>) {
   if (substr($pdb_line, 0,4) eq ATOM || substr($pdb_line, 0,6) eq HETATM) {
      $resn  = substr($pdb_line, 17, 3);
      $chain = substr($pdb_line, 20, 2);
      $resid = substr($pdb_line, 22, 4);
      $ins   = substr($pdb_line, 26, 1);
      $cnit  = "$chain$resid$ins$resn"; 
      if (!defined $pdb_hash{$cnit}) {
         $pdb_hash{$cnit}=$count; 
         $probe_score_hash{$cnit}->{score} = 0;
         if ($mcsc) {
            $probe_score_hash{$cnit}->{mc_wc} = 0;
            $probe_score_hash{$cnit}->{mc_cc} = 0;
            $probe_score_hash{$cnit}->{mc_so} = 0;
            $probe_score_hash{$cnit}->{mc_bo} = 0;
            $probe_score_hash{$cnit}->{mc_hb} = 0;
            $probe_score_hash{$cnit}->{sc_wc} = 0;
            $probe_score_hash{$cnit}->{sc_cc} = 0;
            $probe_score_hash{$cnit}->{sc_so} = 0;
            $probe_score_hash{$cnit}->{sc_bo} = 0;
            $probe_score_hash{$cnit}->{sc_hb} = 0;
         }
         else {
            $probe_score_hash{$cnit}->{wc} = 0;
            $probe_score_hash{$cnit}->{cc} = 0;
            $probe_score_hash{$cnit}->{so} = 0;
            $probe_score_hash{$cnit}->{bo} = 0;
            $probe_score_hash{$cnit}->{hb} = 0;
         }
         $count++;
      }    
   }
}
close IN; 

# adds hydrogens with reduce and looks for VDW contacts with probe

if ($nobuild==0) {
   system ("reduce -q -trim -DB \"lib\/reduce_wwPDB_het_dict.txt\"  ". $filename ." > /tmp/temp.pdb_".$uniquid);
   system ("reduce -q -build -PEN200 -DB \"lib\/reduce_wwPDB_het_dict.txt\" /tmp/temp.pdb_".$uniquid." > /tmp/tempH.pdb_".$uniquid);
#   system ("probe -c -q -mc -het -once -stdbonds -4h \"alta ogt33 not water\" \"all\" /tmp/tempH.pdb_".$uniquid."  > /tmp/temp.probec_".$uniquid);
#        exit(0);
   if ($mcsc) {
      system ("probe -q -u -mc -het -once -stdbonds -4h \"mc alta ogt33 not water\" \"alta ogt33\" /tmp/tempH.pdb_".$uniquid."  > /tmp/temp.probe_mc_".$uniquid);       
      system ("probe -q -u -mc -het -once -stdbonds -4h \"sc alta ogt33 not water\" \"alta ogt33\" /tmp/tempH.pdb_".$uniquid."  > /tmp/temp.probe_sc_".$uniquid);
   }
   else {
      system ("probe -q -u -mc -het -once -stdbonds -4h \"alta ogt33 not water\" \"alta ogt33\" /tmp/tempH.pdb_".$uniquid."  > /tmp/temp.probe_".$uniquid);
   }
}

else { 
   if ($mcsc) {
      system ("probe -q -u -mc -het -once -stdbonds -4h \"mc alta ogt33 not water\" \"alta ogt33\" ". $filename ."  > /tmp/temp.probe_mc_".$uniquid);
      system ("probe -q -u -mc -het -once -stdbonds -4h \"sc alta ogt33 not water\" \"alta ogt33\" ". $filename ."  > /tmp/temp.probe_sc_".$uniquid);
   }
   else {
      system ("probe -q -u -mc -het -once -stdbonds -4h \"alta ogt33 not water\" \"alta ogt33\" ". $filename ."  > /tmp/temp.probe_".$uniquid); 
#probe -u -q -mc -het -once "alta ogt$ocutval not water" "alta ogt$ocutval" $1 |\

   }
}

# the probe output is then read line by line and the largest overlaps per residue are stored in the hash probe_hash 
%probe_hash=();
for ($i=0; $i<=2; $i++) {
   if (!$mcsc) { $i += 2; }
   if ($i == 0) { $probe_file = "/tmp/temp.probe_mc_".$uniquid; }
   if ($i == 1) { $probe_file = "/tmp/temp.probe_sc_".$uniquid; }
   if ($i == 2) { $probe_file = "/tmp/temp.probe_".$uniquid; }
   open PROBE, "<$probe_file";        
   while ($line = <PROBE>) {                  
        ($name, $pat, $type, $srcAtom, $targAtom, $mingap, $gap, $spX, $spY, $spZ, $spikeLen, $score, $stype, $ttype ,$x ,$y ,$z, $sBval, $tBval) = split(":", $line);    
      $total_score += ($score + 0.0);
           if (length($srcAtom) == 15) {
              $src_res      = " ".substr($srcAtom,  0,9);
              $targ_res     = " ".substr($targAtom, 0,9);
              if ($mcsc) {
                 $srcAtomName  =     substr($srcAtom,  10,4);
                 $targAtomName =     substr($targAtom, 10,4);
              }
           }
           elsif (length($srcAtom) == 16) {
              $src_res      = substr($srcAtom,  0,10);
              $targ_res     = substr($targAtom, 0,10);
              if ($mcsc) {
                 $srcAtomName  = substr($srcAtom,  11,4);
                 $targAtomName = substr($targAtom, 11,4);
              }
           }
   
      if ($i == 0) { $type_full = "mc_".$type; }
      if ($i == 1) { $type_full = "sc_".$type; }
      if ($i == 2) { $type_full = $type; }

      $probe_score_hash{$src_res}->{score} += $score;
      $probe_score_hash{$src_res}->{$type_full} += $score;
   
   
      if ($type eq hb) {next;}
   
      if (defined $probe_hash{$src_res}) {
         ($order, $old_gap, $src, $targ)=split(":",$probe_hash{$src_res});
      }
      else { $old_gap = 0; }
      if (defined $probe_hash{$src_res}) {
         ($order, $old_gap2, $src, $targ)=split(":",$probe_hash{$targ_res});
      }
      else { $old_gap2 = 0; }
   
      $order1=$pdb_hash{$targ_res};
      $order2=$pdb_hash{$src_res};
      if ($gap < $old_gap2) { $probe_hash{$targ_res} = "$order1:$gap:$srcAtom:$targAtom"; } # the dots of the contacts aren't symmetrical so both
      if ($gap < $old_gap)  { $probe_hash{$src_res}  = "$order2:$gap:$srcAtom:$targAtom"; } # the src and targ contacts are checked to find the max
   }    
}
close(PROBE);
system ("rm -f /tmp/temp.probe_".$uniquid." /tmp/temp.pdb_".$uniquid." /tmp/tempH.pdb_".$uniquid); 

# the probe_hash is sorted to follow the order of the residues in the .pdb and 

sub sort_by_value {$probe_hash{$a} <=> $probe_hash{$b};}

#$check_score=0; 
foreach $cnit (%pdb_hash) {
   if ($pdb_hash{$cnit}) {
      ($order ,$gap, $src, $targ)=split(":",$probe_hash{$cnit}); 
       $score = $probe_score_hash{$cnit}->{score};  
       if ($mcsc) {
          $mc_wc = $probe_score_hash{$cnit}->{mc_wc};
          $mc_cc = $probe_score_hash{$cnit}->{mc_cc};
          $mc_so = $probe_score_hash{$cnit}->{mc_so};
          $mc_bo = $probe_score_hash{$cnit}->{mc_bo};
          $mc_hb = $probe_score_hash{$cnit}->{mc_hb};
          $sc_wc = $probe_score_hash{$cnit}->{sc_wc};
          $sc_cc = $probe_score_hash{$cnit}->{sc_cc};
          $sc_so = $probe_score_hash{$cnit}->{sc_so};
          $sc_bo = $probe_score_hash{$cnit}->{sc_bo};
          $sc_hb = $probe_score_hash{$cnit}->{sc_hb};
       }
       else {
          $wc = $probe_score_hash{$cnit}->{wc};
          $cc = $probe_score_hash{$cnit}->{cc};
          $so = $probe_score_hash{$cnit}->{so};
          $bo = $probe_score_hash{$cnit}->{bo};
          $hb = $probe_score_hash{$cnit}->{hb};
       }

      #$check_score+=$score;
      printf OUT "%5d", $pdb_hash{$cnit};
      if ($gap > -1*$threshold) { 
         $gap=""; 
         $src=""; 
         $targ=""; 
      }
      if ($mcsc) {
         printf OUT ":$cnit:%10s:%10s:%10s:%6.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f\n", 
               $gap, $src, $targ, $score, $mc_wc, $mc_cc, $mc_so, $mc_bo, $mc_hb, $sc_wc, $sc_cc, $sc_so, $sc_bo, $sc_hb;
      }
      else {
         printf OUT ":$cnit:%10s:%10s:%10s:%6.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f\n",
               $gap, $src, $targ, $score, $wc, $cc, $so, $bo, $hb, $sc_wc;
      }
   }
} 
system ("sort -n /tmp/temp.list_".$uniquid." > /tmp/temp2.list_".$uniquid);

if ($mcsc) {
   open IN, "</tmp/temp2.list_".$uniquid; 
   if ($verbose==0) {
      print "residue:clash:probe_score_src:mc_wc:mc_cc:mc_so:mc_bo:mc_hb:sc_wc:sc_cc:sc_so:sc_bo : sc_hb:srcAtom:targAtom \n";
   }
   else {
      print "residue   :clash     :score :mc_wc:mc_cc:mc_so:mc_bo:mc_hb:sc_wc:sc_cc:sc_so:sc_bo: sc_hb\n"; 
   }
   while ($line = <IN>) {
      ($order, $cnit, $gap, $src, $targ, $score, $mc_wc, $mc_cc, $mc_so, $mc_bo, $mc_hb, $sc_wc, $sc_cc, $sc_so, $sc_bo, $sc_hb)=split(":",$line); 
      if ($verbose==0) {
         printf "$cnit:%10s:%6.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f\n", $gap, $score, $mc_wc, $mc_cc, $mc_so, $mc_bo, $mc_hb, $sc_wc, $sc_cc, $sc_so, $sc_bo, $sc_hb;
      }
      else {
         if ($src =~ m/[A-Z]/ || $src =~ m/[0-9]/) {
            printf "$cnit:%10s:%6.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %10s : %10s \n", $gap, $score, $mc_wc, $mc_cc, $mc_so, $mc_bo, $mc_hb, $sc_wc, $sc_cc, $sc_so, $sc_bo, $sc_hb, $src, $targ;
         }
         else {
            printf "$cnit:%10s:%6.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f\n", $gap, $score, $mc_wc, $mc_cc, $mc_so, $mc_bo, $mc_hb, $sc_wc, $sc_cc, $sc_so, $sc_bo, $sc_hb;
         }
      }
   }
   close IN;
}

else {
   open IN, "</tmp/temp2.list_".$uniquid;
   if ($verbose==0) {
      print "residue   :clash     :score :mc_wc:mc_cc:mc_so:mc_bo:mc_hb:sc_wc:sc_cc:sc_so:sc_bo: sc_hb\n";
   }
   else {
      print "residue:clash:probe_score_src wc : cc : so : bo : hb :srcAtom:targAtom\n";
   }
   while ($line = <IN>) {
      ($order, $key, $gap, $src, $targ, $score, $wc, $cc, $so, $bo, $hb)=split(":",$line);
      if ($verbose==0) {
         printf "$key:%10s:%6.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f\n", $gap, $score, $wc, $cc, $so, $bo, $hb;
      }
      else {
         if ($src =~ m/[A-Z]/ || $src =~ m/[0-9]/) {
            printf "$key:%10s:%6.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f  : %10s : %10s \n", $gap, $score, $wc, $cc, $so, $bo, $hb, $src, $targ;
         }
         else {
            printf "$key:%10s:%6.1f: %4.1f: %4.1f: %4.1f: %4.1f: %4.1f\n", $gap, $score, $wc, $cc, $so, $bo, $hb;
         }
      }
   }
   close IN;
}

#print $check_score."\n"; 
print $total_score."\n"; 

# clean up temporary files
unlink ("/tmp/temp.pdb_".$uniquid); 
unlink ("/tmp/tempH.pdb_".$uniquid);
unlink ("/tmp/temp.probec_".$uniquid);
unlink ("/tmp/temp.probe_mc_".$uniquid);       
unlink ("/tmp/temp.probe_sc_".$uniquid);
unlink ("/tmp/temp.probe_".$uniquid);
unlink ("/tmp/temp.list_".$uniquid);
unlink ("/tmp/temp2.list_".$uniquid); 
