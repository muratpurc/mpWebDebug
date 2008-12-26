<?php
/**
 * Example usage of mpWebDebug bar.
 *
 * This Script shows the usage of separated output of debug contents, the css-/js-code for head-part
 * and the rest for content.
 * 
 * @author      Murat Purc <murat@purc.de>
 * @copyright   © Murat Purc 2008
 * @package     Development
 * @subpackage  Debugging
 */


####################################################################################################
##### Example usage of mpWebDebug bar

// include the class file
require_once('class.mpdebug.php');


// get instance of the mpdebug class
$mpDebug = mpDebug::getInstance();


// set configuration
$options = array(
    'enable'                    => true,
    'ressource_urls'            => array('/path_to_logs/error.txt'), // this is a not working exaqmple ;-)
    'dump_super_globals'        => array('$_GET', '$_POST', '$_COOKIE', '$_SESSION'),
    'ignore_empty_superglobals' => true,
    'max_superglobals_size'     => 512
);
$mpDebug->setConfig($options);


// debug: text (string) example
$foo = 'Text';
$mpDebug->addDebug($foo, 'Content of foo');


// debug: array example
$bar = array('win', 'nix', 'apple');
$mpDebug->addDebug($bar, 'win, nix and apple', __FILE__, __LINE__);

// debug: object example
class User {
    const MR  = 'Mr.';
    const MRS = 'Mrs.';
    private $firstname;
    private $lastname;
    private $gender;
    public function __construct($firstname, $lastname, $gender){
        $this->firstname = $firstname;
        $this->lastname  = $lastname;
        $this->gender    = ($gender == self::MR) ? self::MR : self::MRS;
    }
    public function greet() {
        return 'Hi ' . $this->gender . ' ' . $this->lastname;
    }
}

$user = new User('John O.', 'Public', User::MR);
$mpDebug->addDebug($user, 'User object');


// debug: 2. text example
$greet = $user->greet();
$mpDebug->addDebug($greet, 'Greet user');


// debug: null example
$nullVar = null;
$mpDebug->addDebug($nullVar, 'Variable having null');


// debug: empty example
$emptyVar = '';
$mpDebug->addDebug($emptyVar, 'Empty variable');


// debug: !isset example, commentent due to thrown php notice message
#$mpDebug->addDebug($notIssetVar, 'This variable is not set');


// get only css/js code which could paste inside head-tag
$head = $mpDebug->getCssJsCode();

// assign code of mpWebDebug bar to an variable
$content = $mpDebug->getResults(false);


####################################################################################################
##### Template Output of

echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/Strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="de">
<head>
<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=iso-8859-1" />
<title> mpWebDebug example 1 </title>
<style type="text/css"><!--
body {font-family:arial; font-size:12pt;}
// --></style>
$head
</head>

<body>

<h1>mpWebDebug bar example 2</h1>

<div>
    <p>
    This example shows how to paste CSS/JS code of the mpWebDebug bar intro head-tag, which is usefull, 
    if you want to get a valid output.<br />
    See Sourcecode of example2.php for details.
    </p>

    <p>
    Lorem ipsum dolor sit amet, consectetur adipisci elit. Nunc ac ante sed ante imperdiet auctor. Fusce 
    dignissim, magna eu feugiat tincidunt, nibh metus tincidunt augue, quis ullamcorper lorem pede a ante. 
    Proin congue nisl a arcu. Donec et elit. Etiam ac eros nec metus molestie aliquam. Nullam vestibulum 
    molestie magna. In varius quam in nulla luctus tristique. Nam et eros. Sed vitae sem a velit mattis 
    dapibus. Sed blandit, sapien auctor adipiscing viverra, purus urna fermentum wisi, id luctus tortor 
    augue et ligula. In quis libero. Sed urna arcu, malesuada in, adipiscing vitae, vehicula vitae, magna. 
    Phasellus sit amet nisl at erat aliquet eleifend. Quisque malesuada porta elit. Nulla nec orci ac 
    leo posuere eleifend. Aliquam ultrices vulputate velit. Vestibulum vitae ipsum. Vestibulum pede erat, 
    cursus nec, porttitor ac, accumsan ut, neque.
    </p>

    <p>
    Aenean vel mi. Donec blandit mauris convallis lacus. Sed a urna. Vestibulum ante ipsum primis in 
    faucibus orci luctus et ultrices posuere cubilia Curae; Vivamus hendrerit. Curabitur libero leo, 
    laoreet nec, lobortis in, auctor malesuada, metus. Vivamus ultrices eros eget pede. Morbi facilisis 
    leo ut elit. Fusce viverra iaculis risus. Pellentesque posuere faucibus sem. Praesent et felis ac 
    lorem laoreet venenatis.
    </p>

    <p>
    Etiam pede. Sed et orci quis nulla condimentum suscipit. Fusce quam lectus, tincidunt quis, gravida 
    vel, interdum non, quam. Phasellus nibh pede, rhoncus id, bibendum non, eleifend sit amet, dui. 
    Integer non nibh quis magna elementum condimentum. Etiam varius iaculis nunc. Curabitur et metus in 
    lectus malesuada venenatis. Aliquam erat volutpat. Aliquam sit amet ligula ut eros consequat laoreet. 
    Ut accumsan, urna eu ullamcorper fermentum, ipsum nunc aliquam odio, lobortis interdum sem leo sed 
    metus. Nam quis purus a est luctus laoreet. Nulla bibendum. Sed non pede non quam dictum eleifend.
    </p>
    
</div>

<!-- output of the mpWebDebug bar code -->
$content

</body>
</html>
HTML;
