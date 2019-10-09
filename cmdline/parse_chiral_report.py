import sys
from iotbx.pdb.hybrid_36 import hy36decode

#This script parses the output of mmtbx.mpgeo to find results for chiral volume validation
#It assembles those results into a text file of outliers

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
    self.sigma = x[8]
    #cutoff for identification of outliers is 4 sigma
    if float(self.sigma) > 4:
      self.is_outlier = True
    else:
      self.is_outlier = False
    self.moltype = x[9].rstrip()
    self.resid = ":".join([self.chain, self.resseq+self.icode, self.altloc, self.resname, self.atom])

  def make_recommendation(self):
    if float(self.sigma) > 10:
      self.recommendation = "Probable handedness swap"
    else:
      self.recommendation = ""

  def make_output_line(self):
    return ":".join([self.resid, self.sigma, self.recommendation])

geomfile = open(sys.argv[1])

backbone_chiral_center_count = 0
other_chiral_center_count = 0
backbone_ca_outliers = []
other_outliers = []

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
  if chiral.atom == 'CA':
    backbone_chiral_center_count += 1
  else:
    other_chiral_center_count += 1
  if not chiral.is_outlier:
    continue
  chiral.make_recommendation()
  if chiral.atom == 'CA':
    backbone_ca_outliers.append(chiral)
  else:
    other_outliers.append(chiral)

backbone_ca_outliers.sort(key=lambda r: (r.chain, int(hy36decode(len(r.resseq), r.resseq)), r.icode, r.altloc))
other_outliers.sort(key=lambda r: (r.chain, int(hy36decode(len(r.resseq), r.resseq)), r.icode, r.altloc))


if backbone_chiral_center_count == 0:
  percent_backbone_outliers = 0
else:
  percent_backbone_outliers = len(backbone_ca_outliers)/backbone_chiral_center_count*100

if other_chiral_center_count == 0:
  percent_other_outliers = 0
else:
  percent_other_outliers = len(other_outliers)/other_chiral_center_count*100

sys.stdout.write("Chiral volume outliers around protein backbone CA\n")
sys.stdout.write("SUMMARY: %i outliers out of %i CA chiral centers (%.2f%%)\n" % (len(backbone_ca_outliers), backbone_chiral_center_count, percent_backbone_outliers))
sys.stdout.write("----------------------------------------------------------------------\n")
for chiral in backbone_ca_outliers:
  sys.stdout.write(chiral.make_output_line()+'\n')
sys.stdout.write("----------------------------------------------------------------------\n")
sys.stdout.write("\n")
sys.stdout.write("Chiral volume outliers around other atoms\n")
sys.stdout.write("SUMMARY: %i outliers out of %i other chiral centers (%.2f%%)\n" % (len(other_outliers), other_chiral_center_count, percent_other_outliers))
sys.stdout.write("----------------------------------------------------------------------\n")
for chiral in other_outliers:
  sys.stdout.write(chiral.make_output_line()+'\n')
sys.stdout.write("----------------------------------------------------------------------\n")


  
