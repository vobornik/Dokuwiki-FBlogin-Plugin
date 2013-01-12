<?php
/**
 * Facebook authentication backend
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Václav Voborník <nomail@vobornik.eu>
 */

define('DOKU_AUTH', dirname(__FILE__));
require_once(DOKU_AUTH.'/basic.class.php');
require_once(DOKU_AUTH.'/../../lib/plugins/fblogin/lib/facebook.php');

#define('AUTH_USERFILE',DOKU_CONF.'users.auth.php');

class auth_facebook extends auth_basic {

  var $users = null;
  var $_pattern = array();

  var $fbsession = array();

  /**
   * Constructor
   *
   * Carry out sanity checks to ensure the object is
   * able to operate. Set capabilities.
   *
   * @author  Václav Voborník <nomail@vobornik.eu>
   */
  function auth_facebook() {
    global $conf;
    $this->cando['external']     = true;

    if (!isset($conf['plugin']['fblogin']['applicationID']) || empty($conf['plugin']['fblogin']['applicationID'])) {
      $this->success = false;
      return;
    }

    if (!isset($conf['plugin']['fblogin']['applicationSecret']) || empty($conf['plugin']['fblogin']['applicationSecret'])) {
      $this->success = false;
      return;
    }



    $this->success = true;
    return;

  }
  function trustExternal($user,$pass,$sticky=true ){
    global $USERINFO;
    global $conf;
    $sticky ? $sticky = true : $sticky = false; //sanity check

    

  if ($conf['plugin']['fblogin']['applicationID'] && $conf['plugin']['fblogin']['applicationSecret']) {

    $facebook = new Facebook(array(
      'appId'      => $conf['plugin']['fblogin']['applicationID'],
      'secret'     => $conf['plugin']['fblogin']['applicationSecret'],
      'cookie'     => true,
    ));
    $fbsession = $facebook->getUser();
    
      if($_REQUEST['do'] == 'logout'){
        $logoutUrl = $facebook->getLogoutUrl(
          array(
            'next' => $_SERVER['HTTP_REFERER'],
          )
        );
        unset($fbsession);
#        unset($_SESSION[DOKU_COOKIE]['auth']['user']);
#        unset($_SESSION[DOKU_COOKIE]['auth']['buid']);
#        unset($_SESSION[DOKU_COOKIE]['auth']['pass']);
#        unset($_SESSION[DOKU_COOKIE]['auth']['info']);
        session_destroy();
        error_log('fblogin : authenticated user redirected for logout to '.$logoutUrl);
        header("Location: ".$logoutUrl);
        exit;
      }
    if ($fbsession) {
      try {
        $me = $facebook->api('/me');
#        $friends = $facebook->api('/me/friends');  // for future usage

      } catch (FacebookApiException $e) {
        error_log($e);
      }
      if ($me) {
        $conf['superuser']   = $conf['plugin']['fblogin']['superuser'];

        $USERINFO['name'] = $me['name'];
        $USERINFO['mail'] = $me['email'];
        $USERINFO['grps'] = array('user');
        $user = $me['id'];
        $pass = '';
        $_SERVER['REMOTE_USER'] = $user;
        $_SESSION[DOKU_COOKIE]['auth']['user'] = $user;
        $_SESSION[DOKU_COOKIE]['auth']['pass'] = $pass;
        $_SESSION[DOKU_COOKIE]['auth']['info'] = $USERINFO;
      } //me
    }  // FB session


    if($_REQUEST['do'] == 'login'){
      $loginUrl = $facebook->getLoginUrl(
        array(
          'next' => $_SERVER['HTTP_REFERER'],
          'redirect_uri' => $conf['baseurl'],
          'canvas'    => 0,
          'fbconnect' => 1,
#          'req_perms' => 'publish_stream,status_update'   //for future usage
        )
      );
      header("Location: ".$loginUrl);
      exit;
    }
  }
  return true;
}

    /**
     * Check user+password [required auth function]
     *
     * Checks if the given user exists and the given
     * plaintext password is correct
     *
     * @author  
     * @return  bool
     */
    function checkPass($user,$pass){

    }

    /**
     * Return user info
     *
     * Returns info about the given user needs to contain
     * at least these fields:
     *
     * name string  full name of the user
     * mail string  email addres of the user
     * grps array   list of groups the user is in
     *
     * @author  Václav Voborník <nomail@vobornik.eu>
     */
  function getUserData($user){
    global $conf;


    if (!$conf['plugin']['fblogin']['applicationID'] || !$conf['plugin']['fblogin']['applicationSecret']) {
      return false;
    }
    $facebook = new Facebook(array(
      'appId'      => $conf['plugin']['fblogin']['applicationID'],
      'secret'     => $conf['plugin']['fblogin']['applicationSecret'],
      'cookie'     => true,
    ));
    $fbsession = $facebook->getUser();

    if ($fbsession) {
      try {
        $fbuser = $facebook->api("/$user");
      } catch (FacebookApiException $e) {
        error_log($e);
      }
      if ($fbuser) {
        $row['name'] = $fbuser['name'];
        $row['mail'] = $fbuser['mail'];
        $row['grps'] = array ('users');

        return $row;
      }
    }
  }
    /**
     * Create a new User
     *
     * Returns false if the user already exists, null when an error
     * occurred and true if everything went well.
     *
     * The new user will be added to the default group by this
     * function if grps are not specified (default behaviour).
     *
     * @author 
     */
    function createUser($user,$pwd,$name,$mail,$grps=null){
    }

    /**
     * Modify user data
     *
     * @author  
     * @param   $user      nick of the user to be changed
     * @param   $changes   array of field/value pairs to be changed (password will be clear text)
     * @return  bool
     */
    function modifyUser($user, $changes) {
      global $conf;
      global $ACT;
      global $INFO;

    }

    /**
     *  Remove one or more users from the list of registered users
     *
     *  @author  
     *  @param   array  $users   array of users to be deleted
     *  @return  int             the number of users deleted
     */
    function deleteUsers($users) {

    }

    /**
     * Return a count of the number of user which meet $filter criteria
     *
     * @author  
     */
    function getUserCount($filter=array()) {

    }

    /**
     * Bulk retrieval of user data
     *
     * @author  
     * @param   start     index of first user to be returned
     * @param   limit     max number of users to be returned
     * @param   filter    array of field/pattern pairs
     * @return  array of userinfo (refer getUserData for internal userinfo details)
     */
    function retrieveUsers($start=0,$limit=0,$filter=array()) {


    }

    /**
     * Only valid pageid's (no namespaces) for usernames
     */
    function cleanUser($user){
        global $conf;
        return cleanID(str_replace(':',$conf['sepchar'],$user));
    }

    /**
     * Only valid pageid's (no namespaces) for groupnames
     */
    function cleanGroup($group){
        global $conf;
        return cleanID(str_replace(':',$conf['sepchar'],$group));
    }


}

//Setup VIM: ex: et ts=2 enc=utf-8 :
