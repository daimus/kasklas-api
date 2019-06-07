<?php

/**
 * Class Name : MY_Model
 * Here i put most used queries
 */
class MY_Model extends CI_Model {

  public function __construct(){
    parent::__construct();
  }

  public function get($table, $where = array()){
    if (!empty($where) && is_array($where)){
      foreach ($where as $column => $value) {
        $this->db->where($column, $value);
      }
    }
    return $this->db->get($table);
  }

  public function insert($table, $data){
    return $this->db->insert($table, $data);
  }

  public function update($table, $where, $data){
    foreach ($where as $column => $value) {
      $this->db->where($column, $value);
    }

    return $this->db->update($table, $data);
  }

  public function delete($table, $where){
    foreach ($where as $column => $value) {
      $this->db->where($column, $value);
    }

    return $this->db->delete($table);
  }

  public function count($table, $where = array()){
    foreach ($where as $column => $value) {
      $this->db->where($column, $value);
    }
    return $this->db->get($table)->num_rows();
  }
}
?>
