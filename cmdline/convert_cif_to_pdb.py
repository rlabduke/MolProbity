from __future__ import division
# LIBTBX_SET_DISPATCHER_NAME phenix.cif_as_pdb
import os
import iotbx.pdb
import iotbx.pdb.mmcif
import mmtbx.model

#This code is derived from cctbx_project/iotbx/command_line/cif_as_pdb.py
#  (aka phenix.cif_as_pdb), with modifications for use in MolProbity

def run(args):
  if len(args) != 2:
    sys.stdout.write("""
Error in cif conversion commandline
Format is:
convert_cif_to_pdb.py input_file_path.cif output_file_path.pdb
""")
    sys.exit()
  file_name = args[0]
  output_name = args[1]
  try:
    assert os.path.exists(file_name)
    #print "Converting %s to PDB format." %file_name
    cif_input = iotbx.pdb.mmcif.cif_input(file_name=file_name)
    m = mmtbx.model.manager(model_input=cif_input)
    basename = os.path.splitext(os.path.basename(file_name))[0]
    pdb_text = m.model_as_pdb()
    with open(output_name, 'w') as f:
        f.write(pdb_text)
  except Exception, e:
    print "Error converting %s to PDB format:" %file_name
    print " ", str(e)

if __name__ == '__main__':
  import sys
  run(sys.argv[1:])
