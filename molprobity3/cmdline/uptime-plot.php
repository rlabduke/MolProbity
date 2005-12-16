<?php
#
# This script analyzes the output of a command like
#
#   (while true; do uptime >> uptime.log; sleep 60; done) &
#
# given on stdin and writes a kinemage plot to stdout.
#
$lines = file("php://stdin");
$timestamps = array();
$sysload_01 = array();
$sysload_05 = array();
$sysload_15 = array();

foreach($lines as $line)
{
    if(preg_match('/^\s*(\d+:\d+).+load average:\s+(\d+\.\d+),\s+(\d+\.\d+),\s+(\d+\.\d+)/', $line, $f))
    {
        #echo "time $f[1]    load $f[2]\n";
        $timestamps[]   = $f[1];
        $sysload_01[]   = $f[2];
        $sysload_05[]   = $f[3];
        $sysload_15[]   = $f[4];
    }
}

$scale = 3.0;

echo "@kinemage 1
@flatland
@vectorlist {axes} color= gray
{load} $scale 1 0
{load} 0 1 0
{origin} 0 0 0
{time} $scale 0 0
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
    for($i = 0; $i < $len; $i++)
    {
        echo "{".$timestamps[$i]."} ".($scale * $i / $len)." ".$sysload[$i]." 0\n";
    }
}
?>
