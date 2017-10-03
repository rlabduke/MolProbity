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
import math

# {{{ mtz_amplitudes_check
def mtz_amplitudes_check(file_name) :
  from iotbx.file_reader import any_file
  inp = any_file(file_name)
  if(inp.file_type == "hkl") : s = 'True'
  else : s = 'False'
  sys.stdout.write(s)
# }}}

#{{{ all_chain_ids
def all_chain_ids():
  all_chain_ids = []
  for i in range(0, 3905):
    all_chain_ids.append(convert_integer_to_chain_id(i))
    print all_chain_ids[i]

def convert_integer_to_chain_id(chain_number):
  """
  Takes as input a number and returns the corresponding 2 character chain id
  First returns all possible single letter codes (a space followed by upper case letters, then numbers, then lower case)
  Then returns all possible uppercase/number codes (AA-A9-BA-B9-99).
  Then returns all possible codes with second letter lower case (Aa-Az-Ba-9z).
  Then returns all possible codes with first letter lower case (aA-a9-bA-b9-z9).
  Then returns all possible codes with lower case letters (aa-az-ba-zz).
  There are 3906 possible codes with this system.
  """
  allowed_chains = "ABCDEFGHIJKLMNOPQRSTUVWXYZ" \
             "0123456789" \
             "abcdefghijklmnopqrstuvwxyz"
  left_chain_characters = " "+allowed_chains
  try:
    chain_int = int(chain_number)
  except ValueError:
    raise Sorry("Must enter an integer into iotbx.pdb.hierarchy.get_remapped_chain_id")
  if chain_int < 0:
    raise Sorry("Must enter a non-negative integer into iotbx.pdb.hierarchy.get_remapped_chain_id")
  if chain_int > 3905:
    raise Sorry("Must enter an integer less than 3906 into iotbx.pdb.hierarchy.get_remapped_chain_id")
  left_chain_index, right_chain_index = convert_integer_to_chain_indexes(chain_int)
  # print(str(left_chain_index)+":"+str(right_chain_index))
  return left_chain_characters[left_chain_index]+allowed_chains[right_chain_index]

def convert_integer_to_chain_indexes(chain_integer):
  """
  Helper function for convert_integer_to_chain_id.
  Takes as input an allowable chain_integer and returns the indexes for the corresponding two character chain ID.
  """
  left_chain_index = 0
  right_chain_index = 0
  if chain_integer < 62: return left_chain_index, chain_integer # if below 62, return indexes for single characters
  elif chain_integer < 1358: # 2 char chains (only upper case and numbers)
    left_chain_index = int(math.floor((chain_integer-62)/36)+1)
    right_chain_index = int((chain_integer-62) % 36)
  elif chain_integer < 2294: # 2 char chains (upper case and numbers followed by a lower case letter)
    left_chain_index = int(math.floor((chain_integer-1358)/26)+1)
    right_chain_index = int((chain_integer-1358) % 26)+36
  elif chain_integer < 3230: # 2 char chains (lower case letter followed by an upper case or number)
    left_chain_index = int(math.floor((chain_integer-2294)/36)+37)
    right_chain_index = int((chain_integer-2294) % 36)
  elif chain_integer < 3906: # 2 char chains (both lower case)
    left_chain_index = int(math.floor((chain_integer-3230)/26)+37)
    right_chain_index = int((chain_integer-3230) % 26)+36
  return left_chain_index, right_chain_index
#}}}

if __name__ == '__main__' :
  args = sys.argv[1:]
  if args[0] == 'mtz_amplitudes_check' : mtz_amplitudes_check(args[1])
  if args[0] == 'all_chain_ids': all_chain_ids()