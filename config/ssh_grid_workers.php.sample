<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file controls the hosts used by cmdline/ssh_grid_filter.
    It must be edited and exist as config/ssh_grid_workers.php before
    running that script.
    
    Local workers are "sh -c".
    Remote workers are (for example) "ssh user@host.com".
    Two-hop workers can be given as "ssh user1@gateway ssh user2@internal".
    SSH passwords should be supplied by RSA or DSA keys,
    with ssh-agent already running if the keys are password protected.
    
    # A brief example of how to set this stuff up.
    # Read the ssh man pages for more information.
    # Generate key pairs for password-less authentication
    # This is done on the master node (running ssh_grid_filter)
    # This only needs to ever be done once
    # Don't use passwords (if you do, use ssh-agent later)
    ssh-keygen -t rsa
    # Copy public key to authorized_keys files on all remote hosts
    cat ~/.ssh/id_rsa.pub | ssh user@slave1 'cat - >> .ssh/authorized_keys'
    cat ~/.ssh/id_rsa.pub | ssh user@slave2 'cat - >> .ssh/authorized_keys'
    ...
    # Login to remote hosts to make sure it works - should get no pw prompt
    ssh user@slave1
    # Enter ssh commands into this file (below)
    # Now, convert a bunch of PDBs to lower case
    ssh_grid_filter ~/my_pdbs/ ~/lowercase_pdbs/ 'tr [A-Z] [a-z]'
    
    
*****************************************************************************/



// Please delete the die() line below once you've configured the grid workers:
die("Fatal error: please read and edit the file molprobity/config/ssh_grid_workers.php\n");



$ssh_grid_workers = array(
    // Local machine is dual core
    'local 1' => "sh -c", // a local worker
    'local 2' => "sh -c", // a local worker
    
    // Rarely in use, dual core
    'quirk 1' => "ssh iwd@quiddity.biochem.duke.edu ssh quirk",     // back room public Q-client
    'quirk 2' => "ssh iwd@quiddity.biochem.duke.edu ssh quirk",
    'quake 1' => "ssh iwd@quiddity.biochem.duke.edu ssh quake",     // Vince's Q-client
    'quake 2' => "ssh iwd@quiddity.biochem.duke.edu ssh quake",
    'slide 1' => "ssh iwd@quiddity.biochem.duke.edu ssh slide",     // Gary's Q-client
    'slide 2' => "ssh iwd@quiddity.biochem.duke.edu ssh slide",
    
    // Rarely in use, single core
    'soar'    => "ssh iwd@richardsons.biochem.duke.edu ssh soar",   // back room public R-client
    'song'    => "ssh iwd@richardsons.biochem.duke.edu ssh song",   // Sandra's R-client / public
    
    // Generally in use, dual core
    // Without -T, we get:
    //   Pseudo-terminal will not be allocated because stdin is not a terminal.
    //   Warning: no access to tty (Bad file descriptor).
    //   Thus no job control in this shell.
    // Also had to log into each one once manually, or killed by the
    // "unrecognized host key" message.
    'sack 1'  => "ssh -T iwd@richardsons.biochem.duke.edu ssh -T sack",   // Laura's R-client
    'sack 2'  => "ssh -T iwd@richardsons.biochem.duke.edu ssh -T sack",
    'slip 1'  => "ssh -T iwd@richardsons.biochem.duke.edu ssh -T slip",   // Gary's R-client
    'slip 2'  => "ssh -T iwd@richardsons.biochem.duke.edu ssh -T slip",
    #'seed 1'  => "ssh -T iwd@richardsons.biochem.duke.edu ssh -T seed",   // Vince's R-client
    #'seed 2'  => "ssh -T iwd@richardsons.biochem.duke.edu ssh -T seed",
    'sail 1'  => "ssh -T iwd@richardsons.biochem.duke.edu ssh -T sail",   // Bob's R-client
    'sail 2'  => "ssh -T iwd@richardsons.biochem.duke.edu ssh -T sail",
    'soot 1'  => "ssh -T iwd@richardsons.biochem.duke.edu ssh -T soot",   // Jane's R-client
    'soot 2'  => "ssh -T iwd@richardsons.biochem.duke.edu ssh -T soot",
);
?>
