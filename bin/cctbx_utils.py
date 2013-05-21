# (jEdit options) :folding=explicit:collapseFolds=1:
# this script has many functions that can be called via php exec and 'returns' 
# relevant information. The info is returned via sys.stdout.write as php exec
# captures stdout. To do this run this in php:
# 
# $a = array();
# exec('phenix.python /path/to/cctbx_utils.py command args', $a)
#
# $a has all stdout lines.
#
import sys,os

# {{{ mtz_amplitudes_check
def mtz_amplitudes_check(file_name) :
  from iotbx.file_reader import any_file
  inp = any_file(file_name)
  if(inp.file_type == "hkl") : s = 'True'
  else : s = 'False'
  sys.stdout.write(s)
# }}}


if __name__ == '__main__' :
  args = sys.argv[1:]
  if args[0] == 'mtz_amplitudes_check' : mtz_amplitudes_check(args[1])
