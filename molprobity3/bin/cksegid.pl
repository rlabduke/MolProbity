#!/usr/bin/perl -w
#------
# Name: cksegid.pl
# Author:J. Michael Word
# Date Written: 12/9/2002
# Purpose: scan file.pdb for use of segID rather than chainID
#
# Modifications:
# 12/11/2002 - JM Word - v1.1 - modified output and flags
#  1/31/2003 - JM Word - v1.2 - better disambiguate conflicting segIDs

use strict;

use constant VERSION => "$0 v1.2.030131";
use constant USAGE   => "$0 [-v] [-s#] [-h] input.pdb (or - for piped input)";

use constant DEFAULT_SEG_LIMIT => 36;

my @potential_chain_ids = (reverse ('A' .. 'Z'), 1 .. 9, 0);

# commandline switches
my $verbose;
my $seg_limit = DEFAULT_SEG_LIMIT;

if (! defined($ARGV[0])) {
  &show_help();
}

my $inpdb;

while(defined($ARGV[0]) && $ARGV[0] =~ /^-\S+/) {
  $_ = shift;
  die "Unrecognized flag: $_\n" unless /^-(v|h|s\d+)$/i;
  if (/\-v/i) {        # verbose flag
    $verbose = 'true';
  }
  elsif (/^-s(\d+)/) { # max segments for short output
    $seg_limit = int($1 * 1);
  }
  elsif (/\-h/i) {     # help flag
    &show_help();
  }
}

if ($#ARGV == 0 && defined($ARGV[0])) { # only one argument
   $inpdb = shift;
}
else {
   die "Usage: ".USAGE."\nstopped";
}

my ($chain, $segID, $c, $s);
my %c_count;   # how many times each chainID is observed
my %s_count;   # ditto for segIDs
my $num_a = 0; # how many Atoms

open(IN, $inpdb) || die "Can not open file: $inpdb\n";

while(<IN>) {
  if (/^ATOM|^HETATM/i) {
    chomp;

    $num_a++;

  # EXTRACT AND REMEMBER THE CHAIN ID
    $c = substr($_, 21, 1);
    $c =~ tr/a-z/A-Z/;
    $chain = substr(($c . ' '), 0, 1);
    $c_count{$chain}++;

  # EXTRACT AND REMEMBER THE SEGMENT ID
    $s = substr($_, 72, 4);
    $s =~ tr/a-z/A-Z/;
    $segID = substr(($s . '    '), 0, 4);

    $s_count{$segID}++;
  }
}
close(IN);

my $num_c = scalar keys %c_count;
my $num_s = scalar keys %s_count;

#print "DEBUG: $num_a, $num_c, $num_s\n";

if ($num_c == 1) {
  my $blank_chain = ((keys %c_count)[0] eq ' ');
  my $enough_segs = ($num_a > $num_s && $num_s > 1);

  if ($blank_chain && $enough_segs) {
    &show_segs;
  }
  else {
    &show_chains;
  }
}
elsif ($num_c > 1) {
  &show_chains;
}
else {
  die "input not PDB format ($num_a atoms): $inpdb\n";
}

sub show_chains {
  if ($verbose) {
    my $i = 0;
    print "CHAINS ($num_c)\n";
    foreach $c (sort keys %c_count) {
      $i++;
      print "$i:$c:$c_count{$c}\n";
    }
    $i = 0;
    print "\nSEGS ($num_s)";
    print " **TRUNCATED**" if $num_s > $seg_limit;
    print "\n";
    foreach $s (sort keys %s_count) {

      $i++;

      print "$i:$s:$s_count{$s}\n" if $i <= $seg_limit;
    }
    print "...\n" if $i > $seg_limit;
  }
  else {
    # silence...
  }
}

sub show_segs {
  my $i = 0;  # count of segments
  my %c_hash; # hashes for judging OK or ASK
  my %s_hash;
  my ($mc, $ms); # max number of times entry is in map

  if ($verbose) {
    print "SEGS ($num_s)";
    print " **TRUNCATED**" if $num_s > $seg_limit;
    print "\n";
    foreach $s (sort keys %s_count) {

      $c = &check_dup_c(\%c_hash, &conv_s_to_c($s, ' '));

      $c_hash{$c}++;
      $s_hash{$s}++;

      $i++;

      print "$i:$s:$c:$s_count{$s}\n" if $i <= $seg_limit;
    }
    print "...\n" if $i > $seg_limit;

    $mc = (sort {$b <=> $a} (values %c_hash))[0];
    $ms = (sort {$b <=> $a} (values %s_hash))[0];

    if ($mc != 1 || $ms != 1) { # entries seen more than once
       print "**AMBIGUOUS**\n";
    }
  }
  else {
    my $first_pass = 'true';

    foreach $s (sort keys %s_count) {
      $c = &check_dup_c(\%c_hash, &conv_s_to_c($s, '_'));

      $s =~ s/[ \n\r\t]/\_/g;


      $c_hash{$c}++;
      $s_hash{$s}++;

      $i++;
      
      if ($first_pass)         { undef $first_pass; }
      elsif ($i <= $seg_limit) { print ","; }

      print "$s,$c" if $i <= $seg_limit;
    }
    $mc = (sort {$b <=> $a} (values %c_hash))[0];
    $ms = (sort {$b <=> $a} (values %s_hash))[0];

    if ($i > $seg_limit) {         # too many
       print " TRUNCATED";
    }
    elsif ($mc != 1 || $ms != 1) { # entries seen more than once
       print " ambiguous";
    }
    else {                         # just right
       print " OK";
    }
    print "\n";
  }
}

sub check_dup_c {
  my $observed_chains  = shift; # hash_ref
  my $suggested_c_ID   = shift;

  if (exists $observed_chains->{$suggested_c_ID}) {
    foreach my $possible_id (@potential_chain_ids) {
      unless (exists $observed_chains->{$possible_id}) {
        $suggested_c_ID = $possible_id;
        last;
      }
    }
  }
  return $suggested_c_ID;
}

sub conv_s_to_c {
  my $val       = shift;
  my $default_c = shift;

  $val =~ s/SEG//i;
  $val =~ s/ +//g;
      
  $val = substr($val, 0, 1) if (length($val) >  1);
  $val = $default_c         if (length($val) != 1);
  return $val;
}

sub show_help {
  warn "Usage: ".USAGE."\n";
  warn "       Scan file.pdb for use of segID rather than chainID.\n";
  warn "       Return blank if chain OK, translation list if segID used.\n";
  warn "flags:\n";
  warn "  -v   verbose (tabular) output\n";
  warn "  -s#  max number of segs in short output is #\n";
  warn "       (default ".DEFAULT_SEG_LIMIT.")\n";
  warn "  -h   help\n";
  warn VERSION."\n";
  die "command line parameter error, stopped";
}
