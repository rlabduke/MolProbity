#!/usr/bin/perl -w

# name: pdbcns
# date created: 10/2/98
# author: J. Michael Word, Richardson Lab, Duke University
# purpose: Perl script to convert atom names for common amino acids and
#          nucleic acid bases from PDB format to CNS (or XPLOR) style
#          atom names. Getting this refined will be an ongoing process -- if
#          you notice a problem, please help me make the neccessary
#          improvements.
#
# use: In addition to changing atom names, the chain id can be mapped to a
#      given segID string and vice versa.
#
#      To convert from pdb to cns/xplor:
#            pdbcns -PM a,sega,b,segb  inputfile.pdb > outputfile.cns
#
#      To convert from cns/xplor to pdb:
#            pdbcns -CM sega,a,segb,b  inputfile.cns > outputfile.pdb
#
#      The mapping  "c,ssss..." or "ssss,c..." can not contain
#      spaces but may contain as many pairs as required separated by commas
#      use quotes if neccessary.
#
# improvements: No warranty is made as to the total accuracy of this script.
#               If you find anything that needs improving, send your comments
#               by e-mail to: mike.word@duke.edu
#
# installation: You will probably need to change the location of the perl
#               on the first line to reflect your site's configuration
#
# revision history:
#    10/2/98 - JM Word - v1.0 - first cut released
#    10/7/98 - JM Word - v1.1 - added " H  " <--> " HN " and expanded header msg.
#     2/4/00 - JM Word - v1.2 - put awk script into separate file because of
#                               error on some new SGI IRIX systems with all-in-one
#
#     5/4/01 - JM Word - v2.0 - perl version

use strict;

my $VersionString = 'pdbcns (version 2.0 - May 4, 2001 -- perl)';

my $hNameIfHasO2Prime  = '';
my $hNameWithNoO2Prime = '';
my $lastRes            = '';
my $hasO2prime = 0;

my %s2c; ## seg <-> chain maps
my %c2s;
my %p2x; ## atom mapping
my %x2p;

my $DIRECTION = '';

my $cnsinput;
my $map = '';

while(defined($ARGV[0]) && $ARGV[0] =~ /^-/) {
   $_ = shift;
   if (/[Pp]/) { # -P
      $DIRECTION = 'pdb -> cns';
   }
   if (/[Cc]/) { # -C
      $DIRECTION = 'cns -> pdb';
   }
   if (/[Pp][Mm]/) { # -PM c,seg,c,seg
      $DIRECTION = 'pdb -> cns';
      if (defined($ARGV[0])) {
        $_ = shift;
        $map = $1 if /(\S+)/;
      }
      else {
         die "no chainID to segID mapping following flag: $_\n";
      }
   }
   if (/[Cc][Mm]/) { #  -CM seg,c,seg,c
      $DIRECTION = 'cns -> pdb';
      if (defined($ARGV[0])) {
        $_ = shift;
        $map = $1 if /(\S+)/;
      }
      else {
         die "no segID to chainID mapping following flag: $_\n";
      }
   }
}
if ($DIRECTION eq '') {
   warn "syntax: pdbcns flag inputpdb > outputpdb\n";
   warn "where the flag defines the translation direction and chain<->seg mapping\n";
   warn "   -p               PDB->CNS/XPLOR\n";
   warn "   -pm c,seg,c,seg  ditto, mapping each chainID to corresponding segID\n";
   warn "   -c               CNS/XPLOR->PDB\n";
   warn "   -cm seg,c,seg,c  ditto, mapping each segID to corresponding chainID\n";
   warn "\n";
   die "command line parameter error, stopped";
}

&mapSegAndChain();

# ------------------------------------------------------------ #
# CNS and XPLOR seem to use the opposite handedness than the PDB for the
# numbering of methyl hydrogens, methylene hydrogens and most NH2s (ASN
# and GLN, but not ARG). This is why this script is not simply shifting
# the characters of the atom names but instead re-mapping them
# (often: [321]* <--> *[123], [21]*<-->*[12]).
#
# Heterogens often have a different convention in the PDB from standard
# amino acids (e.g. the methyls in a heme). Nucleic acid hydrogens are
# described in the PDB using several slightly different conventions which
# is a headache but the code here makes a stab at doing something reasonable.
# ------------------------------------------------------------ #
my @AminoAcid = ('GLY', 'ALA', 'VAL', 'PHE', 'PRO', 'MET', 'ILE',
                 'LEU', 'ASP', 'GLU', 'LYS', 'ARG', 'SER', 'THR',
                 'TYR', 'HIS', 'CYS', 'ASN', 'GLN', 'TRP', 'ASX',
                 'GLX', 'ABU', 'AIB', 'ABU', 'MSE', 'PCA');

&multi(\@AminoAcid, '', ' H  ', 2, ' HN ', 2);

&multi(\@AminoAcid, 'PRO', '3H  ', 2, ' HT1', 2);
&multi(\@AminoAcid, 'PRO', '2H  ', 2, ' HT2', 2);
&multi(\@AminoAcid, 'PRO', '1H  ', 2, ' HT3', 2);

&single('PRO', '2H  ', 2, ' HT1', 2);
&single('PRO', '1H  ', 2, ' HT2', 2);

&multi(\@AminoAcid, '', ' HA ', 2, ' HA ', 2);# (no change)

&single('LYS', '3HZ ', 2, ' HZ1', 2);
&single('LYS', '2HZ ', 2, ' HZ2', 2);
&single('LYS', '1HZ ', 2, ' HZ3', 2);
&single('LYS', '2HE ', 2, ' HE1', 2);
&single('LYS', '1HE ', 2, ' HE2', 2);
&single('LYS', '2HD ', 2, ' HD1', 2);
&single('LYS', '1HD ', 2, ' HD2', 2);
&single('LYS', '2HG ', 2, ' HG1', 2);
&single('LYS', '1HG ', 2, ' HG2', 2);
&single('LYS', '2HB ', 2, ' HB1', 2);
&single('LYS', '1HB ', 2, ' HB2', 2);

&single('GLY', '2HA ', 2, ' HA1', 2);
&single('GLY', '1HA ', 2, ' HA2', 2);

&single('GLU', '2HG ', 2, ' HG1', 2);
&single('GLU', '1HG ', 2, ' HG2', 2);
&single('GLU', '2HB ', 2, ' HB1', 2);
&single('GLU', '1HB ', 2, ' HB2', 2);

&single('THR', '3HG2', 2, 'HG21', 1);
&single('THR', '2HG2', 2, 'HG22', 1);
&single('THR', '1HG2', 2, 'HG23', 1);
&single('THR', ' HG1', 2, ' HG1', 2);# (no change)
&single('THR', ' HB ', 2, ' HB ', 2);# (no change)

&single('ALA', '3HB ', 2, ' HB1', 2);
&single('ALA', '2HB ', 2, ' HB2', 2);
&single('ALA', '1HB ', 2, ' HB3', 2);

&single('PHE', ' HZ ', 2, ' HZ ', 2);# (no change)
&single('PHE', ' HE2', 2, ' HE2', 2);# (no change)
&single('PHE', ' HE1', 2, ' HE1', 2);# (no change)
&single('PHE', ' HD2', 2, ' HD2', 2);# (no change)
&single('PHE', ' HD1', 2, ' HD1', 2);# (no change)
&single('PHE', '2HB ', 2, ' HB1', 2);
&single('PHE', '1HB ', 2, ' HB2', 2);

&single('ARG', '2HH2', 2, 'HH22', 1);# here numbering
&single('ARG', '1HH2', 2, 'HH21', 1);# is not flipped!?
&single('ARG', '2HH1', 2, 'HH12', 1);
&single('ARG', '1HH1', 2, 'HH11', 1);
&single('ARG', ' HE ', 2, ' HE ', 2);# (no change)
&single('ARG', '2HD ', 2, ' HD1', 2);
&single('ARG', '1HD ', 2, ' HD2', 2);
&single('ARG', '2HG ', 2, ' HG1', 2);
&single('ARG', '1HG ', 2, ' HG2', 2);
&single('ARG', '2HB ', 2, ' HB1', 2);
&single('ARG', '1HB ', 2, ' HB2', 2);

&single('HIS', ' HE2', 2, ' HE2', 2);# (no change)
&single('HIS', ' HE1', 2, ' HE1', 2);# (no change)
&single('HIS', ' HD2', 2, ' HD2', 2);# (no change)
&single('HIS', ' HD1', 2, ' HD1', 2);# (no change)
&single('HIS', '2HB ', 2, ' HB1', 2);
&single('HIS', '1HB ', 2, ' HB2', 2);

&single('MET', '3HE ', 2, ' HE1', 2);
&single('MET', '2HE ', 2, ' HE2', 2);
&single('MET', '1HE ', 2, ' HE3', 2);
&single('MET', '2HG ', 2, ' HG1', 2);
&single('MET', '1HG ', 2, ' HG2', 2);
&single('MET', '2HB ', 2, ' HB1', 2);
&single('MET', '1HB ', 2, ' HB2', 2);

&single('ASP', '2HB ', 2, ' HB1', 2);
&single('ASP', '1HB ', 2, ' HB2', 2);

&single('SER', ' HG ', 2, ' HG ', 2);# (no change)
&single('SER', '2HB ', 2, ' HB1', 2);
&single('SER', '1HB ', 2, ' HB2', 2);

&single('ASN', '2HD2', 2, 'HD21', 1);
&single('ASN', '1HD2', 2, 'HD22', 1);
&single('ASN', '2HB ', 2, ' HB1', 2);
&single('ASN', '1HB ', 2, ' HB2', 2);

&single('TYR', ' HH ', 2, ' HH ', 2);# (no change)
&single('TYR', ' HE2', 2, ' HE2', 2);# (no change)
&single('TYR', ' HE1', 2, ' HE1', 2);# (no change)
&single('TYR', ' HD2', 2, ' HD2', 2);# (no change)
&single('TYR', ' HD1', 2, ' HD1', 2);# (no change)
&single('TYR', '2HB ', 2, ' HB1', 2);
&single('TYR', '1HB ', 2, ' HB2', 2);

&single('CYS', ' HG ', 2, ' HG ', 2);# (no change)
&single('CYS', '2HB ', 2, ' HB1', 2);
&single('CYS', '1HB ', 2, ' HB2', 2);

&single('GLN', '2HE2', 2, 'HE21', 1);
&single('GLN', '1HE2', 2, 'HE22', 1);
&single('GLN', '2HG ', 2, ' HG1', 2);
&single('GLN', '1HG ', 2, ' HG2', 2);
&single('GLN', '2HB ', 2, ' HB1', 2);
&single('GLN', '1HB ', 2, ' HB2', 2);

&single('LEU', '3HD2', 2, 'HD21', 1);
&single('LEU', '2HD2', 2, 'HD22', 1);
&single('LEU', '1HD2', 2, 'HD23', 1);
&single('LEU', '3HD1', 2, 'HD11', 1);
&single('LEU', '2HD1', 2, 'HD12', 1);
&single('LEU', '1HD1', 2, 'HD13', 1);
&single('LEU', ' HG ', 2, ' HG ', 2);# (no change)
&single('LEU', '2HB ', 2, ' HB1', 2);
&single('LEU', '1HB ', 2, ' HB2', 2);

&single('PRO', '2HD ', 2, ' HD1', 2);
&single('PRO', '1HD ', 2, ' HD2', 2);
&single('PRO', '2HG ', 2, ' HG1', 2);
&single('PRO', '1HG ', 2, ' HG2', 2);
&single('PRO', '2HB ', 2, ' HB1', 2);
&single('PRO', '1HB ', 2, ' HB2', 2);

&single('VAL', '3HG2', 2, 'HG21', 1);
&single('VAL', '2HG2', 2, 'HG22', 1);
&single('VAL', '1HG2', 2, 'HG23', 1);
&single('VAL', '3HG1', 2, 'HG11', 1);
&single('VAL', '2HG1', 2, 'HG12', 1);
&single('VAL', '1HG1', 2, 'HG13', 1);
&single('VAL', ' HB ', 2, ' HB ', 2);# (no change)

&single('ILE', '3HD1', 2, 'HD11', 1);
&single('ILE', '2HD1', 2, 'HD12', 1);
&single('ILE', '1HD1', 2, 'HD13', 1);
&single('ILE', '3HG2', 2, 'HG21', 1);
&single('ILE', '2HG2', 2, 'HG22', 1);
&single('ILE', '1HG2', 2, 'HG23', 1);
&single('ILE', '2HG1', 2, 'HG11', 1);
&single('ILE', '1HG1', 2, 'HG12', 1);
&single('ILE', ' HB ', 2, ' HB ', 2);# (no change)

&single('TRP', ' HH2', 2, ' HH2', 2);# (no change)
&single('TRP', ' HZ3', 2, ' HZ3', 2);# (no change)
&single('TRP', ' HZ2', 2, ' HZ2', 2);# (no change)
&single('TRP', ' HE3', 2, ' HE3', 2);# (no change)
&single('TRP', ' HE1', 2, ' HE1', 2);# (no change)
&single('TRP', ' HD1', 2, ' HD1', 2);# (no change)
&single('TRP', '2HB ', 2, ' HB1', 2);
&single('TRP', '1HB ', 2, ' HB2', 2);

##-----------------------------------------------------------------------
# uncompletely specified sidechains

&single('ASX', '2HB ', 2, ' HB1', 2);
&single('ASX', '1HB ', 2, ' HB2', 2);

&single('GLX', '2HG ', 2, ' HG1', 2);
&single('GLX', '1HG ', 2, ' HG2', 2);
&single('GLX', '2HB ', 2, ' HB1', 2);
&single('GLX', '1HB ', 2, ' HB2', 2);

##-----------------------------------------------------------------------
# not so standard amino acids

&single('AIB', '3HB2', 2, 'HB21', 1);
&single('AIB', '2HB2', 2, 'HB22', 1);
&single('AIB', '1HB2', 2, 'HB23', 1);
&single('AIB', '3HB1', 2, 'HB11', 1);
&single('AIB', '2HB1', 2, 'HB12', 1);
&single('AIB', '1HB1', 2, 'HB13', 1);

&single('ABU', '3HG ', 2, ' HG1', 2);
&single('ABU', '2HG ', 2, ' HG2', 2);
&single('ABU', '1HG ', 2, ' HG3', 2);
&single('ABU', '2HB ', 2, ' HB1', 2);
&single('ABU', '1HB ', 2, ' HB2', 2);

&single('ACE', '3HH3', 2, 'HH31', 1);
&single('ACE', '2HH3', 2, 'HH32', 1);
&single('ACE', '1HH3', 2, 'HH33', 1);

&single('MSE', '3HE ', 2, ' HE1', 2);
&single('MSE', '2HE ', 2, ' HE2', 2);
&single('MSE', '1HE ', 2, ' HE3', 2);
&single('MSE', '2HG ', 2, ' HG1', 2);
&single('MSE', '1HG ', 2, ' HG2', 2);
&single('MSE', '2HB ', 2, ' HB1', 2);
&single('MSE', '1HB ', 2, ' HB2', 2);

&single('PCA', '2HG ', 2, ' HG1', 2);
&single('PCA', '1HG ', 2, ' HG2', 2);
&single('PCA', '2HB ', 2, ' HB1', 2);
&single('PCA', '1HB ', 2, ' HB2', 2);

&single('NH2', '2HN ', 2, ' HN1', 2);
&single('NH2', '1HN ', 2, ' HN2', 2);

&single('NME', '3HH3', 2, 'HH31', 1);
&single('NME', '2HH3', 2, 'HH32', 1);
&single('NME', '1HH3', 2, 'HH33', 1);

##-----------------------------------------------------------------------
# generic heme

&single('HEM', '2HAD', 2, 'HAD2', 1);
&single('HEM', '1HAD', 2, 'HAD1', 1);
&single('HEM', '2HBD', 2, 'HBD2', 1);
&single('HEM', '1HBD', 2, 'HBD1', 1);
&single('HEM', '2HAA', 2, 'HAA2', 1);
&single('HEM', '1HAA', 2, 'HAA1', 1);
&single('HEM', '2HBA', 2, 'HBA2', 1);
&single('HEM', '1HBA', 2, 'HBA1', 1);
&single('HEM', '2HBC', 2, 'HBC2', 1);
&single('HEM', '1HBC', 2, 'HBC1', 1);
&single('HEM', ' HAC', 2, ' HAC', 2);# (no change)
&single('HEM', '2HBB', 2, 'HBB2', 1);
&single('HEM', '1HBB', 2, 'HBB1', 1);
&single('HEM', ' HAB', 2, ' HAB', 2);# (no change)
&single('HEM', '3HMD', 2, 'HMD3', 1);
&single('HEM', '2HMD', 2, 'HMD2', 1);
&single('HEM', '1HMD', 2, 'HMD1', 1);
&single('HEM', '3HMC', 2, 'HMC3', 1);
&single('HEM', '2HMC', 2, 'HMC2', 1);
&single('HEM', '1HMC', 2, 'HMC1', 1);
&single('HEM', '3HMB', 2, 'HMB3', 1);
&single('HEM', '2HMB', 2, 'HMB2', 1);
&single('HEM', '1HMB', 2, 'HMB1', 1);
&single('HEM', '3HMA', 2, 'HMA3', 1);
&single('HEM', '2HMA', 2, 'HMA2', 1);
&single('HEM', '1HMA', 2, 'HMA1', 1);
&single('HEM', ' HHD', 2, ' HHD', 2);# (no change)
&single('HEM', ' HHC', 2, ' HHC', 2);# (no change)
&single('HEM', ' HHB', 2, ' HHB', 2);# (no change)
&single('HEM', ' HHA', 2, ' HHA', 2);# (no change)

##-----------------------------------------------------------------------
my @Water = ('HOH', 'DOD', 'H2O', 'D2O', 'WAT', 'TIP', 'SOL', 'MTO');

&multi(\@Water, '', '2H  ', 2, ' H2 ', 2);
&multi(\@Water, '', '1H  ', 2, ' H1 ', 2);
##-----------------------------------------------------------------------

my @gNA = ('  G', 'G  ', 'GUA', 'GTP', 'GDP', 'GMP', 'GSP');
my @aNA = ('  A', 'A  ', 'ADE', 'ATP', 'ADP', 'AMP');
my @cNA = ('  C', 'C  ', 'CYT', 'CTP', 'CDP', 'CMP');
my @uNA = ('  U', 'U  ', 'URA', 'UTP', 'UDP', 'UMP');
my @tNA = ('  T', 'T  ', 'THY', 'TTP', 'TDP', 'TMP');

my @NucleicAcid = (@gNA, @aNA, @cNA, @uNA, @tNA);

# note: in the DNA section \047 stands for a single prime character

&multi(\@NucleicAcid, '', ' C1*', 0, " C1\\047", 0);
&multi(\@NucleicAcid, '', ' O2*', 0, " O2\\047", 0);
&multi(\@NucleicAcid, '', ' C2*', 0, " C2\\047", 0);
&multi(\@NucleicAcid, '', ' O3*', 0, " O3\\047", 0);
&multi(\@NucleicAcid, '', ' C3*', 0, " C3\\047", 0);
&multi(\@NucleicAcid, '', ' O4*', 0, " O4\\047", 0);
&multi(\@NucleicAcid, '', ' C4*', 0, " C4\\047", 0);
&multi(\@NucleicAcid, '', ' O5*', 0, " O5\\047", 0);
&multi(\@NucleicAcid, '', ' C5*', 0, " C5\\047", 0);

# comment: Unfortunately, there is not a one-to-one mapping between
#          pdb and xplor/cns atom names. This is especially true in
#          the case of nucleic acid bases and common heterogen variants.
#          Because of this, the code supplied here is order dependent.
#          Where there is an ambiguity the last definition trumps!

# -------------
# problem because a single xplor type atom name (H2prime) maps
# onto to different kinds of atoms, not just aliases for the same atom.
# In pdb, DNA sugar deoxy 2prime CH name != RNA sugar oxy 2prime OH name

my $tExcludeStr = join(':', @tNA);
&multi(\@NucleicAcid, $tExcludeStr, '2HO*', 2, " H2\\047", 2);## rna

my $uExcludeStr = join(':', @tNA);
&multi(\@NucleicAcid, $uExcludeStr, '2H2*', 2, " H2\\047", 2);## dna

if ($DIRECTION eq 'cns -> pdb') {
    ## for special purpose code lower down when scanning records
    $hNameIfHasO2Prime  = '2HO*';
    $hNameWithNoO2Prime = '2H2*';
}
# -------------

&multi(\@NucleicAcid, '', ' H2*', 2, "H2\\047\\047", 1);##Two name styles in PDB
&multi(\@NucleicAcid, '', '1H2*', 2, "H2\\047\\047", 1);

&multi(\@NucleicAcid, '', ' H1*', 2, " H1\\047", 2);
&multi(\@NucleicAcid, '', ' H3*', 2, " H3\\047", 2);
&multi(\@NucleicAcid, '', ' H4*', 2, " H4\\047", 2);
&multi(\@NucleicAcid, '', '2H5*', 2, " H5\\047", 2);
&multi(\@NucleicAcid, '', '1H5*', 2, "H5\\047\\047", 1);

&multi(\@NucleicAcid, '', '3HO*', 2, ' H3T', 2);## common misnames
&multi(\@NucleicAcid, '', '*HO3', 2, ' H3T', 2);## with
&multi(\@NucleicAcid, '', ' H3T', 2, ' H3T', 2);## correct last (no change)

&multi(\@NucleicAcid, '', '5HO*', 2, ' H5T', 2);## common misnames
&multi(\@NucleicAcid, '', '*HO5', 2, ' H5T', 2);## with
&multi(\@NucleicAcid, '', ' H5T', 2, ' H5T', 2);## correct last (no change)

#----------------------------------- U

&multi(\@uNA, '', ' H6 ', 2, ' H6 ', 2);# (no change)
&multi(\@uNA, '', ' H5 ', 2, ' H5 ', 2);# (no change)

&multi(\@uNA, '', ' HN3', 2, ' H3 ', 2);## Two name styles in PDB
&multi(\@uNA, '', ' H3 ', 2, ' H3 ', 2);# (no change)

#----------------------------------- T

&multi(\@tNA, '', ' H6 ', 2, ' H6 ', 2);# (no change)

&multi(\@tNA, '', '3HM5', 2, 'H5M1', 1);##   unfortunately in the pdb
&multi(\@tNA, '', '2HM5', 2, 'H5M2', 1);##   both as #HM5
&multi(\@tNA, '', '1HM5', 2, 'H5M3', 1);##   and
&multi(\@tNA, '', '3H5M', 2, 'H5M1', 1);##   #H5M
&multi(\@tNA, '', '2H5M', 2, 'H5M2', 1);
&multi(\@tNA, '', '1H5M', 2, 'H5M3', 1);

&multi(\@tNA, '', ' HN3', 2, ' H3 ', 2);## Two name styles in PDB
&multi(\@tNA, '', ' H3 ', 2, ' H3 ', 2);# (no change)

#----------------------------------- A

&multi(\@aNA, '', ' H2 ', 2, ' H2 ', 2);# (no change)

&multi(\@aNA, '', '2HN6', 2, ' H61', 2);## Two name styles in PDB
&multi(\@aNA, '', '1HN6', 2, ' H62', 2);
&multi(\@aNA, '', '2H6 ', 2, ' H61', 2);
&multi(\@aNA, '', '1H6 ', 2, ' H62', 2);

&multi(\@aNA, '', ' H8 ', 2, ' H8 ', 2);# (no change)

#----------------------------------- C

&multi(\@cNA, '', ' H6 ', 2, ' H6 ', 2);# (no change)

&multi(\@cNA, '', ' H5 ', 2, ' H5 ', 2);# (no change)

&multi(\@cNA, '', '2HN4', 2, ' H41', 2);## Two name styles in PDB
&multi(\@cNA, '', '1HN4', 2, ' H42', 2);
&multi(\@cNA, '', '2H4 ', 2, ' H41', 2);
&multi(\@cNA, '', '1H4 ', 2, ' H42', 2);

#----------------------------------- G

&multi(\@gNA, '', ' H8 ', 2, ' H8 ', 2);# (no change)

&multi(\@gNA, '', ' HN1', 2, ' H1 ', 2);## Two name styles in PDB
&multi(\@gNA, '', ' H1 ', 2, ' H1 ', 2);# (no change)

&multi(\@gNA, '', '2H2 ', 2, ' H21', 2);## Two name styles in PDB
&multi(\@gNA, '', '1H2 ', 2, ' H22', 2);
&multi(\@gNA, '', '2H2 ', 2, ' H21', 2);
&multi(\@gNA, '', '1H2 ', 2, ' H22', 2);

while (<>) {
   # scan each record...

   if(($_ =~ /^HETATM|^ATOM  |^TER   |^SIGATM|^ANISOU|^SIGUIJ/)
   || ($_ =~ /^hetatm|^atom  |^ter   |^sigatm|^anisou|^siguij/)) {
      chop; # strip record separator
      my $rec = $_ . ( ' ' x 80); # pad short records with blanks

      my $inputAtomName    = substr($rec, 12, 4);
      my $inputResidueName = substr($rec, 17, 3);

      $inputAtomName    =~ tr/a-z/A-Z/;
      $inputResidueName =~ tr/a-z/A-Z/;

      if ($DIRECTION eq 'pdb -> cns') {
         # lookup cns style atom name and translate chainid to segid

	 my $atomName = (defined $p2x{$inputResidueName, $inputAtomName})
	        ? $p2x{$inputResidueName, $inputAtomName} : $inputAtomName;

         my $cvar = substr($rec, 21, 1); $cvar =~ tr/a-z/A-Z/;
         my $svar = substr($rec, 72, 4); $svar =~ tr/a-z/A-Z/;

	 my $segID = &chain2seg($cvar, $svar);

         printf "%s%s%s%s%s\n", substr($rec,  0, 12), $atomName, 
	                        substr($rec, 16, 56), $segID,
	                        substr($rec, 76,  8);
      }
      else { # cns -> pdb
         # lookup pdb style atom name and translate segid to chainid

	 my $atomName = (defined $x2p{$inputResidueName, $inputAtomName})
	           ? $x2p{$inputResidueName, $inputAtomName} : $inputAtomName;

	 my $theRes = substr($rec, 17, 10) . ' ' . substr($rec, 72,  4);
	    $theRes =~ tr/a-z/A-Z/;

	 if ($theRes ne $lastRes) { # code to handle XPLOR relic O2prime
            $lastRes = $theRes;
            $hasO2prime = 0;
	 }
	 if ($inputAtomName eq " O2\\047") {
            $hasO2prime = 1;
	 }
	 if ($inputAtomName eq " H2\\047") {
            $atomName = ($hasO2prime ? $hNameIfHasO2Prime : $hNameWithNoO2Prime);
	 }

         my $svar = substr($rec, 72, 4); $svar =~ tr/a-z/A-Z/;
         my $cvar = substr($rec, 21, 1); $cvar =~ tr/a-z/A-Z/;

	 my $chainID = &seg2chain($svar, $cvar);

         printf "%s%s%s%s%s\n", substr($rec, 0, 12), $atomName, 
	                        substr($rec, 16, 5), $chainID,
	                        substr($rec, 22, 62);
      }
   }
   elsif ($_ =~ /^MODEL|^model/) {
      print;
      $lastRes = '';
   }
   else { # header records, etc.
      print;
   }
}

### interpret the definitions on the command line to specify direction
### and any chain/seg mapping

sub mapSegAndChain {
   my ($c, $s, $cid, $sid);

   my @mapElements = split(/,/, $map, 999);

   $s2c{''} = ' ';
   $c2s{''} = '    ';

   if ($DIRECTION eq 'cns -> pdb') {
      printf "USER  MOD converting CNS/XPLOR format input to traditional PDB atom names\n";
      printf "USER  MOD    using %s\n", $VersionString;

      while (defined ($mapElements[0])) {
         $s = shift @mapElements;
         if (defined ($mapElements[0])) {
            $c = shift @mapElements;
            $s =~ tr/a-z/A-Z/;
            $c =~ tr/a-z/A-Z/;
            $sid = substr(($s . '    '), 0, 4);
            $cid = substr(($c . ' '),    0, 1);
            $cid =~ s/_/ /g;
            $sid =~ s/_/ /g;
            $s2c{$sid} = $cid;
            printf "USER  MOD    mapping segID \"%s\" to chainID \"%s\"\n",
                   $sid, $cid;
         }
         else {
            die "not enough chains for all segments\n";
         }
      }
   }
   elsif ($DIRECTION eq 'pdb -> cns') {
      printf "USER  MOD converting traditional PDB format input to CNS/XPLOR atom names\n";
      printf "USER  MOD    using %s\n", $VersionString;

      while (defined ($mapElements[0])) {
         $c = shift @mapElements;
         if (defined ($mapElements[0])) {
            $s = shift @mapElements;
            $c =~ tr/a-z/A-Z/;
            $s =~ tr/a-z/A-Z/;
            $cid = substr(($c . ' '),    0, 1);
            $sid = substr(($s . '    '), 0, 4);
            $cid =~ s/_/ /g;
            $sid =~ s/_/ /g;
            $c2s{$cid} = $sid;
            printf "USER  MOD    mapping chainID \"%s\" to segID \"%s\"\n",
                   $cid, $sid;
         }
         else {
            die "not enough segments for all chains\n";
         }
      }
   }
   else {
      die "unknown translation direction";
   }
}

### actual chain/seg mapping functions

sub chain2seg {
   my ($cid, $sid) = @_;
   my $seg = $sid;
   if (defined $c2s{$cid}) { $seg = $c2s{$cid}; }
   $seg;
}

sub seg2chain {
   my ($sid, $cid) = @_;
   my $chain = $cid;
   if (defined $s2c{$sid}) { $chain = $s2c{$sid}; }
   $chain;
}

### the function convHtoD() makes the conversions work for deuterium

sub convHtoD {
   my ($hpat, $hloc) = @_;

   if ($hloc == 1) {
      return 'D' . substr($hpat, 1, 999);
   }
   elsif ($hloc == 2) {
      return substr($hpat, 0, 1) . 'D' . substr($hpat, 2, 999);
   }
   #else we ignore (this is the way we handle non-hydrogens)
   undef;
}

### identical definitions for multiple residues

sub multi {
   my ($r_resNameRange, $resNameExclude, $pdbAtomName, $hp,
                                         $xplAtomName, $hx) = @_;
   my $rn = '';

   foreach $rn (@$r_resNameRange) {

      &single($rn, $pdbAtomName, $hp, $xplAtomName, $hx)
	   unless $resNameExclude =~ /$rn/ && length($rn) == 3;
   }
}

### definitions unique to a single residue

sub single {
   my ($resName, $pdbAtomName, $hp, $xplAtomName, $hx) = @_;

   # install atom name related stuff

   my $pn = $pdbAtomName;
   my $xn = $xplAtomName;

   $p2x{$resName, $pn} = $xn;
   $x2p{$resName, $xn} = $pn;

   # also setup deuterium, if neccessary

   $pn = &convHtoD($pdbAtomName, $hp);
   $xn = &convHtoD($xplAtomName, $hx);

   if (defined $pn && defined $xn) {
     $p2x{$resName, $pn} = $xn;
     $x2p{$resName, $xn} = $pn;
   }
}
