<?php
/**
 * Example usage of mpWebDebug bar.
 * 
 * @package     Development
 * @subpackage  Debugging
 * @author      Murat Purç <murat@purc.de>
 * @copyright   Copyright (c) 2008-2019 Murat Purç (http://www.purc.de)
 * @license     https://www.gnu.org/licenses/gpl-2.0.html - GNU General Public License, version 2
 */


####################################################################################################
##### Example usage of mpWebDebug bar

// include the class file
require_once 'class.mpdebug.php';

// create instance of the debugger class
$mpDebug = new \Purc\MpWebDebug\Debugger();


// set configuration
$options = [
    'enable'                    => true,
    'ressource_urls'            => ['/path_to_logs/error.txt'], // this is a not working example ;-)
    'dump_super_globals'        => ['$_GET', '$_POST', '$_COOKIE', '$_SESSION'],
    'ignore_empty_superglobals' => true,
    'max_superglobals_size'     => 512
];
$mpDebug->setConfig($options);


// debug: text (string) example
$foo = 'Text';
$mpDebug->addDebug($foo, 'Content of foo');


// debug: array example
$bar = ['win', 'nix', 'apple'];
$mpDebug->addDebug($bar, 'win, nix and apple', __FILE__, __LINE__);

// debug: object example
class User {
    const MR  = 'Mr.';
    const MRS = 'Mrs.';
    private $firstName;
    private $lastName;
    private $gender;
    public function __construct($firstName, $lastName, $gender) {
        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->gender    = ($gender == self::MR) ? self::MR : self::MRS;
    }
    public function greet() {
        return 'Hi ' . $this->gender . ' ' . $this->lastName;
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


// debug: !isset example, commented due to thrown php notice message
#$mpDebug->addDebug($notIssetVar, 'This variable is not set');


// assign code of mpWebDebug bar to an variable
$content = $mpDebug->getResults(false);


####################################################################################################
##### Template Output of

echo <<<HTML
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>mpWebDebug example 1</title>
        <style>
            body {font-family:arial,serif; font-size:12pt;}
        </style>
    </head>
    <body>

        <h1>mpWebDebug bar example 1</h1>

        <div>
            <p>Simple example of usage, see Sourcecode of example1.php for details.</p>

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
