import os, sys
from libtbx import easy_run

#-u -q -mc -het -once -NOVDWOUT %s %s' % (probe_command, condensed_flag, nuclear_flag) "ogt%d not water" "ogt%d" -' % (ogt, ogt)
#       phenix.probe -4H -quiet -noticks -nogroup -dotmaster -mc -het -once -wat2wat 'water' 'water'

class water_contact():
  def __init__(self, src_atom_id, trg_atom_id, trg_heavy_atom, mingap, src_b, trg_b):
    self.src_atom_id = src_atom_id
    self.trg_atom_id = trg_atom_id
    self.trg_heavy_atom = trg_heavy_atom
    # A2778 HOH  O  A
    #ccnnnnirrr?aaaal
    self.src_chain = src_atom_id[0:2].strip()
    self.src_resseq = src_atom_id[2:6]
    self.src_icode = src_atom_id[6:7]
    self.src_resname = src_atom_id[7:10]
    self.src_atom = src_atom_id[11:15]
    self.src_altloc = src_atom_id[15:16]

    self.trg_chain = trg_atom_id[0:2].strip()
    self.trg_resseq = trg_atom_id[2:6]
    self.trg_icode = trg_atom_id[6:7]
    self.trg_resname = trg_atom_id[7:10]
    self.trg_atom = trg_atom_id[11:15]
    self.trg_altloc = trg_atom_id[15:16]

    self.mingap = mingap.lstrip('-')
    #main table lists all overlaps as positive
    #the simple strip works as long as we only look at clashes

    self.src_b = float(src_b)
    self.trg_b = float(trg_b)

  def trg_is_H(self):
    if self.trg_atom.strip().startswith('H'): return True
    else: return False

  def trg_is_charged_N(self):
  #what N's can be charged in nucleic acids?
  #how to handle the great variety of ligands?
    if self.trg_resname == 'LYS' and self.trg_atom == ' NZ ':
      return True
    elif self.trg_resname == 'ARG' and self.trg_atom in [' NH1',' NH2']:
      return True
    elif self.trg_resname == 'HIS' and self.trg_atom in [' ND1',' NE2']:
      return True
    else:
      return False

  def format_src_id_str(self):
    return ":".join([self.src_chain,self.src_resseq+self.src_icode,self.src_resname,self.src_altloc])

  def format_trg_id_str(self):
    #return self.trg_heavy_atom+" of "+":".join([self.trg_chain,self.trg_resseq+self.trg_icode,self.trg_resname,self.trg_altloc])
    return self.trg_atom+" of "+":".join([self.trg_chain,self.trg_resseq+self.trg_icode,self.trg_resname,self.trg_altloc])

  def color_clash_severity(self):
    mingap = float(self.mingap)
    if mingap < 0.5: return '#ffb3cc'
    elif mingap > 0.9: return '#ee4d4d'
    else: return '#ff76a9'

  def does_it_clash_with_polar(self):
    #Polar atoms in proteins are O, N, and S
    #Nucleic acids also have P, but that appears completely shielded by O's
    #All O are polar.  Are any N sufficiently non-polar that they wouldn't coordinate with metal?
    #Another water does not count as polar for these purposes
    #Not currently considering what polar atoms might be in ligands
    if self.trg_resname == "HOH":
      return ''
    if self.trg_heavy_atom in ['O','S']:
      return True
    if self.trg_heavy_atom == 'N':
      if self.trg_is_H():
        return True #H on N is polar
      elif self.trg_is_charged_N():
        return True
    return ''

  def does_it_clash_with_altloc(self):
    #Goal is to find any clashes that might be resolved by different altloc naming
    #A-A clashes, A-_ clashes, and _-A clashes
    #Probe should automatically ignore any A-B contacts, since those are in fully different alts
    if self.src_altloc.strip() or self.trg_altloc.strip():
      return True
    else:
      return ''

  def does_it_clash_with_other_water(self):
    if self.trg_resname == "HOH":
      return True
    else:
      return ''

  def does_it_clash_with_nonpolar(self):
    #This is the most complex one and will likely receive iterations
    #Start with simple check for non-polars
    if self.trg_heavy_atom in ['C']:
      return True
    elif self.trg_heavy_atom in ['N']:
      if not self.trg_is_charged_N():
        return True
      else:
        return ''
    else:
      return ''

  def write_polar_cell(self):
    if self.does_it_clash_with_polar():
      trg_element = self.trg_atom.strip()[0:1]
      if trg_element == 'H':
        return "<td align='center' bgcolor='%s'>&minus; ion</td>" % self.color_clash_severity()
      elif trg_element in ['O','S']:
        return "<td align='center' bgcolor='%s'>&plus; ion</td>" % self.color_clash_severity()
      elif trg_element == 'N':
        return "<td align='center' bgcolor='%s'>&minus; ion</td>" % self.color_clash_severity()
      else:
        return "<td align='center' bgcolor='%s'>*</td>" % self.color_clash_severity()
    else:
      return "<td></td>"

  def write_nonpolar_cell(self):
    if self.does_it_clash_with_nonpolar():
      return "<td align='center' bgcolor='%s'>&times;</td>" % self.color_clash_severity()
    else:
      return "<td></td>"

  def write_altloc_cell(self):
    if self.does_it_clash_with_altloc():
      if self.does_it_clash_with_other_water():
        return "<td align='center' bgcolor='%s'>alt water</td>" % self.color_clash_severity()
      elif self.src_altloc.strip() and self.trg_altloc.strip():
        return "<td align='center' bgcolor='%s'>alt both sides</td>" % self.color_clash_severity()
      elif self.src_altloc.strip():
        return "<td align='center' bgcolor='%s'>alt water</td>" % self.color_clash_severity()
      else:
        return "<td align='center' bgcolor='%s'>alt partner atom</td>" % self.color_clash_severity()
      #return "<td align='center' bgcolor='%s'>&times;</td>" % self.color_clash_severity()
    else:
      return "<td></td>"

  def write_other_water_cell(self):
    if self.does_it_clash_with_other_water():
      return "<td align='center' bgcolor='%s'>&times;</td>" % self.color_clash_severity()
    else:
      return "<td></td>"

  def write_b_cell(self, b):
    return "<td>%.2f</td>" % b

def cumulative_severity(contact_key, water_contacts):
  cumulative_severity = 0
  water = water_contacts[contact_key]
  for clash in water:
    weighted_severity = float(clash.mingap) - 0.2
    cumulative_severity += weighted_severity
  return cumulative_severity

def color_cell(clash_check):
  if clash_check: return 'bgcolor=#ff76a9'
  else: return ''

def count_waters(file_path):
#this is fragile and dependent on pdb format, but faster than hierarchy read-in
  pdbfile = open(file_path)
  water_count = 0
  for line in pdbfile:
    if line.startswith("HETATM") and line[17:20]=="HOH":
      water_count += 1
  pdbfile.close()
  return water_count

def as_html_table(water_contacts, water_count, out=sys.stdout):
  out.write("""<html>
<head>
  <title>Summary table of water clashes</title>
</head>

<body>

<hr>
This table lists all the waters in the structure that have steric clashes. Waters are classified into common categories based on the atom - or parent heavy atom of a hydrogen - they clash with.
<br><br>
Clash with polar - Waters that clash with polar groups may actually be coordinated ions.
<br>
Clash with nonpolar - Waters that clash with nonpolar groups may indicate place where covalently-bonded atoms should be, such as an unmodeled alternate conformation or a ligand.
<br>
Clash with water - Water-water clashes may be resolved by changing the involved waters into alternates of compatible occupancy. Or they may indicate a place where covalently-bonded atoms like a ligand or sidechain should be built.
<br>
Clash with altloc - Water clashes involving one or more alternate conformations may be resolved by renaming some of the alternates.
<br><br>
High B-factor - Waters with clashes and minimal support in the map should be removed from the model. This table does not report map data directly, but a high B-factor is a likely warning sign that a water is a poor fit to the map.
<br><br>
These categories are general suggestions. Check your electron density and trust your intuition and experience.
<br>
<hr>
<br>
""")
  if water_count == 0:
    out.write("SUMMARY: %i waters out of %i have clashes (%.2f%%)" % (0, 0, 0))
  else:
    out.write("SUMMARY: %i waters out of %i have clashes (%.2f%%)" % (len(water_contacts), water_count, len(water_contacts)/water_count*100))
  out.write("""
<br><br>
<hr>
<br>
<table border=1 width='100%'>
<tr bgcolor='#9999cc'><td rowspan='1' align='center'>Water ID</td>
<td align='center'>Clashes with</td>
<td align='center'>Water B</td>
<td align='center'>Contact B</td>
<td align='center'>Clash<br>Severity</td>
<td align='center'>Clash with Polar<br><small>May be ion</small></td>
<td align='center'>Clash with non-polar<br><small>Unmodeled alt or noise</small></td>
<td align='center'>Clash with water<br><small>Occ &lt;1 or ligand</small></td>
<td align='center'>Clash with altloc<br><small>Add or rename alts</small></td></tr>
""")

  contact_keys = water_contacts.keys()
  contact_keys.sort(key=lambda c: (-1*cumulative_severity(c, water_contacts)))
  #simple reverse sorting may put waters with the same cumulative severity in reverse sequence order

  row_number = 0
  row_color = ['#eaeaea','#ffffff']
  for contact_key in contact_keys:
    water = water_contacts[contact_key]
    water.sort(key=lambda w: (-1*float(w.mingap)))
    bgcolor = row_color[row_number%2]
    out.write("<tr bgcolor=%s><td rowspan='%i' ><pre><code>%s</code></pre></td>\n" % (bgcolor,len(water),water[0].format_src_id_str()))
    row_number+=1
    clashcount = 0
    for clash in water:
      if clashcount: out.write('<tr bgcolor=%s>' % bgcolor)
      clashcount+=1
      out.write("<td><pre><code>%s</code></pre></td>%s%s<td bgcolor='%s'>%s</td>%s%s%s%s</tr>\n" % (clash.format_trg_id_str(), clash.write_b_cell(clash.src_b), clash.write_b_cell(clash.trg_b), clash.color_clash_severity(), clash.mingap, clash.write_polar_cell(), clash.write_nonpolar_cell(), clash.write_other_water_cell(), clash.write_altloc_cell()) )
  out.write("</table>")
      #out.write("<td>%s</td><td bgcolor='%s'>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>\n" % (clash.format_trg_id_str(), clash.color_clash_severity(), clash.mingap,  clash.does_it_clash_with_altloc(), clash.does_it_clash_with_polar(), clash.does_it_clash_with_nonpolar(), clash.does_it_clash_with_other_water()))
      #out.write("<td>%s</td><td bgcolor='%s'>%s</td><td %s></td><td %s></td><td %s></td><td %s></td></tr>\n" % (clash.format_trg_id_str(), clash.color_clash_severity(), clash.mingap, color_cell(clash.does_it_clash_with_polar()), color_cell(clash.does_it_clash_with_nonpolar()), color_cell(clash.does_it_clash_with_other_water()), color_cell(clash.does_it_clash_with_altloc()) ))

#tr-level bgcolors for alternating table stripes
#bgcolor='#9999cc' (blue for column headers)
#bgcolor='#ffffff' (white background)
#bgcolor='#f0f0f0' (original gray for stripes)
#bgcolor='#eaeaea' (slightly darker gray for stripes)

water_contacts = {}

pdbfile = sys.argv[1]

probe_command = "phenix.probe -u -q -mc -het -con -once -wat2wat -onlybadout 'water' 'all' %s" % pdbfile

probe_out = easy_run.fully_buffered(probe_command)
if (probe_out.return_code != 0):
  raise RuntimeError("Probe crashed - dumping stderr:\n%s" %
    "\n".join(probe_out.stderr_lines))
probe_unformatted = probe_out.stdout_lines

for line in probe_unformatted:
#>>name:pat:type:srcAtom:targAtom:dot-count:min-gap:gap:spX:spY:spZ:spikeLen:score:stype:ttype:x:y:z:sBval:tBval
#SelfIntersect
#:1->2:bo: A2778 HOH  O  A: A 464 ARG  HD3 : 8:-0.581:-0.475: 36.509: 0.601: 18.650:0.238:-0.1485:O:C:36.622:0.786:18.552:30.97:17.73
#:1->2:bo: A2001 HOH  O  A: A2013 HOH  O  A:14:-0.792:-0.442:-12.858:17.914:-23.935:0.221:-0.1381:O:O:-12.726:18.090:-23.907:21.07:14.91
  n = line.split(':')
  contact_type = n[2]
  src_atom_id = n[3]
  trg_atom_id = n[4]
  dotcount = n[5]
  mingap = n[6]
  #src_heavy_atom = n[13] #always the water O
  trg_heavy_atom = n[14] #parent heavy atom of a clashing H
  #gap = n[7] #meaningless in -condensed mode, use mingap instead
  src_b = n[18]
  trg_b = n[19].strip()
  clash = water_contact(src_atom_id, trg_atom_id, trg_heavy_atom, mingap, src_b, trg_b)

  #sys.stdout.write(line)
  #sys.stdout.write('\n')
  polar_clash = clash.does_it_clash_with_polar()
  alt_clash = clash.does_it_clash_with_altloc()
  water_clash = clash.does_it_clash_with_other_water()
  nonpolar_clash = clash.does_it_clash_with_nonpolar()

  if src_atom_id not in water_contacts:
    water_contacts[src_atom_id] = []
  water_contacts[src_atom_id].append(clash)

water_count = count_waters(pdbfile)
as_html_table(water_contacts, water_count)
#sys.exit()

#contact_keys = water_contacts.keys()
#contact_keys.sort()

#sys.stdout.write("WaterID : clashes_with : clash_severity : polar : nonpolar : alt : water\n")
#for contact_key in contact_keys:
#  current_water = water_contacts[contact_key]
#  sorted(current_water, key=lambda contact:contact.src_atom_id, reverse=True)
#  #current_water.sort()
#  sys.stdout.write(current_water[0].src_atom_id + ":")
#  after_first_contact = False
#  for clash in current_water:
#    if after_first_contact:
#      sys.stdout.write("                :")
#    sys.stdout.write(":".join(
#    [clash.trg_atom_id,
#    clash.mingap,
#    str(clash.does_it_clash_with_polar()),
#    str(clash.does_it_clash_with_nonpolar()),
#    str(clash.does_it_clash_with_altloc()),
#    str(clash.does_it_clash_with_other_water())])+"\n")
#    after_first_contact = True
