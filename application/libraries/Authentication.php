<?php

class Authentication {

  function __construct() {
    $this->load->model('authenticationmodel');
    $this->load->config('authentication');
  }

  public function __get($var){
    return get_instance()->$var;
  }

  public function signin($identity, $password, $remember = false){
    $result['success'] = false;
    $result['message'] = null;
    $result['hash'] = null;

    $user_data = $this->authenticationmodel->get('user', array('username' => $identity));

    if ($this->isMaxAttemptExceeded($user_data->row('id'))){
      $result['message'] = 'max login attemp exceeded';
      return $result;
    }

    if ($user_data->num_rows() <= 0){
      $this->addAttempt($user_data->row('id'));
      $result['message'] = 'user not found';
      return $result;
    }

    if ( $user_data->row('status') != 1){
      $this->addAttempt($user_data->row('id'));
      $result['message'] = 'user inactive';
      return $result;
    }

    if ( ! password_verify($password, $user_data->row('password'))){
      $this->addAttempt($user_data->row('id'));
      $result['message'] = 'password incorrect';
      return $result;
    }

    if ( ! $this->config->item('allow_concurrent_session')){
      $this->authenticationmodel->delete('session', array('user_id' => $user_data->row('id')));
    }

    $session = $this->createSession($user_data->row('id'), $remember);

    if ( ! $session['success']){
      $result['message'] = 'error occured when creating session';
      return $result;
    }

    $result['success'] = true;
    $result['message'] = 'logged in';
    $result['hash'] = $session['session_data']['hash'];
    $result['user'] = $identity;
    return $result;
  }

  private function addAttempt($user_id){
    $this->load->library('user_agent');
    $attempt_data = array(
      'user_id'     => $user_id,
      'ip_address'  => $this->input->ip_address(),
      'user_agent'  => $this->agent->agent_string(),
      'timestamp'   => time()
    );
    $this->authenticationmodel->insert('attempt', $attempt_data);
  }

  private function isMaxAttemptExceeded($user_id){
    $attempt_data = $this->authenticationmodel->get('attempt', array('user_id' => $user_id));
    if ($attempt_data->num_rows() >= $this->config->item('max_login_attempt')){
      return true;
    }
    return false;
  }

  private function clearAttempt($user_id){
    return $this->authenticationmodel->delete('attempt', array('user_id', $user_id));
  }

  private function createSession($user_id, $remember = false){
    $this->load->library('user_agent');
    $this->load->helper('cookie');

    if ($remember){
      $expire_date = strtotime($this->config->item('session_remember'), time());
    } else {
      $expire_date = strtotime($this->config->item('session_forgot'), time());
    }

    $hash = sha1($this->config->item('site_key') . microtime());
    $session_data = array(
      'user_id'     => $user_id,
      'hash'        => $hash,
      'ip_address'  => $this->input->ip_address(),
      'user_agent'  => $this->agent->agent_string(),
      'expire'      => $expire_date,
      'cookie_crc'  => $hash . $this->config->item('site_key'),
      'timestamp'   => time()
    );
    if ($this->authenticationmodel->insert('session', $session_data)){
      return array('success' => true, 'session_data' => $session_data);
    } else {
      return array('success' => false, 'session_data' => null);
    }
  }

  private function checkSession($hash = null){
    $this->load->library('user_agent');
    if (empty($hash)){
      $hash = $this->getCurrentHash();
    }
    $session_data = $this->authenticationmodel->get('session', array('hash' => $hash));

    if ($session_data->num_rows() <= 0){
      return false;
    }

    if (time() > $session_data->row('expire')){
      $this->removeSession($hash);
      return false;
    }

    if ($this->input->ip_address() != $session_data->row('ip_address')){
      return false;
    }

    if ($this->agent->agent_string() != $session_data->row('user_agent')){
      return false;
    }

    if ($session_data->row('expire') - time() < strtotime($this->config->item('session_renew'), time()) - time()){
      if ( ! $this->removeSession($hash) && ! $this->createSession($session_data->user_id)){
        return false;
      }
    }

    return true;
  }

  public function isLoggedIn(){
    return $this->checkSession($this->getCurrentHash());
  }

  private function removeSession($hash){
    if ($this->authenticationmodel->delete('session', array('hash' => $hash))){
      $result['status'] = 'success';
      $result['message'] = 'changes saved';
    } else {
      $result['status'] = 'error';
      $result['message'] = 'error when executing query';
    }
    return $result;
  }

  public function getUserData($hash = null, $with_password = false){
    if (empty($hash)){
      $hash = $this->getCurrentHash();
    }
    $user_data = $this->authenticationmodel->getUserData($hash)->row();
    if ( ! $with_password){
      unset($user_data->password);
    }
    return $user_data;
  }

  private function getCurrentHash(){
    return empty($this->input->cookie($this->config->item('cookie_name'))) ? false : $this->input->cookie($this->config->item('cookie_name'));
  }

  public function createUser($param, $group_id = null){
    if (empty($param) || ! is_array($param)){
      $result['status'] = 'error';
      $result['message'] = 'invalid parameter';
      return $result;
    }
    $param['password'] = password_hash($param['password'], PASSWORD_DEFAULT);
    $param['timestamp'] = time();
    if ($this->authenticationmodel->createUser($param, $group_id)){
      if (empty($group_id)){
        $result['status'] = 'warning';
        $result['message'] = 'changes saved but dont have role';
      } else {
        $result['status'] = 'success';
        $result['message'] = 'changes saved';
      }
    } else {
      $result['status'] = 'error';
      $result['message'] = 'error when executing query';
    }
    return $result;
  }

  public function editUser($user_id, $param){
    unset($param['password']);
    if ($this->authenticationmodel->update('user', array('id' => $user_id), $param)){
      $result['status'] = 'success';
      $result['message'] = 'changes saved';
    } else {
      $result['status'] = 'error';
      $result['message'] = 'error when executing query';
    }
    return $result;
  }

  public function changePassword($user_id, $password_current, $password_new, $password_confirm){
    $result['status'] = 'error';

    $user_data = $this->authenticationmodel->get('user', array('id' => $user_id));

    if ($user_data->num_rows() <= 0){
      $result['message'] = "user not found";
      return $result;
    }

    if ($password_new != $password_confirm){
      $result['message'] = "password confirmation doesn't match";
      return $result;
    }

    if ( ! password_verify($user_data->row('password'), $password_current)){
      $result['message'] = "invalid current password";
      return $result;
    }

    $password = array('password' => password_hash($password_new, PASSWORD_DEFAULT));
    return $this->authenticationmodel->update('user', array('id' => $user_id), $password);
  }

  public function deleteUser($user_id){
    if ($this->authenticationmodel->delete('user', array('id' => $user_id))){
      $result['status'] = 'success';
      $result['message'] = 'changes saved';
    } else {
      $result['status'] = 'error';
      $result['message'] = 'error when executing query';
    }
    return $result;
  }

  public function createGroup($param){
    if (empty($param) || ! is_array($param)){
      $result['status'] = 'error';
      $result['message'] = 'invalid parameter';
      return $result;
    }
    $param['permission'] = serialize($param['permission']);
    if ($this->authenticationmodel->insert('group', $param)){
      $result['status'] = 'success';
      $result['message'] = 'changes saved';
    } else {
      $result['status'] = 'error';
      $result['message'] = 'error when executing query';
    }
    return $result;
  }

  public function editGroup($group_id, $param){
    if (empty($group_id) || empty($param) || ! is_array($param)){
      $result['status'] = 'error';
      $result['message'] = 'invalid parameter';
      return $result;
    }

    if ($this->authenticationmodel->update('group', array('id' => $group_id), $param)){
      $result['status'] = 'success';
      $result['message'] = 'changes saved';
    } else {
      $result['status'] = 'error';
      $result['message'] = 'error when executing query';
    }
    return $result;
  }

  public function deleteGroup($group_id, $force = false){
    $result['status'] = 'error';
    if ($this->authenticationmodel->count('user_group', array('group_id' => $group_id)) > 0 && ! $force){
      $result['status'] = 'warning';
      $result['message'] = 'group in use';
      return $result;
    }

    if ($this->authenticationmodel->delete('group', array('id' => $group_id))){
      $result['status'] = 'success';
      $result['message'] = 'group deleted';
    } else {
      $result['status'] = 'error';
      $result['message'] = 'error when executing query';
    }
    return $result;
  }

  public function hasPermission($permission){
    if ( ! unserialize($this->getUserData()->permission)){
      return false;
    }
    if (in_array($permission, unserialize($this->getUserData()->permission))){
      return true;
    }
    return false;
  }

  public function roleAs($role){
    if ($role == $this->getUserData()->group_name){
      return true;
    }
    return false;
  }
}

?>
