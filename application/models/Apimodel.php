<?php

class Apimodel extends MY_Model {

  function __construct(){
    parent::__construct();
  }

  public function getBalance(){
    $income = $this->db->select('sum(amount) as amount')->get('payment')->row('amount');
    $expense = $this->db->select('sum(amount) as amount')->get('expense')->row('amount');
    return array('income' => $income, 'expense' => $expense);
  }

  public function fetchStudent($params){
    $this->db->select('SQL_CALC_FOUND_ROWS *', false);
    $this->db->from('student');
    if ( ! empty($params['filter_search_query'])){
      $this->db->like('name', $params['filter_search_query']);
      $this->db->or_like('student_id', $params['filter_search_query']);
      $this->db->or_like('phone', $params['filter_search_query']);
      $this->db->or_like('address', $params['filter_search_query']);
    }
    $this->db->order_by('id', 'asc');
    $this->db->limit($params['length'], $params['start']);
    $result['filtered_rows']  = $this->db->get();
    $result['total_rows']     = $this->db->query('select found_rows() count;')->row()->count;
    return $result;
  }

  public function fetchBill($params){
    $this->db->select('SQL_CALC_FOUND_ROWS *', false);
    $this->db->from('student');
    if ( ! empty($params['filter_search_query'])){
      $this->db->like('name', $params['filter_search_query']);
      $this->db->or_like('student_id', $params['filter_search_query']);
      $this->db->or_like('phone', $params['filter_search_query']);
      $this->db->or_like('address', $params['filter_search_query']);
    }
    $this->db->order_by('id', 'asc');
    $this->db->limit($params['length'], $params['start']);
    $result['filtered_rows']  = $this->db->get();
    $result['total_rows']     = $this->db->query('select found_rows() count;')->row()->count;
    $result['result_rows']    = $result['filtered_rows']->result();

    $products = $this->db->get('product');

    foreach ($result['result_rows'] as $key => $value) {
      $value->total_bill = 0;
      $value->bills = array();
      // $paid_products = $this->db->select('payment.timestamp as payment_timestamp, payment_item.amount as paid_amount, product.id as product_id, product.name as product_name, product.period, product.amount as product_amount')
      // ->from('product')
      // ->join('student_product', 'product.id = student_product.product_id')
      // ->join('payment', 'student_product.student_id = payment.student_id')
      // ->join('payment_item', 'payment.id = payment_item.payment_id AND student_product.product_id = payment_item.product_id')
      // ->where('student_product.student_id', $value->id)
      // ->get();

      $paid_products = $this->db->query("SELECT `payment`.`timestamp` AS `payment_timestamp`, `payment_item`.`amount` AS `paid_amount`, `product`.`id` AS `product_id`, `product`.`name` AS `product_name`, `product`.`period`, `product`.`amount` AS `product_amount`
      FROM `product`
      JOIN `student_product` ON `product`.`id` = `student_product`.`product_id`
      JOIN `payment` ON `student_product`.`student_id` = `payment`.`student_id`
      JOIN `payment_item` ON `payment`.`id` = `payment_item`.`payment_id` AND `student_product`.`product_id` = `payment_item`.`product_id`
      WHERE `student_product`.`student_id` = $value->id
      UNION
      SELECT NOW() AS `payment_timestamp`, 0 AS `paid_amount`, `id` AS `product_id`, `name` AS `product_name`, `period`, `amount` FROM `product`
      WHERE `id` NOT IN (SELECT `product_id` FROM `payment_item` JOIN `payment` ON `payment_item`.`payment_id` = `payment`.`id` WHERE `payment`.`student_id` = $value->id)");

      if ($paid_products->num_rows() > 0){
        $weekly_payment = array();

        foreach ($paid_products->result() as $k => $v) {
          if ($v->period == 'once'){
            if ($v->paid_amount < $v->product_amount){
              $value->total_bill += $v->product_amount;
              $bill = new stdClass();
              $bill->id = $v->product_id;
              $bill->name = $v->product_name;
              $bill->amount = $v->product_amount;
              $value->bills[] = $bill;
            }
          } else if ($v->period == 'weekly') {
            if ($v->paid_amount >= $v->product_amount){
              if (array_key_exists(date('Y-m-d', strtotime($v->payment_timestamp)), $weekly_payment)){
                $weekly_payment[date('Y-m-d', strtotime($v->payment_timestamp))]->paid_amount += $v->paid_amount;
              } else {
                $weekly_payment[date('Y-m-d', strtotime($v->payment_timestamp))] = $v;
              }
            }
          }
        }

        $date = $this->db->where('code', 202)->get('options')->row('value');
        $regular_payment_id = $this->db->where('code', 201)->get('options')->row('value');
        $regular_payment_data = $this->db->where('id', $regular_payment_id)->get('product')->row();
        $bill_in_week = $regular_payment_data->amount;
        $regular_bill = new stdClass();
        $regular_bill->id = $regular_payment_data->id;
        $regular_bill->name = $regular_payment_data->name;
        $regular_bill->amount = 0;
        do {
          if (array_key_exists($date, $weekly_payment)){
            $value->total_bill -= $weekly_payment[$date]->paid_amount;
            $regular_bill->amount -= $weekly_payment[$date]->paid_amount;
          }
          if (date('w', strtotime($date)) == 0){
            $bill_in_week = $regular_payment_data->amount;
            $value->total_bill += $bill_in_week;
            $regular_bill->amount += $bill_in_week;
          }
          $date = date('Y-m-d', strtotime($date . "+1 day"));
        } while ($date <= date('Y-m-d'));
        $value->bills[] = $regular_bill;
      }
    }

    return $result;
  }

  public function fetchPayment($params){
    $this->db->select('SQL_CALC_FOUND_ROWS payment.id as payment_id, payment.payment_no, payment.student_id, payment.amount, payment.timestamp, student.name', false);
    $this->db->from('payment');
    $this->db->join('student', 'payment.student_id = student.id');
    if ( ! empty($params['filter_student_id'])){
      $this->db->where('student_id', $params['filter_student_id']);
    }
    $this->db->order_by('timestamp', 'desc');
    $this->db->limit($params['length'], $params['start']);
    $result['filtered_rows']  = $this->db->get();
    $result['total_rows']     = $this->db->query('select found_rows() count;')->row()->count;
    return $result;
  }

  public function getPaymentItem($payment_id){
    $this->db->select('payment_item.product_id as id, product.name, product.amount, payment_item.qty');
    $this->db->from('payment_item');
    $this->db->join('product', 'payment_item.product_id = product.id');
    $this->db->where('payment_id', $payment_id);
    return $this->db->get();
  }

  public function fetchProduct($params){
    $regular_payment_id = $this->db->where('code', 201)->get('options')->row('value');
    $this->db->select('SQL_CALC_FOUND_ROWS *', false);
    $this->db->from('product');
    if ( ! empty($params['filter_search_query'])){
      $this->db->like('name', $params['filter_search_query']);
    }

    if ($params['filter_removable'] == 1){
      $this->db->where('id <>', $regular_payment_id);
    }
    $this->db->order_by('id', 'asc');
    $this->db->limit($params['length'], $params['start']);
    $result['filtered_rows']  = $this->db->get();
    $result['total_rows']     = $this->db->query('select found_rows() count;')->row()->count;
    return $result;
  }

  public function fetchExpense($params){
    $this->db->select('SQL_CALC_FOUND_ROWS *', false);
    $this->db->from('expense');
    if ( ! empty($params['filter_search_query'])){
      $this->db->like('name', $params['filter_search_query']);
      $this->db->or_like('description', $params['filter_search_query']);
    }
    $this->db->order_by('timestamp', 'desc');
    $this->db->limit($params['length'], $params['start']);
    $result['filtered_rows']  = $this->db->get();
    $result['total_rows']     = $this->db->query('select found_rows() count;')->row()->count;
    return $result;
  }

}


?>
