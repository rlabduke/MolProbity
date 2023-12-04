<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Defines custom session-handling routines.
*****************************************************************************/
// Someone else MUST have defined this before including us!
if(!defined('MP_BASE_DIR')) die("MP_BASE_DIR is not defined.");

#{{{ mpInitEnvirons - sets environment variables, etc.
############################################################################
/** Does not need to be called explicitly if mpStartSession is called. */
function mpInitEnvirons()
{
    // Die if disk is at least 98% full, in order to protect the server.
    $df = shell_exec("df '".MP_BASE_DIR."'");
    $i = strrpos($df, "%");
    if(trim(substr($df, $i-2, 2)) >= 98) die("Server disk is more than 98% full.");

    // Configure some PHP options for our use
    // comment out the following set_magic_quotes_runtime line; deprecated in PHP vers 5.3
    // set_magic_quotes_runtime(0); // off

    // Make sure our pages aren't cached by the browser.
    // Most browsers are OK without this, but we're just being safe...
    // Taken from the PHP manual:
    /*
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");               // Date in the past
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
    header("Cache-Control: no-store, no-cache, must-revalidate");   // HTTP/1.1
    header("Cache-Control: post-check=0, pre-check=0", false);      // HTTP/1.1
    header("Pragma: no-cache");                                     // HTTP/1.0
    */

    // Set umask for files, directories, etc. that are created
    umask(MP_UMASK);

    // Set PATH for executable programs
    putenv("PATH=".mpGetPath());

    // Source cctbx environment
    putenv("PATH=".cctbxGetPath());
    //system("source ".MP_BASE_DIR."/build/setpaths.sh"); //bash
    //system("source ".MP_BASE_DIR."/build/setpaths.csh"); //tcsh

    // Also set location of Reduce's heterogen dictionary
    // Better here than on command line b/c it affects e.g. flipkin too
    putenv("REDUCE_HET_DICT=".MP_REDUCE_HET_DICT);

    // Initialize location for job files
    if(!defined('MP_JOB_DATA_DIR'))
    {
      //try global environmental variable
      //$job_loc = getenv('MP_JOB_DATA_DIR');
      $job_loc = getenv('MP_JOB_DATA_DIR');
      if($job_loc)
      {
        define("MP_JOB_DATA_DIR", $job_loc);
      }
      else
      {
        define("MP_JOB_DATA_DIR", MP_BASE_DIR."/public_html/data/");
      }
    }
}
#}}}########################################################################

#{{{ mpGetPath - constructs a plausible PATH for the shell
############################################################################
// This is in part necessary because getenv() doesn't seem to see
// the changes made by putenv() (PHP 4.3.4)
function mpGetPath()
{
    $mpPath = MP_BASE_DIR."/bin";

    if(preg_match("/(darwin|os ?x|mac)/i", PHP_OS))
        $mpPath .= ":".MP_BASE_DIR."/bin/macosx";
    elseif(preg_match("/(linux)/i", PHP_OS))
        $mpPath .= ":".MP_BASE_DIR."/bin/linux";

    if(MP_BIN_PATH != "")
        $mpPath = MP_BIN_PATH.":$mpPath";

    if(getenv("PATH") != "")
        $mpPath .= ":".getenv("PATH");

    return $mpPath;
}
#}}}########################################################################

#{{{ cctbxGetPath - gets path for cctbx executables
function cctbxGetPath()
{
    #standard and "bootstrap" installs of MolProbity have different directory structures.
    #This should account for both possibilities
    if(is_dir(MP_BASE_DIR."/build/bin")) #build,modules inside molprobity
      $cctbxPath = MP_BASE_DIR."/build/bin";
    elseif(is_dir(MP_BASE_DIR."/../build/bin")) #molprobity next to build,modules
      $cctbxPath = MP_BASE_DIR."/../build/bin";
    elseif(is_dir(MP_BASE_DIR."/../../build/bin")) #molprobity inside modules
      $cctbxPath = MP_BASE_DIR."/../../build/bin";
    else 
      die("viable cctbxPath not found, see lib/sessions.php");

    if(MP_BIN_PATH != "")
      $cctbxPath = MP_BIN_PATH.":$cctbxPath";

    if(getenv("PATH") != "")
      $cctbxPath .= ":".getenv("PATH");

    return $cctbxPath;
}
#}}}

#{{{ mpCheckSessionID - verifies that an ID has the expected form
############################################################################
function mpCheckSessionID($id)
{
    if(!preg_match('/^[a-zA-Z0-9_]{16,64}$/', $id))
        die("Illegal session ID: '$id'");
}
#}}}########################################################################

#{{{ mpStartSession - does mpInitEnvirons() and restores session data
############################################################################
/** Returns true if a new session was created, false otherwise. */
function mpStartSession($createIfNeeded = false)
{
    // First set up constants, env. variables, etc.
    mpInitEnvirons();

    // Cookies cause more trouble than they're worth
    ini_set("session.use_cookies", 0);
    ini_set("session.use_only_cookies", 0);
    // We want to control garbage collection more carefully
    // (MP_SESSION_LIFETIME is a dummy -- lifetime is determined per-session)
    ini_set("session.gc_maxlifetime", MP_SESSION_LIFETIME);
    #ini_set("session.gc_probability", 100);
    // Set up our session name
    session_name(MP_SESSION_NAME);
    // Establish custom routines for persisting session data
    session_set_save_handler ("mpSessOpen", "mpSessClose", "mpSessRead", "mpSessWrite", "mpSessDestroy", "mpSessGC");

    // Restore the session data
    @session_start(); // we get meaningless error msgs when used from a script env.
    mpCheckSessionID(session_id()); // just in case

    // Check to make sure we have a working directory for this user.
    //$dataDir = MP_BASE_DIR."/public_html/data/".session_id();
    $dataDir = MP_JOB_DATA_DIR.session_id();
    if(!file_exists($dataDir))
    {
        if($createIfNeeded)
        {
            // Always do cleanup before starting a new session
            // (MP_SESSION_LIFETIME is a dummy -- lifetime is determined per-session)
            mpSessGC(MP_SESSION_LIFETIME);

            // Main data directories
            mkdir($dataDir, 0770); // Default mode; is modified by UMASK too.
            mkdir("$dataDir/".MP_DIR_SYSTEM, 0770);
            mkdir("$dataDir/tmp", 0770);

            // Others specified in config.php must be created on demand.

            // Set up some session variables. See docs for explanation.
            $_SESSION['dataDir']        = $dataDir;
            $_SESSION['dataURL']        = "data/".session_id();
            $_SESSION['sessTag']        = session_name() . "=" . session_id();
            $_SESSION['userIP']         = getVisitorIP();
            $_SESSION['timeZone']       = MP_DEFAULT_TIMEZONE;
            $_SESSION['kingSize']       = "default";
            $_SESSION['currEventID']    = 1; // used by (optional) MVC/event architecture
            $_SESSION['models']         = array(); // no models to start with
            $_SESSION['ensembles']      = array(); // no ensembles to start with
            $_SESSION['reduce_blength'] = "ecloud"; // x-H distance
            $_SESSION['useSEGID']       = false;

            // TODO: perform other tasks to start a session
            // Create databases, etc, etc.

            //mpLog("new-session:New user session started");
            $sessionCreated = true;
        }
        else
        {
            mpLog("badsession:Unknown session with ID '".session_id()."'");
            die("Specified session '".session_id()."' does not exist.");
        }
    }
    else $sessionCreated = false;

    // Mark the lifetime of this session
    mpSessSetTTL(session_id(), MP_SESSION_LIFETIME);

    // Also set location of Reduce's heterogen dictionary,
    // overriding the value set up by mpInitEnvirons().
    // Better here than on command line b/c it affects e.g. flipkin too
    if(isset($_SESSION['hetdict'])) putenv("REDUCE_HET_DICT=".$_SESSION['hetdict']);
    else                            putenv("REDUCE_HET_DICT=".MP_REDUCE_HET_DICT);

    return $sessionCreated;
}
#}}}########################################################################

#{{{ mpSessReadOnly - prevents changes to the session from being saved
############################################################################
/**
* This might be used by progress monitoring pages that want to follow
* session data but don't want to risk interfering with the background job.
*/
$__mpsess_readonly__ = false;

function mpSessReadOnly($b = true)
{
    global $__mpsess_readonly__;
    $__mpsess_readonly__ = $b;
}
#}}}########################################################################

#{{{ mpSaveSession - write session data to disk, right now
############################################################################
// session_write_close() doesn't take effect until end of script
// has no effect if read only has been set for this session
function mpSaveSession()
{
    mpSessWrite(session_id(), session_encode());
}
#}}}########################################################################

#{{{ mpDestroySession - closes out session and destroys all user data
############################################################################
function mpDestroySession()
{
    session_unset();
    session_destroy(); // calls mpSessDestroy() to do cleanup
}
#}}}########################################################################

#{{{ mpSessOpen, mpSessClose, mpSessRead - custom session handling
############################################################################
// These functions are typically called by the system, not by users.

// This does nothing because we don't know our ID yet.
function mpSessOpen($save_path, $session_name)
{ return true; }

// This doesn't do anything either
function mpSessClose()
{ return true; }

// Read and initialize our session
function mpSessRead($id)
{
    mpCheckSessionID($id); // just in case something nasty is in there
    //$dataDir    = MP_BASE_DIR."/public_html/data/$id";
    $dataDir = MP_JOB_DATA_DIR.$id;
    $sessFile   = "$dataDir/".MP_DIR_SYSTEM."/session";

    // Read in session data, if present
    if($fp = @fopen($sessFile, "r"))
    {
        // read-write-read sequence will fail if filesize changes unless we:
        clearstatcache();
        $sessData = fread($fp, filesize($sessFile));
        @fclose($fp);
        return $sessData;
    }
    else return(""); // Must return "" here.
}
#}}}########################################################################

#{{{ mpSessWrite, mpSessDestroy, mpSessGC - custom session handling
############################################################################
// These functions are typically called by the system, not by users.

// Persist session variables to disk
function mpSessWrite($id, $sessData)
{
    // Don't do anything if we've been marked read-only
    global $__mpsess_readonly__;
    if($__mpsess_readonly__) return false;

    mpCheckSessionID($id); // just in case something nasty is in there
    //$dataDir    = MP_BASE_DIR."/public_html/data/$id";
    $dataDir    = MP_JOB_DATA_DIR.$id;
    $sessFile   = "$dataDir/".MP_DIR_SYSTEM."/session";

    // Write the session data
    if($fp = @fopen($sessFile, "w"))
    {
        $r = fwrite($fp, $sessData);
        @fclose($fp);
        if($r !== false) return true;
        return $r;
    }
    else return false;
}

function mpSessDestroy($id)
{
    mpCheckSessionID($id); // just in case something nasty is in there
    //$dataDir    = MP_BASE_DIR."/public_html/data/$id";
    $dataDir = MP_JOB_DATA_DIR.$id;

    // This actually seems to be most robust and portable... unlink() is very awkward
    `rm -rf '$dataDir'`;
    return true;
}

// $maxlifetime equals MP_SESSION_LIFETIME when called by system
// Still, we're ignoring it right now.
function mpSessGC($maxlifetime)
{
    // This is straightforward but unacceptably slow for thousands of sessions,
    // because we have to calculate both time-to-live and disk usage --
    // requires a LOT of disk access.
    #$sessions = mpEnumerateSessions();
    #foreach($sessions as $sess)
    #{
    #    // Destroy old sessions and ones that are way too big
    #    if($sess['ttl'] < 0 || $sess['size'] > 1.5*MP_SESSION_MAX_SIZE)
    #        mpSessDestroy($sess['id']);
    #}

    // Time-limited, probabalistic cleanup of old / oversize sessions
    // 1. Enumerate IDs of all active sessions.
    $start = microtime(); // seconds, as string
    $session_ids = array();
    //$baseDataDir = MP_BASE_DIR."/public_html/data";
    $baseDataDir = substr(MP_JOB_DATA_DIR, 0, strlen(MP_JOB_DATA_DIR)-1);
    $h = opendir($baseDataDir);
    while( ($id = readdir($h)) != false )
    {
        // Assume they're directories for now so we don't have to touch the disk so much
        if(preg_match('/^[a-zA-Z0-9_]{16,64}$/', $id)) $session_ids[$id] = $id;
    }
    closedir($h);
    // 2. Iterate over sessions in random order until we cover them all or run out of time
    shuffle($session_ids);
    foreach($session_ids as $id)
    {
        // Confirm now that they're really directories
        if(!is_dir("$baseDataDir/$id")) continue;
        $lifetime = mpSessLifetime($id);
        $ttl = $lifetime['ttl'];
        $size = mpSessSizeOnDisk($id);
        if($ttl < 0 || $size > 1.5*MP_SESSION_MAX_SIZE)
            mpSessDestroy($id);
        $ellapsed = microtimeSubtract(microtime(), $start);
        //echo "$ellapsed seconds ellapsed...\n";
        if( $ellapsed > 1.0 ) break;
    }
    return true;
}
#}}}########################################################################

#{{{ mpSessSetTTL, mpSessTimeToLive, mpSessLifetime - session lifetimes
############################################################################
/**
* Sets the number of seconds remaining in the session's life
* and updates its last-touched flag.
*/
function mpSessSetTTL($id, $timeToLive)
{
    mpCheckSessionID($id); // just in case something nasty is in there
    //$dataDir = MP_BASE_DIR."/public_html/data/$id";
    $dataDir = MP_JOB_DATA_DIR.$id;
    if($fp = @fopen("$dataDir/".MP_DIR_SYSTEM."/lifetime", "w"))
    {
        $time = time();
        if($timeToLive <= 0) $timeToLive = MP_SESSION_LIFETIME;
        $destroy = $time + $timeToLive;

        fwrite($fp, $time."    # Last-used date: ".date("j M Y \\a\\t g:ia", ($time))."\n");
        fwrite($fp, $destroy."    # Destroy-on date: ".date("j M Y \\a\\t g:ia", ($destroy))."\n");
        @fclose($fp);
    }
}

/**
* Returns number of seconds remaining in this sessions life.
* The number may be less than 0, indicating a session past its
* expiration date.
*/
function mpSessTimeToLive($id)
{
    $lifetime = mpSessLifetime($id);
    return $lifetime['ttl'];
}

/**
* Returns array('last' => last_touched, 'ttl' => time_to_live).
*/
function mpSessLifetime($id)
{
    mpCheckSessionID($id); // just in case something nasty is in there
    //$dataDir = MP_BASE_DIR."/public_html/data/$id";
    $dataDir = MP_JOB_DATA_DIR.$id;
    if($fp = @fopen("$dataDir/".MP_DIR_SYSTEM."/lifetime", "r"))
    {
        $lastTouched = trim(fgets($fp, 1024)) + 0;
        $destroyOn = trim(fgets($fp, 1024)) + 0;
        @fclose($fp);

        // For backwards compat: second line used to be text
        // Use longest possible lifetime to give 'em benefit of the doubt
        if($destroyOn == 0) $destroyOn = ($lastTouched + MP_SESSION_LIFETIME_EXT);

        return array('last' => $lastTouched, 'ttl' => ($destroyOn-time()));
    }
    else return array('last' => 0, 'ttl' => 0);
}
#}}}########################################################################

#{{{ mpSessSizeOnDisk - returns the total size of a session (bytes)
############################################################################
function mpSessSizeOnDisk($id)
{
    mpCheckSessionID($id); // just in case something nasty is in there
    //$dataDir    = MP_BASE_DIR."/public_html/data/$id";
    $dataDir    = MP_JOB_DATA_DIR.$id;
    return `du -sk '$dataDir'` * 1024;
}
#}}}########################################################################

#{{{ mpEnumerateSessions - collect data on all current sessions
############################################################################
/**
* Returns an array (keyed on session ID) of arrays with the following fields:
*   id      the session id
*   last    last touched time (Unix timestamp)
*   ttl     time to live, in seconds
*   size    disk usage, in bytes
* This function may take tens of seconds to run on a production server with
* thousands of active sessions.
*/
function mpEnumerateSessions()
{
    $sesslist = array();

    //$baseDataDir = MP_BASE_DIR."/public_html/data";
    $baseDataDir = substr(MP_JOB_DATA_DIR, 0, strlen(MP_JOB_DATA_DIR)-1);
    $h = opendir($baseDataDir);
    while( ($id = readdir($h)) != false )
    {
        if(preg_match('/^[a-zA-Z0-9_]{16,64}$/', $id)
        && is_dir("$baseDataDir/$id"))
        {
            unset($sess);
            $lifetime       = mpSessLifetime($id);
            $sess['id']     = $id;
            $sess['last']   = $lifetime['last'];
            $sess['ttl']    = $lifetime['ttl'];
            $sess['size']   = mpSessSizeOnDisk($id);
            $sesslist[$id]  = $sess;
        }
    }
    closedir($h);

    return $sesslist;
}
#}}}########################################################################

#{{{ mpLog - records a system log message
############################################################################
/**
* Writes the message, along with IP number, session ID, and current time.
* Fields are colon-delimited, so the recommended format is a short identifying string
* followed by a colon and a longer, more human-readable description.
*/
function mpLog($msg)
{
    $f = fopen(MP_BASE_DIR."/feedback/molprobity.log", "a");

    $sess = session_id();
    $ip = getVisitorIP();
    $time = time(); // seconds since the Epoch (1 Jan 1970 midnight GMT)

    fwrite($f, "$ip:$sess:$time:$msg");
    if(! endsWith($msg, "\n")) fwrite($f, "\n");

    fclose($f);
}
#}}}########################################################################

#{{{ mpLogPath - used for logging a users path thru the system
############################################################################
/**
* Writes the message, along with IP number, session ID, and current time.
* Fields are colon-delimited, so the recommended format is a short identifying string
* followed by a colon and a longer, more human-readable description.
*/
function mpLogPath($pageOrUrl)
{
    $f = fopen(MP_BASE_DIR."/feedback/user_paths.log", "a");

    $sess = session_id();
    $ip = getVisitorIP();
    $time = time(); // seconds since the Epoch (1 Jan 1970 midnight GMT)

    fwrite($f, "$ip:$sess:$time:$pageOrUrl");
    if(! endsWith($pageOrUrl, "\n")) fwrite($f, "\n");

    fclose($f);
}
#}}}########################################################################

#{{{ postSessionID - makes a hidden <INPUT> for forms
############################################################################
function postSessionID()
{
    if(session_id() != "")
        return "<input type='hidden' name='".session_name()."' value='".session_id()."'>\n";
    else
        return "";
}
#}}}########################################################################

#{{{ getVisitorIP - try really hard to figure out the user's actual IP address
############################################################################
// Try really hard to figure out the user's actual IP address, even if
// they're behind a proxy, etc.
//
// Note that this was taken from the net BUT MODIFIED; the original
// reference is still given below.
//
// This wrapper avoids things with underscores, etc. that don't really look like IPs
function getVisitorIP()
{
    $ip = getVisitorIP_impl();
    if(preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $ip)) return $ip;
    else return $_SERVER['REMOTE_ADDR'];
}

/***********************************************
* Copyright:
*
* You may use freely this script, class or functions.
* We hope you'll respect the author's intellectual property by
* --- keeping this notice in your source
*     author's URL is: http://www.marc.meurrens.org
* --- setting a link to the URL of these PHP scripts:
*     http://www.cgsa.net/php
* --- dropping a note to the author:
*     Marc.Meurrens@ACM.org
**********************************************/
function getVisitorIP_impl()
{
    // These aren't registered globally by default anymore -- IWD
    //global  $HTTP_VIA, $HTTP_X_COMING_FROM, $HTTP_X_FORWARDED_FOR, $HTTP_X_FORWARDED,
    //$HTTP_COMING_FROM, $HTTP_FORWARDED_FOR, $HTTP_FORWARDED, $REMOTE_ADDR;
    $HTTP_VIA               = $_SERVER['HTTP_VIA'];
    $HTTP_X_COMING_FROM     = $_SERVER['HTTP_X_COMING_FROM'];
    $HTTP_X_FORWARDED_FOR   = $_SERVER['HTTP_X_FORWARDED_FOR'];
    $HTTP_X_FORWARDED       = $_SERVER['HTTP_X_FORWARDED'];
    $HTTP_COMING_FROM       = $_SERVER['HTTP_COMING_FROM'];
    $HTTP_FORWARDED_FOR     = $_SERVER['HTTP_FORWARDED_FOR'];
    $HTTP_FORWARDED         = $_SERVER['HTTP_FORWARDED'];
    $REMOTE_ADDR            = $_SERVER['REMOTE_ADDR'];

    if($HTTP_X_FORWARDED_FOR)
    {
        // case 1.A: proxy && HTTP_X_FORWARDED_FOR is defined
        $b = preg_match("/^([0-9]{1,3}\.){3,3}[0-9]{1,3}/", $HTTP_X_FORWARDED_FOR, $array) ;
        if ($b && (count($array)>=1) )
        { return ( $array[0] ) ; } // first IP in the list
        else
        { return ( $REMOTE_ADDR . '_' . $HTTP_VIA . '_' . $HTTP_X_FORWARDED_FOR ) ; }
    }
    elseif($HTTP_X_FORWARDED)
    {
        // case 1.B: proxy && HTTP_X_FORWARDED is defined
        $b = preg_match("/^([0-9]{1,3}\.){3,3}[0-9]{1,3}/", $HTTP_X_FORWARDED, $array) ;
        if ($b && (count($array)>=1) )
        { return ( $array[0] ) ; } // first IP in the list
        else
        { return ( $REMOTE_ADDR . '_' . $HTTP_VIA . '_' . $HTTP_X_FORWARDED ) ; }
    }
    elseif($HTTP_FORWARDED_FOR)
    {
        // case 1.C: proxy && HTTP_FORWARDED_FOR is defined
        $b = preg_match("/^([0-9]{1,3}\.){3,3}[0-9]{1,3}/", $HTTP_FORWARDED_FOR, $array) ;
        if ($b && (count($array)>=1) )
        { return ( $array[0] ) ; } // first IP in the list
        else
        { return ( $REMOTE_ADDR . '_' . $HTTP_VIA . '_' . $HTTP_FORWARDED_FOR ) ; }
    }
    elseif($HTTP_FORWARDED)
    {
        // case 1.D: proxy && HTTP_FORWARDED is defined
        $b = preg_match("/^([0-9]{1,3}\.){3,3}[0-9]{1,3}/", $HTTP_FORWARDED, $array) ;
        if ($b && (count($array)>=1) )
        { return ( $array[0] ) ; } // first IP in the list
        else
        { return ( $REMOTE_ADDR . '_' . $HTTP_VIA . '_' . $HTTP_FORWARDED ) ; }
    }
    elseif($HTTP_VIA)
    {
        // case 2:
        // proxy && HTTP_(X_) FORWARDED (_FOR) not defined && HTTP_VIA defined
        // other exotic variables may be defined
        return ( $HTTP_VIA . '_' . $HTTP_X_COMING_FROM . '_' . $HTTP_COMING_FROM ) ;
    }
    elseif(   $HTTP_X_COMING_FROM || $HTTP_COMING_FROM  )
    {
        // case 3: proxy && only exotic variables defined
        // the exotic variables are not enough, we add the REMOTE_ADDR of the proxy
        return ( $REMOTE_ADDR . '_' . $HTTP_X_COMING_FROM . '_' . $HTTP_COMING_FROM ) ;
    }
    else
    {
        // case 4: no proxy
        // or tricky case: proxy+refresh
        return ( $REMOTE_ADDR ) ;
    }
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################
?>
