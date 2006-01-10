<?php
#
# This script analyzes the output of a command like
#
#   (while true; do echo -n `date +'%Y-%m-%d %H:%M:%S %z'` '::' >> uptime.log; uptime >> uptime.log; sleep 60; done) &
#
# given on stdin and writes a kinemage plot to stdout.
#
# The funny args to date() are because the PHP strtotime() doesn't like
# the default date format, which is stupid but true.
#
$lines = file("php://stdin");
$timestamps = array();
$sysload_01 = array();
$sysload_05 = array();
$sysload_15 = array();

foreach($lines as $line)
{
    if(preg_match('/^\s*(.+?)\s*::.*load average:?\s+(\d+\.\d+),?\s+(\d+\.\d+),?\s+(\d+\.\d+)/', $line, $f))
    {
        //echo "time $f[1]    load $f[2]\n";
        $timestamps[]   = strtotime($f[1]);
        $sysload_01[]   = $f[2];
        $sysload_05[]   = $f[3];
        $sysload_15[]   = $f[4];
    }
}

$scale = 60*60*24; // 1 day
//$scale = 60*60; // 1 hour
$axis = (max($timestamps) - min($timestamps)) / $scale;

echo "@kinemage 1
@flatland
@vectorlist {axes} color= gray
{load} $axis 1 0
{load} 0 1 0
{origin} 0 0 0
{time} $axis 0 0
";

echo "@vectorlist {1 min. load} color= blue\n";
printLoad($timestamps, $sysload_01);
echo "@vectorlist {5 min. load} color= sea\n";
printLoad($timestamps, $sysload_05);
echo "@vectorlist {15 min. load} color= yellow\n";
printLoad($timestamps, $sysload_15);

function printLoad($timestamps, $sysload)
{
    global $scale;
    $len = count($timestamps);
    $minT = min($timestamps);
    //$maxT = max($timestamps);
    //$lenT = $maxT - $minT;
    for($i = 0; $i < $len; $i++)
    {
        $t = $timestamps[$i];
        echo "{".date("D j M Y g:ia T", $t)."    ".$sysload[$i]."} ".(($t-$minT)/$scale)." ".$sysload[$i]." 0\n";
    }
}
?>
