<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Launches a page with an embedded NGL instance to view a kinemage.

INPUTS (via Get or Post):
    url             URL of the kinemage to load

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    mpSessReadOnly();
    mpLog("ngl:User opened a kinemage file in NGL");

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################

$url = $_REQUEST['url'];
$file = basename($url);
// Start the page: produces <HTML>, <HEAD>, <BODY> tags
//echo mpPageHeader("NGL - $file");



############################################################################
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title>NGL - placeholder title - MolProbity</title>
    <link rel="StyleSheet" href="css/default.css" TYPE="text/css">
    <link rel="stylesheet" href="js/nglcss/font-awesome.min.css" />
    <link rel="stylesheet" href="js/nglcss/main.css" />
    <link rel="subresource" href="js/nglcss/light.css" />
    <link rel="subresource" href="js/nglcss/dark.css" />
    <link rel="shortcut icon" href="favicon.ico">
    <meta name="ROBOTS" content="INDEX, NOFOLLOW">
</head>
<body>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td width="150"><img src="img/small-logo5.gif" alt="MolProbity logo"></td>
    <td valign="bottom"><div class="pageheader">
        <h1>NGL - 1ubqFH-multi.kin.gz</h1>
    </div></td>
    <td width="1"><img src="img/placeholder.png" alt=""></td>
    <td width="1"><img src="img/placeholder.png" alt=""></td>
</tr>
<tr><td valign="top" colspan="4">
    <div class="pagecontent_alone">

<center>
<div id="viewport" content="width=device-width">

<script src="../public_html/js/ngl.dev.js"></script>
<script src="../public_html/js/nglscripts/ui/signals.min.js"></script>
<script src="../public_html/js/nglscripts/ui/colorpicker.min.js"></script>

<script src="../public_html/js/nglscripts/ui/ui.js"></script>
<script src="../public_html/js/nglscripts/ui/ui.extra.js"></script>
<script src="../public_html/js/nglscripts/ui/ui.ngl.js"></script>
<script src="../public_html/js/nglscripts/kin-viewer.js"></script>
<script src="../public_html/js/nglscripts/gui.js"></script>

<script>
NGL.cssDirectory = "js/nglcss/"
        document.addEventListener( "DOMContentLoaded", function() {
            var stage = new NGL.Stage( "viewport" )
            NGL.StageWidget(stage)
            
            //var load = NGL.getQuery("load")
            //if (load) stage.loadFile(load, {defaultRepresentation: true})
            //
            //var script = NGL.getQuery("script")
            //if (script) stage.loadFile("./scripts/" + script + ".js")
            //
            //var plugin = NGL.getQuery("plugin")
            //if (plugin) NGL.PluginRegistry.load(plugin, stage)
            //  
            //var struc = NGL.getQuery("struc")
            //var traj = NGL.getQuery("traj")
            
            //stage.loadFile('./js/nglscripts/kin-viewer.js')
            console.warn(Object.keys(stage))
            console.warn("myngl.preferences "+NGL.Preferences.storage)
    //$s .= "                stage.loadFile( \"$modelURL/$model[pdb]\", { defaultRepresentation: true } );\n";
    //$s .= "                stage.loadFile( \"$kinURL/$model[prefix]thumbnail.kin\", { ext: \"kin\" }  );\n";
<?php    
echo "NGL.autoLoad(\"$url\").then(function (kinemage) {
  for (let master in kinemage.masterDict) {
    var shape = new NGL.Shape(master)
    //console.warn(Object.keys(shape))
    //console.warn(Object.keys(shape.name))

    kinemage.dotLists.forEach(function (dotList) {
      if (!dotList.masterArray.includes(master)) return
      for (var i = 0, il = dotList.positionArray.length / 3; i < il; ++i) {
        var i3 = i * 3
        var x = dotList.positionArray[ i3 ]
        var y = dotList.positionArray[ i3 + 1 ]
        var z = dotList.positionArray[ i3 + 2 ]
        var r = dotList.colorArray[ i3 ]
        var g = dotList.colorArray[ i3 + 1 ]
        var b = dotList.colorArray[ i3 + 2 ]
        shape.addPoint([ x, y, z ], [ r, g, b ], dotList.labelArray[ i ])
      }
    })

    kinemage.vectorLists.forEach(function (vectorList) {
      if (!vectorList.masterArray.includes(master)) return
      for (var i = 0, il = vectorList.position1Array.length / 3; i < il; ++i) {
        var i3 = i * 3
        var x1 = vectorList.position1Array[ i3 ]
        var y1 = vectorList.position1Array[ i3 + 1 ]
        var z1 = vectorList.position1Array[ i3 + 2 ]
        var x2 = vectorList.position2Array[ i3 ]
        var y2 = vectorList.position2Array[ i3 + 1 ]
        var z2 = vectorList.position2Array[ i3 + 2 ]
        var r = vectorList.color1Array[ i3 ]
        var g = vectorList.color1Array[ i3 + 1 ]
        var b = vectorList.color1Array[ i3 + 2 ]
        var width = 2
        if (vectorList.width.length != 0) {
          width = vectorList.width[0]
        }
        shape.addWideline([ x1, y1, z1 ], [ x2, y2, z2 ], [ r, g, b ], width, vectorList.label1Array[ i ])
      }
    })
    
    kinemage.ballLists.forEach(function (ballList) {
      if (!ballList.masterArray.includes(master)) return
      for (var i = 0, il = ballList.positionArray.length / 3; i < il; ++i) {
        var i3 = i * 3
        var x = ballList.positionArray[ i3 ]
        var y = ballList.positionArray[ i3 + 1 ]
        var z = ballList.positionArray[ i3 + 2 ]
        var r = ballList.colorArray[ i3 ]
        var g = ballList.colorArray[ i3 + 1 ]
        var b = ballList.colorArray[ i3 + 2 ]
        shape.addSphere([ x, y, z ], [ r, g, b ], ballList.radiusArray[ i ], ballList.labelArray[ i ])
      }
    })
    
    var positionArray = []
    var colorArray = []
    kinemage.ribbonLists.forEach(function (ribbonList) {
      if (!ribbonList.masterArray.includes(master)) return
      for (var i = 0, il = ribbonList.positionArray.length / 9; i < il; ++i) {
        var i9 = i * 9
        var i3 = i * 3
        var x1 = ribbonList.positionArray[ i9 ]
        var y1 = ribbonList.positionArray[ i9 + 1 ]
        var z1 = ribbonList.positionArray[ i9 + 2 ]
        var x2 = ribbonList.positionArray[ i9 + 3 ]
        var y2 = ribbonList.positionArray[ i9 + 4 ]
        var z2 = ribbonList.positionArray[ i9 + 5 ]
        var x3 = ribbonList.positionArray[ i9 + 6 ]
        var y3 = ribbonList.positionArray[ i9 + 7 ]
        var z3 = ribbonList.positionArray[ i9 + 8 ]
        var r1 = ribbonList.colorArray[ i9 ]
        var g1 = ribbonList.colorArray[ i9 + 1 ]
        var b1 = ribbonList.colorArray[ i9 + 2 ]
        var r2 = ribbonList.colorArray[ i9 + 3 ]
        var g2 = ribbonList.colorArray[ i9 + 4 ]
        var b2 = ribbonList.colorArray[ i9 + 5 ]
        var r3 = ribbonList.colorArray[ i9 + 6 ]
        var g3 = ribbonList.colorArray[ i9 + 7 ]
        var b3 = ribbonList.colorArray[ i9 + 8 ]
        var nx = ribbonList.normalArray[ i3 ]
        var ny = ribbonList.normalArray[ i3 + 1 ]
        var nz = ribbonList.normalArray[ i3 + 2 ]
        shape.addTriangle([ x1, y1, z1 ], [ x2, y2, z2 ], [ x3, y3, z3 ], [ r1, g1, b1 ], [], ribbonList.labelArray[ i3 ])
        //shape.addMesh([ x1, y1, z1, x2, y2, z2, x3, y3, z3 ], [ r1, g1, b1, r2, g2, b2, r3, g3, b3 ], [ 0, 0, 0 ], [ 1, 1, 1, 1, 1, 1, 1, 1, 1 ], ribbonList.labelArray[ i3 ])
      }
    })
    
    //var testwidelineBuffer = new NGL.WideLineBuffer({
    //  position1: new Float32Array([ 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 1, 1, 0, 1, 0, 0, 0, 1 ]),
    //  position2: new Float32Array([1, 0, 0, 0, 1, 0, 1, 1, 0, 1, 0, 0, 0, 1, 0, 0, 0 ]),
    //  color: new Float32Array([255, 0, 106, 255, 0, 106, 255, 0, 106, 255, 0, 106, 255, 0, 106,]),
    //  color2: new Float32Array([255, 0, 106, 255, 0, 106, 255, 0, 106, 255, 0, 106, 255, 0, 106,])
    //}, {
    //  linewidth: 3
    //})
    //shape.addBuffer(testwidelineBuffer)

    //var meshBuff = new NGL.MeshBuffer({
    //  position: new Float32Array(
    //    [ 27.529, 29.032, 5.354,
    //      25.558, 28.867, 4.930,
    //      27.540, 29.681, 5.820,
    //      
    //      25.558, 28.867, 4.930,
    //      27.540, 29.681, 5.820,
    //      25.537, 29.360, 5.683,
    //      
    //      27.540, 29.681, 5.820,
    //      25.537, 29.360, 5.683,
    //      27.532, 30.283, 6.297
    //     
    //      ]
    //  ),
    //  color: new Float32Array(
    //    [ 0.3, 0.3, 1, 
    //      0.3, 0.3, 1, 
    //      0.3, 0.3, 1, 
    //      
    //      0.3, 0.3, 1, 
    //      0.3, 0.3, 1, 
    //      0.3, 0.3, 1, 
    //      
    //      0.3, 0.3, 1, 
    //      0.3, 0.3, 1, 
    //      0.3, 0.3, 1
    //     ]
    //  )
    //}, {
    //});
    //console.log(meshBuff.drawMode)
    //shape.addBuffer(meshBuff)
    
    var visible = kinemage.masterDict[ master ].visible
    var shapeComp = stage.addComponentFromObject(shape, { visible: visible })
    shapeComp.addRepresentation('buffer')
  }

  //console.warn(stage.parameters)
  stage.setParameters({cameraType: 'orthographic'})
  stage.autoView()
})"
?>
        } )
</script>
</div>

<br><form>
<table border='0' width='100%'><tr>
<td align='left'>
When finished, you should 
<input type="button" value="close this window"
language="JavaScript" onclick="self.close();">.
</td>
<td><a href='help/java.html'>Don't see anything?</a></td>
<td align='right'>
</td>
</tr></table>
</form>

</center>
<?php echo mpPageFooter(); ?>
