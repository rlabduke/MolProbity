#!/usr/bin/perl

##############################################################################################################################################
#Quick Clashlist
#RMI 6/21/07
#For use with PROBE unformatted data
#The variables are as follows:
#$name = title of probe data    $pat = interaction type e.g., "1-->2"   $type = type of interaction e.g., hb or "H-bond"
#$srcAtom = source atom $targAtom = target atom $mingap = minimum gap b/t atoms = maximum overlap
#$gap = distance at dot/spike postions  $spX, $spY, $spZ = coordinates in space of spike        $spikLen = length of spike
#$score = score assigned by probe       $stype = starting atom type     $ttype = target atom type
#$x, $y, $z = coordinates in space of spike     $sBval, $tBval = values attached to starting and target atoms
#This program calls reduce and probe then finds the largest clash per residue and prints it if the clash is greater than .4 Angstroms
#RMI 6/27/07 modified to allow probe run without first using reduce by invoking with -nobuild flag
#JJH 9/12/07 modified to allow user to specify clash threshold for probe run
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
quick_clashlist.pl: version 0.01 9/12/07\nCopyright 2007, Jeffrey J. Headd and Robert Immormino
For a log of changes, view quick_clashlist.pl in your favorite text editor

USAGE: quick_clashlist.pl [-options] input_file threshold

options:
  -h            outputs this help message
  -nobuild      expects a .pdb file with hydrogens present (will not run reduce)
  -verbose      outputs more details about the clashing atoms

EXAMPLE:   quick_clashlist.pl 404D.pdb 0.4 > 404Dr.clashlist
";
        exit(0);
}
#-----------------------------------------------------------------------

$nobuild=0;
$verbose=0;

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
  elsif ($ARGV[$i] eq "-nobuild") { $nobuild=1; }
  elsif ($ARGV[$i] eq "-verbose") { $verbose=1; }
}

$filename=$ARGV[scalar(@ARGV)-2];
$threshold=$ARGV[scalar(@ARGV)-1];

open IN, "<$filename"; 

# create a hash to use later to sort the residues
%pdb_hash=();
$count=1; 
while ($pdb_line=<IN>) {
	if (substr($pdb_line, 0,4) eq ATOM || substr($pdb_line, 0,6) eq HETATM) {
		$resn=substr($pdb_line, 17,3);
		$chain=substr($pdb_line,20,2);
		$resid=substr($pdb_line,22,4);
		$ins=substr($pdb_line,26,1);
		$key="$chain$resid$ins$resn"; 
		if (!defined $pdb_hash{$key}) {
			$pdb_hash{$key}=$count; 
			$count++;
		} 	
	}
}

# adds hydrogens with reduce and looks for VDW contacts with probe

if ($nobuild==0) {
	system ("reduce -q -trim -DB \"lib\/reduce_wwPDB_het_dict.txt\"  ". $filename ." > /tmp/temp.pdb_".$uniquid); 
	system ("reduce -q -build -PEN200 -DB \"lib\/reduce_wwPDB_het_dict.txt\" /tmp/temp.pdb_".$uniquid." > /tmp/tempH.pdb_".$uniquid);
	system ("probe -q -u -mc -het -once -stdbonds -4h \"alta ogt33 not water\" \"alta ogt33\" /tmp/tempH.pdb_".$uniquid."  > /tmp/temp.probe_".$uniquid);       
}
else { 
	system ("probe -q -u -mc -het -once -stdbonds -4h \"alta ogt33 not water\" \"alta ogt33\" ". $filename ."  > /tmp/temp.probe_".$uniquid); 
}

# the probe output is then read line by line and the largest overlaps per residue are stored in the hash probe_hash 
open PROBE, "/tmp/temp.probe_".$uniquid;  		
%probe_hash=();
while ($line = <PROBE>) {						
     ($name, $pat, $type, $srcAtom, $targAtom, $mingap, $gap, $spX, $spY, $spZ, $spikeLen, $score, $stype, $ttype ,$x ,$y ,$z, $sBval, $tBval) = split(":", $line); 	
	$total_score+=$score;
	if ($type eq hb) {next;}
        if (length($srcAtom) == 15) {
     	   $src_res=" ".substr($srcAtom, 0,9);
	   $targ_res=" ".substr($targAtom, 0,9); 
        }
        elsif (length($srcAtom) == 16) {
           $src_res=substr($srcAtom, 0,10);
           $targ_res=substr($targAtom, 0,10);
        }
	($order, $old_gap, $src, $targ)=split(":",$probe_hash{$src_res});
	($order, $old_gap2, $src, $targ)=split(":",$probe_hash{$targ_res});
	$order1=$pdb_hash{$targ_res};
	$order2=$pdb_hash{$src_res};
	if ($gap < $old_gap2) { $probe_hash{$targ_res} = "$order1:$gap:$srcAtom:$targAtom"; } # the dots of the contacts aren't symmetrical so both
	if ($gap < $old_gap) { $probe_hash{$src_res} = "$order2:$gap:$srcAtom:$targAtom"; }   # the src and targ contacts are checked to find the max
}	 
close(PROBE);
system ("rm -f /tmp/temp.probe_".$uniquid." /tmp/temp.pdb_".$uniquid." /tmp/tempH.pdb_".$uniquid); 

# the probe_hash is sorted to follow the order of the residues in the .pdb and 
# residues with greather than .4 Angstrom overlap are printed 

sub sort_by_value {$probe_hash{$a} <=> $probe_hash{$b};}

print $filename.":\n";
foreach $key (sort sort_by_value (keys (%probe_hash))) {
	($order,$gap, $src, $targ)=split(":",$probe_hash{$key}); 
	if (defined $probe_hash{$key} && $gap <= (-1*$threshold)) {
		if ($verbose==1) {
			printf "$key:  %10s:%10s:%10s\n", $src, $targ, $gap;
		}
		else {
                        printf "$key:%10s\n", $gap;
		}
	}
} 
