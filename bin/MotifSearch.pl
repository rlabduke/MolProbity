#!/usr/bin/perl
##################################################################################################################################################
#RNA Motif Search Script
#Gary Kapral 9/12/12
#For use with Suitestring.txt files for MolProbity.
##################################################################################################################################################
$n=0;
$n=@ARGV;

#flags for possible motifs to search
for ($i=0;$i<$n;$i++){	#while i is less than the specified number of input arguments

 if ($ARGV[$i] =~ /-Smotif/){	#if the ith argument is -ext
 $Smotif = 1;			
 print "Smotif's are awesome\n";
 }
elsif ($ARGV[$i] =~ /-GNRA/){	#if the argument is -trim
$GNRA = 1;			
}
}

while ($line = <STDIN>) {	#create a loop to cycle through each line of the input file
  chomp($line);
	$fullsuitestring .= $line; 	#concatenates all the lines into a single uberstring
}

@suites = split(/[A-Z]/,$fullsuitestring); #split out the suites
foreach $_ (@suites) {
	$suitestring .= $_;
}
if ($Smotif){
	if ($suitestring =~ /5z4s#a/) {
		print "Smotif found!\nYour structure contains Smotifs. Please 
		refer to the suitereport file and look for the ";
		
	}
}
print $suitestring;



