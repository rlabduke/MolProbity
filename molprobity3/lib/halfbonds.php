<?php
$process = FALSE;
$stdin = fopen('php://stdin', 'r');
$stdout = fopen('php://stdout', 'w'); 
while( !feof($stdin) )
{
    $s = fgets($stdin, 1024);
    if(preg_match("/^@/", $s)) $process = FALSE;
    
    if($process) // we need to process this line
    {
        preg_match_all("/{[^{]*/", $s, $points);
        foreach($points[0] as $point)
        {
            $point = rtrim($point); // remove possible trailing newline
            // 1: first half
            // 2: point ID
            // 3: P or L or ''
            // 4: second half
            // 5, 6, 7: x, y, z
            if(preg_match("/({([^}]*)} ?([PL])?)([^-0-9]*(-?[0-9]+\\.[0-9]*)[ ,]+(-?[0-9]+\\.[0-9]*)[ ,]+(-?[0-9]+\\.[0-9]*))/", $point, $parts))
            {
                $x = $parts[5] + 0.0;
                $y = $parts[6] + 0.0;
                $z = $parts[7] + 0.0;
                $color = getColor($parts[2]);
                if($hasPrev && $parts[3] != 'P')
                {
                    fwrite($stdout, "{}U $prevColor ".($x+$prevX)/2.0." ".($y+$prevY)/2.0." ".($z+$prevZ)/2.0."\n");
                    fwrite($stdout, "$parts[1] $color $parts[4]\n");
                }
                else fwrite($stdout, $point."\n");
                
                $hasPrev = TRUE;
                $prevX = $x;
                $prevY = $y;
                $prevZ = $z;
                $prevColor = $color;
            }
        }
    }
    else // we're not processing this line
    {
        fwrite($stdout, $s);
    }
    
    if(preg_match("/^@vector/", $s))
    {
        $process = TRUE;
        $hasPrev = FALSE;
    }
}
fclose($stdin);
fclose($stdout);

function getColor($id)
{
    if(preg_match("/^[1-9 ]c/", $id)) return "white";
    elseif(preg_match("/^[1-9 ]h/", $id)) return "gray";
    elseif(preg_match("/^[1-9 ]o/", $id)) return "red";
    elseif(preg_match("/^[1-9 ]n/", $id)) return "sky";
    elseif(preg_match("/^[1-9 ]s/", $id)) return "yellow";
    elseif(preg_match("/^[1-9 ]p/", $id)) return "gold";
    else return "green"; // things we don't recognize
}
?>
