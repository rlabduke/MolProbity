import sys
from iotbx.pdb.hybrid_36 import hy36decode

#This script parses the output of mmtbx.mpgeo to find results for chiral volume validation
#It assembles those results into a text file of outliers
#Three categories:
#1. True chiral centers with handedness swaps
#2. Tetrahedral centers with bad geometry
#3. Pseudochiral centers that look like handedness swap bc of atom naming errors

class chiral_volume():
  def __init__(self, line):
    x = line.split(':')
    self.chain = x[1]
    self.resseq = x[2]
    self.icode = x[3]
    self.altloc = x[4].strip()
    self.resname = x[5]
    self.atom = x[6]
    self.chiral_volume = x[7]
    self.sigma = float(x[8])
    #cutoff for identification of outliers is 4 sigma
    if self.sigma > 4:
      self.is_outlier = True
    else:
      self.is_outlier = False
    if self.sigma > 10:
      self.is_handedness_swap = True
    else:
      self.is_handedness_swap = False
    self.moltype = x[9].rstrip()
    self.resid = ":".join([self.chain, self.resseq+self.icode, self.altloc, self.resname, self.atom])

  def is_pseudochiral(self):
    #Certain atoms are treated like chiral centers because they bond to atoms that have different names without chemical difference.
    #VAL CB bonds to CG1 and CG2, for example.
    #A large chiral volume outlier relfects a failure to follow chemical naming conventions, not necessarily a major geometry error
    #So these pseudochiral centers should be treated differently.
    #
    #backbone phosphate in nucleic acids
    #OP1 and OP2 atoms are chemically identical
    if self.atom == 'P': return True
    #SF4 and F3S are iron-sulfur clusters with frequent naming problems
    if self.resname in ['SF4','F3S']: return True
    #Val CG1 and CG2 are chemically identical
    if self.resname == 'VAL' and self.atom == 'CB': return True
    #LEU CD1 and CD2 are chemically identical
    if self.resname == 'LEU' and self.atom == 'CG': return True
    #Otherwise
    return False

  def make_recommendation(self):
    if float(self.sigma) > 10:
      self.recommendation = "Probable handedness swap"
    else:
      self.recommendation = ""

  def make_output_line(self):
    return "%s:%.2f" %(self.resid, self.sigma)
#    return ":".join([self.resid, self.sigma, self.recommendation])

geomfile = open(sys.argv[1])

handedness_outliers = []
tetrahedral_outliers = []
pseudochiral_naming_errors = []
all_tetrahedral_center_count = 0
true_chiral_center_count = 0

for line in geomfile:
#6O4M_messed.pdb: B: 103: : :SO4:O3-S-O4:109.930:0.153:PROTEIN
#6O4M_messed.pdb: A:   2: : :DIL:CA:2.405:24.194:PROTEIN
#6O4M_messed.pdb: B:   2: : :ILE:CA:2.572:0.695:PROTEIN
  x = line.split(':')
  atom = x[6]
  #geomfile also contains lines for bond lengths A--B and bond angles A-B-C
  #chiral volume lines are identified with single atom names
  if '-' in atom:
    continue
  chiral = chiral_volume(line)

  all_tetrahedral_center_count += 1
  if not chiral.is_pseudochiral():
    true_chiral_center_count += 1

  if not chiral.is_outlier:
    continue

  if chiral.is_handedness_swap:
    if chiral.is_pseudochiral():
      pseudochiral_naming_errors.append(chiral)
    else:
      handedness_outliers.append(chiral)
  else:
    tetrahedral_outliers.append(chiral)

handedness_outliers.sort(key=lambda r: (r.chain, int(hy36decode(len(r.resseq), r.resseq)), r.icode, r.altloc))
tetrahedral_outliers.sort(key=lambda r: (r.chain, int(hy36decode(len(r.resseq), r.resseq)), r.icode, r.altloc))
pseudochiral_naming_errors.sort(key=lambda r: (r.chain, int(hy36decode(len(r.resseq), r.resseq)), r.icode, r.altloc))

total_outliers = len(handedness_outliers)+len(tetrahedral_outliers)+len(pseudochiral_naming_errors)

if true_chiral_center_count == 0:
  percent_chiral_outliers = 0
else:
  percent_chiral_outliers = len(handedness_outliers)/true_chiral_center_count*100

if all_tetrahedral_center_count == 0:
  percent_total_outliers = 0
else:
  percent_total_outliers = total_outliers/all_tetrahedral_center_count*100

sys.stdout.write("SUMMARY: %i total outliers at %i tetrahedral centers (%.2f%%)\n" % (total_outliers, all_tetrahedral_center_count, percent_total_outliers))
sys.stdout.write("SUMMARY: %i handedness outliers at %i chiral centers (%.2f%%)\n" % (len(handedness_outliers), true_chiral_center_count, percent_chiral_outliers))
sys.stdout.write("SUMMARY: %i tetrahedral geometry outliers\n" % (len(tetrahedral_outliers)))
sys.stdout.write("SUMMARY: %i pseudochiral naming errors\n" % (len(pseudochiral_naming_errors)))

sys.stdout.write("\n\n\n")

sys.stdout.write("Handedness swaps\n")
sys.stdout.write("----------------------------------------------------------------------\n")
for chiral in handedness_outliers:
  sys.stdout.write(chiral.make_output_line()+'\n')
if not handedness_outliers:
  sys.stdout.write("None\n")
sys.stdout.write("----------------------------------------------------------------------\n")
sys.stdout.write("\n\n")
sys.stdout.write("Tetrahedral geometry outliers\n")
sys.stdout.write("----------------------------------------------------------------------\n")
for chiral in tetrahedral_outliers:
  sys.stdout.write(chiral.make_output_line()+'\n')
if not tetrahedral_outliers:
  sys.stdout.write("None\n")
sys.stdout.write("----------------------------------------------------------------------\n")
sys.stdout.write("\n\n")
sys.stdout.write("Probable atom naming errors around pseudochiral centers\n")
sys.stdout.write("  e.g. CG1 and CG2 around Valine CB\n")
sys.stdout.write("----------------------------------------------------------------------\n")
for chiral in pseudochiral_naming_errors:
  sys.stdout.write(chiral.make_output_line()+'\n')
if not pseudochiral_naming_errors:
  sys.stdout.write("None\n")
sys.stdout.write("----------------------------------------------------------------------\n")

#sys.stdout.write("Probable handedness swaps\n")
#sys.stdout.write("SUMMARY: %i outliers out of %i CA chiral centers (%.2f%%)\n" % (len(backbone_ca_outliers), backbone_chiral_center_count, percent_backbone_outliers))
#sys.stdout.write("----------------------------------------------------------------------\n")
#for chiral in backbone_ca_outliers:
#  sys.stdout.write(chiral.make_output_line()+'\n')
#sys.stdout.write("----------------------------------------------------------------------\n")
#sys.stdout.write("\n")
#sys.stdout.write("Probable tetrahedral geometry outliers\n")
#sys.stdout.write("SUMMARY: %i outliers out of %i other chiral centers (%.2f%%)\n" % (len(other_outliers), other_chiral_center_count, percent_other_outliers))
#sys.stdout.write("----------------------------------------------------------------------\n")
#for chiral in other_outliers:
#  sys.stdout.write(chiral.make_output_line()+'\n')
#sys.stdout.write("----------------------------------------------------------------------\n")
#sys.stdout.write("\n")
#sys.stdout.write("Probable atom naming errors around pseudochiral centers\n")
#sys.stdout.write("SUMMARY: %i outliers out of %i other chiral centers (%.2f%%)\n" % (len(other_outliers), other_chiral_center_count, percent_other_outliers))
#sys.stdout.write("----------------------------------------------------------------------\n")
#for chiral in other_outliers:
#  sys.stdout.write(chiral.make_output_line()+'\n')
#sys.stdout.write("----------------------------------------------------------------------\n")

  
