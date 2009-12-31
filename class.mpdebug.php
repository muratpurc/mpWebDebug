<?php
/**
 * Contains a debug class used as a helper during development.
 *
 * @author      Murat Purc <murat@purc.de>
 * @copyright   © Murat Purc 2008-2009
 * @package     Development
 * @subpackage  Debugging
 */


/**
 * Debug class. Provides a possibility to debug variables without destroying the page
 * output.
 *
 * If debugging is enabled, the output will be a small collapsable mpWebDebug bar
 * at the top left corner of the page.
 *
 * Inspired by the Web Debug Toolbar of symfony Framework which provides a simple way to display
 * debug informations during development.
 *
 * See example1.php and example2.php in delivered package.
 *
 * Usage:
 * <code>
 * // include the class file
 * require_once('class.mpdebug.php');
 *
 * // get instance of the mpdebug class
 * $mpDebug = mpDebug::getInstance();
 *
 * // set configuration
 * $options = array(
 *     'enable'                    => true,
 *     'ressource_urls'            => array('/path_to_logs/error.txt'), // this is a not working example ;-)
 *     'dump_super_globals'        => array('$_GET', '$_POST', '$_COOKIE', '$_SESSION'),
 *     'ignore_empty_superglobals' => true,
 *     'max_superglobals_size'     => 512
 * );
 * $mpDebug->setConfig($options);
 *
 * $foo = 'Text';
 * $mpDebug->addDebug($foo, 'Content of foo');
 *
 * $bar = array('win', 'nix', 'apple');
 * $mpDebug->addDebug($bar, 'win, nix and apple', __FILE__, __LINE__);
 *
 * ...
 *
 * // and at the end of the page (before closing body-Tag)...
 * echo $mpDebug->getResults();
 * </code>
 *
 * @author      Murat Purc <murat@purc.de>
 * @copyright   © Murat Purc 2008-2009
 * @license     http://www.gnu.org/licenses/gpl-2.0.html - GNU General Public License, version 2
 * @version     0.9.2
 * @package     Development
 * @subpackage  Debugging
 */
class mpDebug
{

    /**
     * Self instance
     * @var  mpDebug
     */
    static private $_instance;

    /**
     * Array to store dumps of variables, as follows:
     * <code>
     * - $_dumpCache[pos]['name']
     * - $_dumpCache[pos]['value']
     * - $_dumpCache[pos]['source']
     * - $_dumpCache[pos]['line']
     * </code>
     * @var  array
     */
    private $_dumpCache = array();

    /**
     * Flag to activate debug
     * @var  bool
     */
    private $_enable = false;

    /**
     * Flag to trim document root from paths
     * @var  bool
     */
    private $_trimDocRoot = true;

    /**
     * Document root
     * @var  string
     */
    private $_docRoot;

    /**
     * Counter 4 added debug items
     * @var  int
     */
    private $_counter = 0;

    /**
     * Debug links absolute from document-root, e. g. to log files or other docs.
     * @var  array
     */
    private $_resUrls = array();

    /**
     * List of superglobals names to dump automatically.
     * @var  array
     */
    private $_dumpSuperglobals = array('$_GET', '$_POST', '$_COOKIE', '$_SESSION');

    /**
     * Ignore dumping of empty superglobals.
     * @var  bool
     */
    private $_ignoreEmptySuperglobals = false;

    /**
     * Maximum allowed superglobal size in KB (used for $_POST and $_SESSION).
     * @var  int
     */
    private $_maxSuperglobalSize = 512;


    /**
     * Constructor
     * @return  void
     */
    protected function __construct()
    {
        if ($this->_trimDocRoot == true) {
            $this->_docRoot = $_SERVER['DOCUMENT_ROOT'];
            if (DIRECTORY_SEPARATOR == "\\") {
                 $this->_docRoot = str_replace(DIRECTORY_SEPARATOR, '/', $this->_docRoot);
             }
        }
    }


    /**
     * Prevent cloning
     * @return  void
     */
    private function __clone()
    {
        // donut
    }


    /**
     * Returns a instance of mpDebug (singleton implementation)
     *
     * @return  mpDebug
     */
    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
     * Sets configuration
     *
     * @param  array  $options  Options array as follows:
     * <code>
     * // true or false to enable/disable debugging
     * $options['enable'] = true;
     *
     * // Array of ressource files which will be linked at the end of debug output. You can add e. g.
     * // the HTML path to existing logfiles.
     * $options['ressource_urls'] = array('/contenido/logs/errorlog.txt', '/cms/logs/my_own_log.txt');
     *
     * // Array superglobals to dump automatically, add each superglobal, but not $GLOBALS
     * $options['dump_super_globals'] = array('$_GET', '$_POST', '$_COOKIE', '$_SESSION');
     *
     * // Flag to ignore dumpoutput of empty superglobals
     * $options['ignore_empty_superglobals'] = true;
     *
     * // Maximum allowed superglobal size in KB (used for $_POST and $_SESSION)
     * $options['max_superglobals_size'] = 512;
     *
     * // Magic word to use, if you want to overwrite $options['enable'] option. You can force debugging
     * // by using this option. In this case, you can enable it adding the parameter
     * // magic_word={my_magic_word} to the URL, e. g.
     * // http://domain.tld/mypage.php?magic_word={my_magic_word}
     * // After then debugging will be enabled for the next 1 hour (set by cookie)
     * $options['magic_word'] = 'foobar';
     *
     * // Second way to overwrite option $options['enable']. Here you can define a own function, which
     * // should check, if debugging is to enable or not. The function should return a boolean value,
     * // true to enable it, or false to disable.
     * $options['user_func'] = 'myFunctionName';
     * </code>
     * @return  void
     */
    public function setConfig(array $options)
    {
        //enable/disable debugging
        if (isset($options['enable'])) {
            $this->_enable = (bool) $options['enable'];
        }

        if (isset($options['magic_word'])){
            // debug enable check with magic_word send by request
            $cookieVal = md5($options['magic_word']);
            if (isset($_GET['magic_word']) == $options['magic_word']) {
                @setcookie('mpDebug_mw', $cookieVal, time() + 3600); // enable debugging for 1 hour
                $this->_enable = true;
            } elseif (isset($_COOKIE['mpDebug_mw']) && $_COOKIE['mpDebug_mw'] == $cookieVal) {
                $this->_enable = true;
            }
        } elseif (isset($options['user_func']) && is_function($options['user_func'])) {
            // debug enable check with userdefined function
            $this->_enable = call_user_func($options['user_func']);
        }

        // ressource urls
        if (isset($options['ressource_urls']) && is_array($options['ressource_urls'])) {
            $this->_resUrls = $options['ressource_urls'];
        }

        // auto dump of globals or superglobals
        if (isset($options['dump_super_globals']) && is_array($options['dump_super_globals'])) {
            $this->_dumpSuperglobals = $options['dump_super_globals'];
        }

        // auto dump of globals or superglobals
        if (isset($options['max_superglobals_size']) && (int) $options['max_superglobals_size'] > 0) {
            $this->_maxSuperglobalSize = (int) $options['max_superglobals_size'];
        }

        // ignore dumping of empty superglobals
        if (isset($options['ignore_empty_superglobals'])) {
            $this->_ignoreEmptySuperglobals = (bool) $options['ignore_empty_superglobals'];
        }
    }


    /**
     * Wrapper 4 var_dump function. Dumps content of passed variable.
     *
     * @param   mixed   $var     Variable 2 dump content
     * @param   string  $source  Name of source
     * @param   bool    $print   Flag to print out dump result
     * @return  mixed   Content of var, if its not to print
     */
    public function vdump($var, $source='', $print=true)
    {
        if ($this->_enable == false) {
            return;
        }

        if ($source !== '') {
            $source .= ":\n";
        }

        $dump = "\n" . $source . "\n" . print_r($var, true) . "\n";

        if ($print == true) {
            print $dump;
        } else {
            return $dump;
        }
    }


    /**
     * Adds a variable dump.
     *
     * @param   mixed   $var     Variable 2 dump
     * @param   string  $name    Additional info like name or whatever you wan't do describe the passed variable
     * @param   string  $source  Filename (e. g. __FILE__) to specify the location of caller
     * @param   string  $line    Line (e. g. __LINE__) to specifiy the line number
     * @return  void
     */
    public function addVdump($var, $name, $source=null, $line=null)
    {
        if ($this->_enable == false) {
            return;
        }

        $this->_addDebugValue($this->vdump($var, $source, false), $name, $source, $line);
    }


    /**
     * Adds a variable dump, does the same as addVdump().
     *
     * @param   mixed   $var     Variable 2 dump
     * @param   string  $name    Additional info like name or whatever you wan't do describe the passed variable
     * @param   string  $source  Filename (e. g. __FILE__) to specify the location of caller
     * @param   string  $line    Line (e. g. __LINE__) to specifiy the line number
     * @return  void
     */
    public function addDebug($var, $name, $source=null, $line=null)
    {
        if ($this->_enable == false) {
            return;
        }

        $this->_addDebugValue($var, $name, $source, $line);
    }


    /**
     * Adds passed variable to the debug cache.
     *
     * @param   mixed   $var     Variable 2 dump
     * @param   string  $name    Additional info like name or whatever you wan't do describe the passed variable
     * @param   string  $source  Filename (e. g. __FILE__) to specify the location of caller
     * @param   string  $line    Line (e. g. __LINE__) to specifiy the line number
     * @return  void
     */
    private function _addDebugValue($var, $name, $source, $line)
    {
        if ($this->_enable == false) {
            return;
        }
        if ($source !== null) {
            if ($this->_trimDocRoot == true) {
                if (DIRECTORY_SEPARATOR == "\\") {
                    $source = str_replace(DIRECTORY_SEPARATOR, '/', $source);
                }
                $source = str_replace($this->_docRoot, '', $source);
            }
        }
        $arr['var']    = $var;
        $arr['name']   = $name;
        $arr['source'] = $source;
        $arr['line']   = $line;

        $this->_dumpCache[] = $arr;
    }


    /**
     * Main method to get the mpWebDebug bar.
     *
     * Returns or prints the mpWebDebug bar depending on state of $print
     *
     * @param   bool   $print  Flag to print the mpWebDebug bar
     * @return  mixed  The mpWebDebug bar if $print is set to false or nothing
     */
    public function getResults($print=true)
    {
        if ($this->_enable == false) {
            return;
        }

        $dumpOutput  = '';
        $dumpOutput .= $this->_startOutput();

        // dump superglobals
        foreach ($this->_dumpSuperglobals as $p => $name) {
            $dumpSG = true;
            $SG = $this->_getSuperGlobal($name);
            if ($this->_ignoreEmptySuperglobals) {
                if ((is_array($SG) && count($SG) == 0) || !isset($SG) || empty($SG)) {
                    $dumpSG = false;
                }
            }
            if ($dumpSG) {
                $dumpOutput .= $this->_contentOutput($name, $SG);
            }
        }

        // dump cache
        foreach ($this->_dumpCache as $p => $v) {
            $info = array();
            if ($v['source'] !== null) {
                $info[] = 'source: ' . $v['source'];
            }
            if ($v['line'] !== null) {
                $info[] = 'line: ' . $v['line'];
            }
            if (count($info) > 0) {
                $info = implode(', ', $info) . "\n";
            } else {
                $info = '';
            }
            $dumpOutput .= $this->_contentOutput($v['name'], $v['var'], $info);
        }

        // add ressource links
        $dumpOutput .= $this->_getRessourceLinks();
        $dumpOutput .= '
<span class="info"><br />
    NOTE: This debug output should be visible only on dev environment<br />
    &copy; Murat Purc 2008-2009 &bull; <a href="http://www.purc.de/">www.purc.de</a>
</span>';
        $dumpOutput .= $this->_endOutput();

        if ($print == true) {
            print $dumpOutput;
        } else {
            return $dumpOutput;
        }
    }


    /**
     * Decorates given data with a html comment and returns it back or prints it out.
     *
     * @param   string  $content  Data to decorate with comment
     * @param   bool    $print    Flag to print the result
     * @return  mixed   The composed result or nothing if is to print
     */
    public function comment($content, $print=true)
    {
        if ($this->_enable == false) {
            return;
        }

        $comment = "\n<!-- " . $content . " -->\n";
        if ($print == true) {
            print $comment;
        } else {
            return $comment;
        }
    }


    /**
     * Returns the code (CSS-/ and JavaScript part) for the mpDebugBar, which can be placed inside
     * the head-Tag.
     *
     * Prevents multiple delivering of the code using a static variable. Only the first call will
     * return the code.
     *
     * @return  string  CSS-/JS-Code of mpWebDebug bar
     */
    public function getCssJsCode()
    {
        static $alreadyDelivered;

        if (isset($alreadyDelivered)) {
            // code for head tag is to deliver once, and it was already delivered, return empty string
            return '';
        }

        $alreadyDelivered = true;

        $code = '
<style type="text/css"><!--
#mpWebDebug {position:absolute;z-index:1000;width:100%;right:0;top:0;font-size:12px;font-family:arial;text-align:left;}
#mpWebDebug #webDebugAnchorBox {padding:2px;background:transparent;}
#mpWebDebug .anchor {font-weight:bold;font-size:0.8em;color:#52bd22;margin:0;padding:0.3em;background:#575555;}
#mpWebDebug .anchor a {font-weight:bold;font-family:arial;color:#52bd22;}
#mpWebDebug #webDebugBox {margin-top:18px;padding:5px;position:absolute;left:0;top:0;background-color:#dadada;border:1px black solid;width:99%;overflow:auto;}
#mpWebDebug .iN {margin:0.3em 0; background:transparent;} /* iN = item name */
#mpWebDebug .iN a, #mpWebDebug .iN a:hover {display:block;font-size:9pt;font-family:arial;font-weight:bold;color:black;background:transparent;background:#cacaca;}
#mpWebDebug .iN a:hover, #mpWebDebug .iN a.open {background:#bababa;}
#mpWebDebug .anchor a:focus, #mpWebDebug .iN a:focus {outline:0;}
#mpWebDebug .iI {text-decoration:underline;} /* iI = item info */
#mpWebDebug .iV {padding-left:0.5em;color:#000;} /* iv = item value */
#mpWebDebug .nV {display:none;} /* nV = not visible */
#mpWebDebug .info {color:#007f46;font-size:11px;}
#mpWebDebug pre {margin:0;color:#000;}
// --></style>
<script type="text/javascript"><!--//<![CDATA[
var mpWebDebug = {
    _toggle: [],
    toggle: function(id, obj) {
        var displayVal;
        if (typeof(this._toggle[id]) == "undefined") {
            displayVal = "block";
        } else {
            displayVal = (this._toggle[id] == "block") ? "none" : "block";
        }
        try {
            var elem = document.getElementById(id);
            elem.style.display = displayVal;
            this._setOpenStatus(elem, displayVal);
            if (typeof(obj) == "object") {
                this._setOpenStatus(obj, displayVal);
            }
            this._toggle[id] = displayVal;
        } catch(e) {/*alert(e);*/}
    },
    _setOpenStatus: function (elem, style) {
        var cn = elem.className.split(" ");
        var pos = elem.className.search(/open/);
        var newCn = [];
        if (style == "block" && pos == -1) {
            newCn = cn;
            newCn.push("open");
        } else if (style == "none" && pos > -1) {
            for (var i=0; i<cn.length; i++) {
                if (cn[i] !== "open") {
                    newCn.push(cn[i]);
                }
            }
        } else {
            newCn = cn;
        }
        elem.className = newCn.join(" ");
    }
}
//]]>--></script>
';
        return $code;
    }


    /**
     * Returns the mpWebDebug bar header.
     *
     * @return  string  Code of the mpWebDebug bar, either with or without CSS/JavaScript code block.
     */
    private function _startOutput()
    {
        // get head code (css/js)
        $out = $this->getCssJsCode();

        $out .= '
<div id="mpWebDebug">
<div id="webDebugAnchorBox"><span class="anchor"><a href="#" onclick="mpWebDebug.toggle(\'webDebugBox\', this);return false;">DEBUG</a></span></div>
<div id="webDebugBox" class="nV">
<div id="jsDebugBox" style="display:none;">
<div class="iN">
    <a href="#" onclick="mpWebDebug.toggle(\'jsDebug\', this);return false;">&diams; JavaScript</a>
</div>
<div id="jsDebug" class="nV"></div>
</div>
';
        return $out;
    }


    /**
     * Prints the content of passed debug item, depending on its type (array, object, string)
     *
     * @param   string  $name  Name of the variable
     * @param   mixed   $var   The variable itself
     * @param   string  $info  Info about the variable
     * @return  string  Container with debug info about the variable
     */
    private function _contentOutput($name, $var, $info=null)
    {
        $id = $this->_nextId();

        if ($info !== null) {
            $info_item = '<div class="iI">' . str_replace("\n", "<br />\n", $info) . '</div>';
        } else {
            $info_item = '';
        }
        $out = '
<div class="iN">
    <a href="#" onclick="mpWebDebug.toggle(\'' . $id . '\', this);return false;">&diams; '.$name.'</a>
</div>
<div id="'.$id.'" class="iV nV">
' . $info_item . '
<pre>';

        if (is_array($var)) {
            $out .= $this->_dumpVar($var);
        } elseif (is_object($var)) {
            $out .= $this->_dumpVar($var);
        } elseif (is_string($var) && !empty($var)) {
            $out .= $name . ' = ' . htmlspecialchars($var);
        } elseif (is_null($var)) {
            $out .= $name . ' = is_null';
        } elseif (empty($var)) {
            $out .= $name . ' = empty';
        } elseif (!isset($var)) {
            $out .= $name . ' = !isset';
        } else {
            $out .= $name . ' = ' . $var;
        }

        $out .= '</pre>
</div>
';
        return $out;
    }


    /**
     * Creates list of linked ressource files. The result will be added to mpWebDebug.
     *
     * @return  string
     */
    private function _getRessourceLinks()
    {
        if (!is_array($this->_resUrls)) {
            return;
        }
        $reslinks = '';
        foreach ($this->_resUrls as $p => $url) {
            if (is_readable($this->_docRoot . $url)) {
                $reslinks .= '
    <li><a href="' . $url . '" onclick="window.open(this.href);return false;">' . $url . '</a></li>
';
            }
        }
        if ($reslinks !== '') {
            $id = $this->_nextId();
            $reslinks = '
<div class="iN">
    <a href="#" onclick="mpWebDebug.toggle(\'' . $id. '\', this);return false;">&diams; Ressource Links</a>
</div>
<div id="' . $id . '" class="nV">
    <ul style="padding:0;">
    ' . $reslinks . '
    </ul>
</div>
';
        }
        return $reslinks;
    }


    /**
     * Returns the footer of mpWebDebug
     *
     * @return  string
     */
    private function _endOutput()
    {
        $out = '
</div>
</div>
';
        return $out;
    }


    /**
     * Dumps passed variable.
     *
     * @param   mixed   $var  Variable to dump content
     * @return  string  Content
     */
    private function _dumpVar(&$var)
    {
        $out = print_r($var, true);
        $out = htmlspecialchars($out);
        $this->_prepareDumpOutput($out);
        return $out;
    }


    /**
     * Prepares data dump for output. Clears some white space characters and line endings to get an
     * compact an more readable result.
     *
     * @param   string  $dumpOutput  Output of var_dump()
     * @return  void
     */
    private function _prepareDumpOutput(&$dumpOutput)
    {
        $dumpOutput = preg_replace("/(Array\n)([\s{1,*}]*)(\()/", 'Array (', $dumpOutput);
        $dumpOutput = preg_replace("/(Array\(\n)([\s{1,*}]*)(\))/", 'Array ()', $dumpOutput);
        $dumpOutput = preg_replace("/(Object\n)([\s{1,*}]*)(\()/", 'Object (', $dumpOutput);
        $dumpOutput = preg_replace("/(Object\(\n)([\s{1,*}]*)(\))/", 'Object ()', $dumpOutput);
    }


    /**
     * Returns the Superglobal variable by name. Provides a precheck of superglobal
     * size to prevend debugging variables having several MB. e. g. $_POST.
     *
     * Does not support the return of $GLOBALS. This variable contains since
     * PHP 5 several cross references, therefore a dump will result in crossing
     * memory limit or script timeout.
     *
     * @param   string  $name  Name of superglobal
     * @return  mixed   Content of superglobal or a message or null
     */
    private function _getSuperGlobal($name)
    {
        switch ($name) {
            case '$_GET':
                return $_GET;
                break;
            case '$_POST':
                // simple check of post size
                if ($sizeKB = $this->_superGlobalTooBig($_POST)) {
                    // content of _POST is > 512 KB
                    return 'Content of POST seems to be big, approximate calculated size is ' . $sizeKB . ' KB';
                }
                return $_POST;
                break;
            case '$_REQUEST':
                return $_REQUEST;
                break;
            case '$_COOKIE':
                return $_COOKIE;
                break;
            case '$_SESSION':
                // simple check of session size
                if ($sizeKB = $this->_superGlobalTooBig($_SESSION)) {
                    // content of _SESSION is > 512 KB
                    return 'Content of SESSION seems to be big, approximate calculated size is ' . $sizeKB . ' KB';
                }
                return $_SESSION;
                break;
            case '$_FILES':
                return $_FILES;
                break;
            case '$_SERVER':
                return $_SERVER;
                break;
            case '$_ENV':
                return $_ENV;
                break;
            default:
                return null;
                break;
        }
    }


    /**
     * Returns approximate size of superglobal in kb if size is > defined max superglobal size or false
     *
     * @param   mixed  $sglobal
     * @return  mixed  Size in kb or false
     */
    private function _superGlobalTooBig(&$sglobal)
    {
        // simple check of variable size
        $dump = print_r($sglobal, true);
        $dump = preg_replace("/\n +/", '', $dump);
        $sizeInKB = ceil((strlen($dump) / 1024));
        if ($sizeInKB > $this->_maxSuperglobalSize) {
            return $sizeInKB;
        } else {
            return false;
        }
    }

    /**
     * Creates a unique id used as id-Attribute for HTML elements using timer and internal counter.
     *
     * @return  string  Generated id
     */
    private function _nextId()
    {
        return 'id' . time() . '_' . $this->_counter++;
    }

}
