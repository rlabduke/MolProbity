from iotbx import reflection_file_reader
import iotbx.pdb
from iotbx import pdb
from cctbx import xray
from cctbx import adptbx
from cctbx.array_family import flex
from scitbx.array_family import shared
from libtbx.str_utils import show_string
from libtbx.utils import Sorry, user_plus_sys_time
from libtbx.option_parser import option_parser
import sys

def read_map_structure_factors(file_name):
  miller_arrays = reflection_file_reader.any_reflection_file(
    file_name=file_name).as_miller_arrays()
  for miller_array in miller_arrays:
    if (miller_array.is_complex_array()):
      break
  else:
    raise Sorry("No complex array in reflection file %s." % (
      show_string(refl_file_name)))
  print "Input structure factors:"
  miller_array.show_comprehensive_summary(prefix="  ")
  print
  if (miller_array.anomalous_flag()):
    print "Averaging Bijvoet mates:"
    miller_array = miller_array.average_bijvoet_mates()
    miller_array.show_comprehensive_summary(prefix="  ")
    print
  return miller_array

def run(args):
  if (len(args) == 0): args = ["--help"]
  command_line = (option_parser(
    usage="iotbx.python %s [options] reflection_file pdb_file" % sys.argv[0])
    .option(None, "--fake_fft_map",
      action="store_true",
      default=False,
      help="Replace structure factors from reflection file"
           " with F-calc from pdb file")
    .option(None, "--best_model_map",
      action="store_true",
      default=False,
      help="Obtain model map from F-calc via FFT")
    .option(None, "--b_extra",
      action="store",
      type="float",
      help="Empirical B-extra for computation of model map."
        " If not given, it is determined based on the resolution of the data.",
      metavar="FLOAT")
  ).process(args=args, nargs=2)
  refl_file_name = command_line.args[0]
  pdb_file_name = command_line.args[1]
  #
  map_structure_factors = read_map_structure_factors(file_name=refl_file_name)
  #
  #pdb_hierarchy = iotbx.pdb.hierarchy.input(file_name=pdb_file_name)
  pdb_inp = pdb.input(file_name=pdb_file_name)
  pdb_hierarchy = pdb_inp.construct_hierarchy()
  print
  pdb_models = pdb_hierarchy.models()
  if (len(pdb_models) != 1):
    raise Sorry("More than one MODEL in PDB file %s." % (
      show_string(pdb_file_name)))
  for chain in pdb_models[0].chains():
    if (len(chain.conformers()) != 1):
      print "**************************************"
      print "WARNING: Ignoring multiple conformers."
      print "**************************************"
      print
      break
  #
  fft_map = map_structure_factors.fft_map().real_map()
  d_min = map_structure_factors.d_min()
  #
  b_extra = command_line.options.b_extra
  if (b_extra is None):
    # simple estimate using linear interpolation based on empirical values
    if (d_min <= 1.5):
      b_extra = 10
    elif (d_min >= 4.0):
      b_extra = 70
    else:
      slope = (70 - 10) / (4.0 - 1.5)
      b_extra = 10 + (d_min - 1.5) * slope
  print "B-extra for model map: %.6g" % b_extra
  print
  #
  xray_structure = pdb_inp.xray_structure_simple()
  timer = user_plus_sys_time()
  sampled_density = xray.sampled_model_density(
    unit_cell=xray_structure.unit_cell(),
    scatterers=xray_structure.scatterers(),
    scattering_type_registry=xray_structure.scattering_type_registry(
      d_min=d_min),
    fft_n_real=fft_map.focus(),
    fft_m_real=fft_map.all(),
    u_base=adptbx.b_as_u(b_extra),
    wing_cutoff=0.001,
    exp_table_one_over_step_size=-100,
    force_complex=False,
    sampled_density_must_be_positive=False,
    tolerance_positive_definite=1.e-5,
    use_u_base_as_u_extra=True,
    store_grid_indices_for_each_scatterer=True)
  print "time xray.sampled_model_density: %.2f s" % timer.elapsed()
  print
  model_map = sampled_density.real_map()
  if (command_line.options.best_model_map):
    print "INFO: using best model_map"
    model_map = map_structure_factors.structure_factors_from_scatterers(
      xray_structure=xray_structure).f_calc().fft_map().real_map()
    print
  if (command_line.options.fake_fft_map):
    print "INFO: fft_map computed from F-calc"
    fft_map = map_structure_factors.structure_factors_from_scatterers(
      xray_structure=xray_structure).f_calc().fft_map().real_map()
    print
  #
  for i_seq,atom in enumerate(pdb_inp.atoms()): # slow Python loop
    atom.tmp = i_seq
  gifes = sampled_density.grid_indices_for_each_scatterer()
  fft_map.resize(flex.grid(fft_map.all())) # ignore padding
  model_map.resize(flex.grid(model_map.all()))
  timer = user_plus_sys_time()
  correlations = flex.double()
  print "chain, residue, correlation, number of contributing grid points"
  for chain in pdb_models[0].chains():
    for residue in chain.conformers()[0].residues():
      residue_selections = shared.stl_set_unsigned()
      i_seqs = flex.size_t()
      for atom in residue.atoms(): # slow Python loop
        i_seqs.append(atom.tmp)
      residue_selections.append_union_of_selected_arrays(
        arrays=gifes,
        selection=i_seqs)
      sel = residue_selections[0]
      corr = flex.linear_correlation(
        x=fft_map.as_1d().select(sel),
        y=model_map.as_1d().select(sel))
      print "%s %s%s%s %6.4f %5d" % (chain.id,residue.resname,residue.resseq,residue.icode,
        corr.coefficient(), corr.n())
      correlations.append(corr.coefficient())
  print "time per-residue correlations: %.2f s" % timer.elapsed()
  print
  print "Correlations:"
  correlations.min_max_mean().show(prefix="  ")

if (__name__ == "__main__"):
  run(sys.argv[1:])
