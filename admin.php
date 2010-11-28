<?php
/**
 * Setting up the Facebook authentication admin plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Václav Voborník <nomail@vobornik.eu>
 */

if(!defined('DOKU')) define('DOKU',realpath(dirname(__FILE__).'/../../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU.'lib/plugins/');
require_once(DOKU_PLUGIN.'admin.php');
if(!defined('DOKU_CONFIGLANG')) define('DOKU_CONFIGLANG', realpath(dirname(__FILE__).'/../config/lang/'));
if(!defined('DOKU_FBLOGINLANG')) define('DOKU_FBLOGINLANG', realpath(dirname(__FILE__) .'/lang/'));

  


/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_fblogin extends DokuWiki_Admin_Plugin {

  //global $conf;
  /**
   * Return some info about the plugin
   *
   **/
  function getInfo(){

      return array(
		   'author' => 'Václav Voborník',
		   'email'  => 'nomail@vobornik.eu',
		   'date'   => '2010/11/28',
		   'name'   => 'fblogin',
		   'desc'   => 'Plugin to get authentication from Facebook',
		   );
  }


  /**
   * handle user request
   */
  function handle() {
    if ($_REQUEST['action_'] == 'install') {
      $this->install();
    }
    elseif ($_REQUEST['action_'] == 'uninstall') {
      $this->uninstall();
    }      
  }


  /**
   * Output appropriate html
   *
   */
  function html() {
    global $ID;
    global $conf;
    // Display the text about this plugin from "fblogin/lang/$lang/intro.txt"
    print $this->locale_xhtml('intro');
    ptln('<div id="fblofin__manager">');

    // If not installed, it displays the install menu
    // if installed, it displays the uninstall menu
    ptln('<form action_="'.wl($ID).'" method="post">');
    if($this->is_installed()) {
      if($this->is_used()) {
	ptln('<p> ' .$this->getLang('is_used'). '</p>');
      }
      else {
	ptln('<p> '.$this->getLang('uninstall') .' </p>');
	ptln('<p> <input type="hidden" name="action_" value="uninstall" />');
	ptln('    <input type="submit" name="submit" class="button" value="'.$this->getLang('btn_uninstall').'" /> </p>');
      }
    }
    else {
      ptln('<p> '.$this->getLang('install') .' </p>');
      ptln('<p> <input type="hidden" name="action_" value="install" />');
      ptln('    <input type="submit" name="submit" class="button" value="'.$this->getLang('btn_install').'" /> </p>');
    }
    ptln('  <p> <input type="hidden" name="do"     value="admin" />');
    ptln('      <input type="hidden" name="page"   value="fblogin" /> </p>');
    ptln('</form>');
    ptln('</div>');
  }

  /**********************
   * Check if the plugin
   * is installed
   *
   **********************/
  function is_installed() {
    //Check if the file facebook.class.php is in the authenticafion methods directory
    if (!file_exists(DOKU . '/inc/auth/facebook.class.php')) {
      return false;
    }


    return true;
  }
   

  /**********************
   * Check if the plugin
   * is currently used
   *
   **********************/
  function is_used() {
    //Check if the authentication mode is facebook
    if (!$local_handle = fopen(DOKU . '/conf/local.php', 'r')) {
      return true;
    } 
    while (!feof($local_handle)) {
      $line = fgets($local_handle, 1000);
      if(preg_match('!\$conf\[\'authtype\'\] *= *.*!', $line)) {
	$value = $line;
      }
      if(preg_match('!\$conf\[\'authtype\'\] *= *[\'"]facebook[\'"]!', $value)) return true;
    }
    fclose($local_handle);
    return false;
  }


  /**********************
   * Install the plugin
   *
   **********************/
  function install() {
    // Check if the plugin files are already there, and delete it if it's the case
    if (file_exists(DOKU . '/inc/auth/facebook.class.php')) {
      if(!unlink(DOKU . '/inc/auth/facebook.class.php')) return false;
    }
    // Copy the new ones
    if (!copy(DOKU .'/lib/plugins/fblogin/files/facebook.class.php', DOKU .'/inc/auth/facebook.class.php')) return false;


    // Install localization for the config plugin
    return($this->install_langconf());
    
    return(true);
  }


  /***********************
   * Uninstall the plugin
   *
   ***********************/
  function uninstall() {
     // Check if the plugin files are already there, and delete it if it's the case
    if (file_exists(DOKU . '/inc/auth/facebook.class.php')) {
      if(!unlink(DOKU . '/inc/auth/facebook.class.php')) return false;
    }
    
    // Uninstall localization for the config plugin
    return($this->uninstall_langconf());
  }


  /**************************
   * Install the language
   * phrases related to this
   * plugin in the config
   * plugin
   *
   **************************/
  function install_langconf() {
    // Read what are the languages available with the config plugin
    if( !$configlang_h = opendir(DOKU_CONFIGLANG) ) return false;
    while ($lang = readdir($configlang_h)) {
      if ($lang != "." && $lang != ".." && is_dir(DOKU_CONFIGLANG.'/'. $lang)) {
	// Then look for every languages in the fblogin plugin
	if(is_dir(DOKU_FBLOGINLANG.'/'. $lang)) {
	  if(!$fblogin_file = fopen(DOKU_FBLOGINLANG .'/' .$lang .'/config_lang.php','r')) return false;
	  $i=0;
	  // And copy the $lang lines from each... assuming that noone would place a ; at the beginning of a new line :-)
	  while (!feof($fblogin_file)) {
	    $lines[$i] = fgets($fblogin_file, 1000);
	    if(!preg_match('!\$lang\[\'\w{1,}\'\] *= *.*!', $lines[$i])) unset($lines[$i--]);
	    $i++;
	  }
	  fclose($fblogin_file);
	  // To the associated language file of the config plugin
	  if(!$config_file= fopen(DOKU_CONFIGLANG .'/'.$lang .'/lang.php','a')) return false;
	  if(!fwrite($config_file,'/* Parameters for authenticating from Facebook */'."\n")) return false;
	  foreach ($lines as $line) {
	    if(!empty($line) && !fwrite($config_file,$line ."\n")) return false;
	  }
	  fclose($config_file);
	}
      }
    }
    closedir($configlang_h);
    return(true);
  }


  /**************************
   * Uninstall the language
   * phrases from the config
   * plugin
   *
   **************************/
  function uninstall_langconf() {
    if( !$configlang_h = opendir(DOKU_CONFIGLANG) ) return false;
    // Delete every line containing the word "fblogin"
    while (false !== ($lang = readdir($configlang_h)) ) {
      if ($lang != "." && $lang != ".." && is_dir(DOKU_CONFIGLANG.'/'. $lang)) {
	if(!$fblogin_file = fopen(DOKU_CONFIGLANG .'/' .$lang .'/lang.php','r')) return false;
	$i = 0;
	while (!feof($fblogin_file)) {
	  $lines[$i] = fgets($fblogin_file, 1000);
	  if(preg_match('!fblogin!i', $lines[$i])) unset($lines[$i--]);
	  $i++;
	}
	fclose($fblogin_file);
	
	// Delete lang.php after having backed it up, then copy $lines to a new lang.php. Restore backup if failure.
	if(!copy(DOKU_CONFIGLANG .'/' .$lang .'/lang.php', DOKU_CONFIGLANG .'/' .$lang .'/lang.php.bak')) return false;
	if(!unlink(DOKU_CONFIGLANG .'/' .$lang .'/lang.php')) return false;
	if (!$fblogin_file = fopen(DOKU_CONFIGLANG .'/' .$lang .'/lang.php', 'w')) {
	  copy(DOKU . '/conf/local.php.bak', DOKU . '/conf/local.php');
	  return false;
	}
	foreach ($lines as $line) {
	  if (!empty($line) && fwrite($fblogin_file, $line) == FALSE) {
	    copy(DOKU . '/conf/local.php.bak', DOKU . '/conf/local.php');
	    return false;
	  }
	}
	fclose($fblogin_file);
      }
    }
  }


}
