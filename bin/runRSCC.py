# (jEdit options) :folding=explicit:collapseFolds=1:
import sys,subprocess

# {{{ multiple_arrays
def multiple_arrays(stderr_out) :
  s = "Multiple equally suitable arrays of observed xray data found."
  return stderr_out.find(s) != -1
# }}}

# {{{ run_subprocess
def run_subprocess(args) :
  out = subprocess.Popen(args, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
  out_c = out.communicate()
  out_out = out_c[0]
  out_err = out_c[1]
  return out_out, out_err
# }}}

# {{{ get_first_data_label
def get_first_data_label(serr) :
  lines = serr.split('\n')
  for i in range(len(lines)) :
    if lines[i].find("Possible choices:") != -1 :
      return lines[i +1].split(":")[1].strip()
  return False
# }}}

# {{{ get_res_info
def get_res_info(s) :
  chain = s[:2]
  alt = s[2:4].strip()
  res_type = s[4:8].strip()
  res_num = s[8:13].strip()
  i_code = s[13:15].strip()
  atom = s[15:].strip()
  if alt == '' : alt = ' '
  if i_code == '' : i_code = ' '
  res_num = '%s%s' % (' '*(4-len(res_num)), res_num)
  assert len(res_num) == 4
  assert len(res_type) == 3
  res_id = chain+res_num+i_code+res_type
  assert len(res_id) == 10
  return res_id,chain,alt,res_type,res_num,i_code,atom
# }}}

# {{{ out_to_allatom_csv
def out_to_allatom_csv(out_string) :
  # Takes the output from phenix.real_space_correlation and formats it to a csv
  # for MolProbity's horizontal chart.
  lines = out_string.split('\n')
  to_residues = False
  headers = ['res_id','chain','alt','res_type','res_num','i_code','atom',
             'occ','ADP','cc','Fc','2fo-fc']
  csv = '%s\n' % ','.join(headers)
  csv_line ='%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n'
  residues_no_alts = {}
  residues_order = []
  for line in lines :
    if line.strip() == '' : continue
    if not to_residues :
      if line.startswith(' <----id') : to_residues = True
      continue
    res_id,chain,alt,res_type,res_num,i_code,atom = get_res_info(line[:21])
    if res_type == 'HOH' : continue
    occ = line[21:25].strip()
    ADP = line[25:33].strip()
    cc = line[33:41].strip()
    Fc = line[41:48].strip()
    two_fo_fc = line[48:].strip()
    tup = (res_id,chain,alt,res_type,res_num,i_code,atom,occ,ADP,cc,Fc,two_fo_fc)
    csv += csv_line % tup
    if res_id not in residues_order : 
      residues_order.append(res_id)
      residues_no_alts[res_id] = []
    assert len(headers) == len(tup)
    d = {}
    for i in range(len(headers)) : d[headers[i]] = tup[i]
    if d['alt'].upper() in [' ', 'A'] :
      residues_no_alts[res_id].append(d)
  return csv, residues_no_alts, residues_order
# }}}

# {{{ get_residue_csv
def get_residue_csv(residues_no_alts, residues_order) :
  headers = ['res_id','worst_b','worst_cc','worst_2fo-fc',
                      'worst_b_bb','worst_cc_bb','worst_2fo-fc_bb',
                      'worst_b_sc','worst_cc_sc','worst_2fo-fc_sc']
  csv = '%s\n' % ','.join(headers)
  csv_line ='%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n'
  for res_id in residues_order :
    worst_b_bb,worst_cc_bb,worst_2fo_fc_bb = False,False,False
    worst_b_sc,worst_cc_sc,worst_2fo_fc_sc = False,False,False
    worst_b,worst_cc,worst_2fo_fc = False,False,False
    for atom in residues_no_alts[res_id] :
      if not worst_b         : worst_b         = atom['ADP']
      if not worst_cc        : worst_cc        = atom['cc']
      if not worst_2fo_fc    : worst_2fo_fc    = atom['2fo-fc']
      if float(atom['ADP']) > float(worst_b) : 
        worst_b      = atom['ADP']
      if float(atom['cc']) < float(worst_cc) : 
        worst_cc     = atom['cc']
      if float(atom['2fo-fc']) < float(worst_2fo_fc) : 
        worst_2fo_fc = atom['2fo-fc']
      if atom['atom'].strip() in ['C','CA','O','N'] :
        if not worst_b_bb      : worst_b_bb      = atom['ADP']
        if not worst_cc_bb     : worst_cc_bb     = atom['cc']
        if not worst_2fo_fc_bb : worst_2fo_fc_bb = atom['2fo-fc']
        if float(atom['ADP']) > float(worst_b_bb) : 
          worst_b_bb      = atom['ADP']
        if float(atom['cc']) < float(worst_cc_bb) : 
          worst_cc_bb     = atom['cc']
        if float(atom['2fo-fc']) < float(worst_2fo_fc_bb) :
          worst_2fo_fc_bb = atom['2fo-fc']
      else :
        if not worst_b_sc      : worst_b_sc      = atom['ADP']
        if not worst_cc_sc     : worst_cc_sc     = atom['cc']
        if not worst_2fo_fc_sc : worst_2fo_fc_sc = atom['2fo-fc']
        if float(atom['ADP']) > float(worst_b_sc) :
          worst_b_sc      = atom['ADP']
        if float(atom['cc']) < float(worst_cc_sc) :
          worst_cc_sc     = atom['cc']
        if float(atom['2fo-fc']) < float(worst_2fo_fc_sc) : 
            worst_2fo_fc_sc = atom['2fo-fc']
    csv += csv_line % (res_id,worst_b,worst_cc,worst_2fo_fc,
                                worst_b_bb,worst_cc_bb,worst_2fo_fc_bb,
                                worst_b_sc,worst_cc_sc,worst_2fo_fc_sc)
  return csv
# }}}

# {{{ get_prequel
def get_prequel(rscc_lines) :
  d = {'uc':'Not Found', 'sg':'Not Found', 'sa':'Not Found', 'cr':'Not Found',\
       'cirr':'Not Found', 'oacc':'Not Found'}
  for line in rscc_lines.split('\n') :
    if line.find("Unit cell:") != -1 :
      d['uc'] = line.split(':')[1].strip()
    if line.find("Space group:") != -1 :
      d['sg'] = line.split(':')[1].strip()
    if line.find("Systematic absences:") != -1 :
      d['sa'] = line.split(':')[1].strip()
    if line.find("Centric reflections:") != -1 :
      d['cr'] = line.split(':')[1].strip()
    if line.find("Completeness in resolution range:") != -1 :
      d['cirr'] = line.split(':')[1].strip()
    if line.find("Overall map cc") != -1 : 
      d['oacc'] = line.split(':')[1].strip()

  # format unit cell
  uc = d['uc'].replace(')','')
  uc = uc.replace('(','')
  uc = uc.split(',')
  dem = '%s,%s,%s' % (uc[0],uc[1],uc[2])
  agl = '%s,%s,%s' % (uc[3],uc[4],uc[5])

  s = '<center>\n<table rules=all  cellpadding=3>\n'
  s += '  <tr><td colspan=2>Number of residues with bad backbone density</td>\n'
  s += '      <td>*REPLACE_MEBB*</td></tr>\n'
  s += '  <tr><td colspan=2>Number of residues with bad sidechain density</td>\n'
  s += '      <td>*REPLACE_MESC*</td></tr>\n'
  s += '  <tr><td colspan=2>Overall map cc</td>\n'
  s += '      <td>%s</td></tr>\n' % d['oacc']
  s += '  <tr><td rowspan=2>Unit cell</td>\n'
  s += '      <td>abc</td>\n'
  s += '      <td>%s</td></tr>\n' % dem
  s += '  <tr><td>&alpha;&beta;&gamma;</td>\n'
  s += '      <td>%s</td></tr>\n' % agl
  s += '  <tr><td colspan=2>Space group</td>\n'
  s += '      <td>%s</td></tr>\n' % d['sg']
  s += '  <tr><td colspan=2>Systematic absences</td>\n'
  s += '      <td>%s</td></tr>\n' % d['sa']
  s += '  <tr><td colspan=2>Centric reflections</td>\n'
  s += '      <td>%s</td></tr>\n' % d['cr']
  s += '  <tr><td colspan=2>Completeness in resolution range</td>\n'
  s += '      <td>%s</td></tr>\n' % d['cirr']
  s += '</table>\n</center>\n'
  return s
# }}}

def run(args) :
  out_string = False
  assert len(args) == 3
  mtz_in, pdb_in, prequel = False, False, False
  for arg in args:
    if arg.startswith("pdb_in=") :     pdb_in    = arg.split("=")[1]
    elif arg.startswith("mtz_in=") :   mtz_in    = arg.split("=")[1]
    elif arg.startswith("prequel=") :  prequel   = arg.split("=")[1]
  assert False not in [mtz_in, pdb_in, prequel], "not all arguments found"
  in_str = 'mtz_in : %s \npdb_in : %s \nprequel : %s' % (mtz_in, pdb_in, prequel)
  rscc_args = ['phenix.real_space_correlation', pdb_in, mtz_in, 'detail=atom']
  sout, serr = run_subprocess(rscc_args)
  if multiple_arrays(serr) :
    label = get_first_data_label(serr)
    assert not label or label.strip() != '', "Trouble finding xray label"
    rscc_args += ['data_labels="%s"' % label]
    sout, serr = run_subprocess(rscc_args)
    if serr.strip() == '' : out_string = sout
  elif serr.strip() == '' : out_string = sout
  # path = '/Users/bradleyhintze/Sites/moltbx/public_html/data/'###
  # path+= '98808cc21a2a364c8610521cf4eda4df/xray_data/70s_rcc_v2.data'###
  # fle = open(path,'r')###
  # out_string = fle.read()###
  # fle.close()###
  if not out_string : 
    sys.stdout.write('Trouble running phenix.real_space_correlation\n' + serr + in_str)
  else :
    fle = open(prequel, 'w')
    fle.write(get_prequel(out_string))
    fle.close()
    csv, residues_no_alts, residues_order = out_to_allatom_csv(out_string)
    out_string = get_residue_csv(residues_no_alts, residues_order)
    sys.stdout.write(out_string)

if __name__ == '__main__' :
  run(sys.argv[1:])
