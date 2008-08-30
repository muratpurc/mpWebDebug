<?php
/**
 * Contains a debug class used as a helper during development.
 * 
 * @author      Murat Purc <murat@purc.de>
 * @copyright   © Murat Purc 2008
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
 * Inspired by the Web Debug Toolbar of symfony Framework whith a simple way to 
 * provide debug informations during development.
 *
 * See example1.php and example1.php in delivered package.
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
 *     'ressource_urls'            => array('/path_to_logs/error.txt'), // this is a not working exaqmple ;-)
 *     'dump_super_globals'        => array('$_GET', '$_POST', '$_COOKIE', '$_SESSION'),
 *     'ignore_empty_superglobals' => true,
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
 * @copyright   © Murat Purc 2008
 * @license     http://www.gnu.org/licenses/gpl-2.0.html - GNU General Public License, version 2
 * @version     0.9
 * @package     Development
 * @subpackage  Debugging
 */
class mpDebug {

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
     * Constructor
     */
    public function __construct(){
        if ($this->_trimDocRoot == true) {
            $this->_docRoot = $_SERVER['DOCUMENT_ROOT'];
            if (DIRECTORY_SEPARATOR == "\\") {
                 $this->_docRoot = str_replace(DIRECTORY_SEPARATOR, '/', $this->_docRoot);
             }
        }
    }


    /**
     * Returns a instance of mpDebug (singleton implementation)
     *
     * @return  mpDebug
     */
    public static function getInstance() {
        if (self::$_instance == null) {
            self::$_instance = new mpDebug();
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
     */
    public function setConfig(array $options) {
        
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
     * @return  mixed            Content of var, if its not to print
     */
    public function vdump($var, $source='', $print=true) {
        if ($this->_enable == false) {
            return;
        }

        if ($source !== '') { $source .= ":\n"; }
        ob_start();
        print "\n".$source."\n";
        print_r($var);
        print "\n";
        $dump = ob_get_contents();
        ob_end_clean();

        if ($print == true) {
            print $dump;
        } else {
            return $dump;
        }
    }


    /**
     * Adds a varible dump.
     * 
     * @param  mixed   $var     Variable 2 dump
     * @param  string  $name    Additional info like name or whatever you wan't do describe the passed variable
     * @param  string  $source  Filename (e. g. __FILE__) to specify the location of caller
     * @param  string  $line    Line (e. g. __LINE__) to specifiy the line number
     */
    public function addVdump($var, $name, $source=null, $line=null) {
        if ($this->_enable == false) {
            return;
        }
        
        $this->_addDebugValue($this->vdump($var, $source, false), $name, $source, $line);
    }


    /**
     * Adds a varible dump, does the same as addVdump().
     * 
     * @param  mixed   $var     Variable 2 dump
     * @param  string  $name    Additional info like name or whatever you wan't do describe the passed variable
     * @param  string  $source  Filename (e. g. __FILE__) to specify the location of caller
     * @param  string  $line    Line (e. g. __LINE__) to specifiy the line number
     */
    public function addDebug($var, $name, $source=null, $line=null){
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
     * @access  private
     */
    private function _addDebugValue($var, $name, $source, $line){
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
     * Main method to get the mpWebDebug Bar.
     * 
     * Returns or prints the mpWebDebug Bar depending on state of $print
     *
     * @param  bool  $print  Flag to print the mpWebDebug Bar
     * @return  mixed  The mpWebDebug Bar if $print is set to false or nothing
     */
    public function getResults($print=true) {
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
            $info = '';
            if ($v['source'] !== null) {
                $info .= 'source: ' . $v['source'] . "\n";
            }
            if ($v['line'] !== null) {
                $info .= 'line: ' . $v['line'] . "\n";
            }
            $dumpOutput .= $this->_contentOutput($v['name'], $v['var'], $info);
        }

        // add ressource links
        $dumpOutput .= $this->_getRessourceLinks();
        $dumpOutput .= '
<span class="info"><br />
    NOTE: This debug output should be visible only on dev environment<br />
    &copy; Murat Purc 2008 &bull; <a href="http://www.purc.de/">www.purc.de</a>
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
     * @return  mixed             The composed result or nothing if is to print
     */
    public function comment($content, $print=true){
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
    public function getCssJsCode() {
        static $alreadyDelivered;
        
        if (isset($alreadyDelivered)) {
            // code for head tag is to deliver once, and it was already delivered, return empty string
            return '';
        }
        
        $alreadyDelivered = true;

        $code = '
<style type="text/css"><!--
#mpWebDebug {position:absolute;z-index:1000;width:100%;right:0;top:0;font-size:12px;font-family:arial;text-align:left;}
#mpWebDebug #webDebugAnchorBox {padding:0.2em;background:transparent;}
#mpWebDebug .anchor {font-weight:bold;font-size:0.8em;color:#52bd22;margin:0;padding:0.3em;background:#575555;}
#mpWebDebug .anchor a {font-weight:bold;font-family:arial;color:#52bd22;}
#mpWebDebug #webDebugBox {margin-top:1.3em;padding:5px;position:absolute;left:0;top:0;background-color:#dadada;border:1px black solid;width:99%;overflow:auto;}
#mpWebDebug .outputItemName {margin:0.3em 0; background:#cacaca;}
#mpWebDebug .outputItemName a, #mpWebDebug .outputItemName a:hover {display:block;font-size:9pt;font-family:arial;font-weight:bold;color:black; background:transparent;}
#mpWebDebug .outputItemInfo {text-decoration:underline;}
#mpWebDebug .outputItemValue {padding-left:0.5em;color:#000;}
#mpWebDebug .notVisible {display:none;}
#mpWebDebug .info {color:#007f46;font-size:11px;}
#mpWebDebug pre {margin:0;color:#000;}
// --></style>
<script type="text/javascript"><!-- // <![CDATA[
var mpWebDebugger = {
    aToggle: new Array(),
    toggle: function(id){
    	var displayVal;
    	if (typeof(this.aToggle[id]) == "undefined") {
    		displayVal = "block";
    	} else {
    		displayVal = (this.aToggle[id] == "block") ? "none" : "block";
    	}
    	try{
    		document.getElementById(id).style.display = displayVal;
    		this.aToggle[id] = displayVal;
    	} catch(e) {/*alert(e);*/}
    }
}
//]]> --></script>
';
        return $code;
    
    }
    
    
    /**
     * Returns the mpWebDebug bar header.
     *
     * @return  string  Code of the mpWebDebug bar, either with or without CSS/JavaScript code block.
     */
    private function _startOutput(){

        // get head code (css/js)
        $out = $this->getCssJsCode();
        
        $out .= '
<div id="mpWebDebug">
<div id="webDebugAnchorBox"><span class="anchor">
	<a href="javascript:void(0);" onclick="mpWebDebugger.toggle(\'webDebugBox\');return false;">DEBUG</a>
</span></div>
<div id="webDebugBox" class="notVisible">
<div id="jsDebugBox" style="display:none;">
<div class="outputItemName">
    <a href="javascript:void(0);" onclick="mpWebDebugger.toggle(\'jsDebug\');return false;">&diams; JavaScript</a>
</div>
<div id="jsDebug" class="notVisible"></div>
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
    private function _contentOutput($name, $var, $info=null){
        $id = $this->_nextId();

        if ($info !== null) {
            $info_item = '<div class="outputItemInfo">' . str_replace("\n", "<br />\n", $info) . '</div>';
        } else {
            $info_item = '';
        }
        $out = '
<div class="outputItemName">
    <a href="javascript:void(0);" onclick="mpWebDebugger.toggle(\'' . $id . '\');return false;">&diams; '.$name.'</a>
</div>
<div id="'.$id.'" class="outputItemValue notVisible">
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
    private function _getRessourceLinks() {
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
<div class="outputItemName">
    <a href="javascript:void(0);" onclick="mpWebDebugger.toggle(\'' . $id. '\');return false;">&diams; Ressource Links</a>
</div>
<div id="' . $id . '" class="notVisible">
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
    private function _endOutput(){
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
    private function _dumpVar(&$var){
        $out = '';
        ob_start();
        print_r($var);
        $varDump = ob_get_contents();
        ob_end_clean();
        $out .= htmlspecialchars($varDump);
        $this->_prepareDumpOutput($out);
        return $out;
    }


    /**
     * Prepares data dump for output. Clears some white space characters and line endings to get an 
     * compact an more readable result.
     * 
     * @param   string  $dumpOutput  Output of var_dump()
     */
    private function _prepareDumpOutput(&$dumpOutput){
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
    private function _getSuperGlobal($name) {
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
     * Returns approximate size of superglobal in kb if size is > 512 kb or false
     *
     * @param   mixed  $sglobal
     * @return  mixed  Size in kb or false
     */
    private function _superGlobalTooBig(&$sglobal) {
        // simple check of variable size
    	ob_start();
        print_r($sglobal);
        $dump = ob_get_contents();
        ob_end_clean();
        if (strlen($dump) > 524288) {
        	return ceil((strlen($dump) / 1024));
        } else {
        	return false;
        }
    }
    
    /**
     * Creates a unique id used as id-Attribute for HTML elements using timer and internal counter.
     *
     * @return  string  Generated id
     */
    private function _nextId(){
        return 'id' . time() . '_' . $this->_counter++;
    }

}


