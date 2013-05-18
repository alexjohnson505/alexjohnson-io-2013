<?php

class Reports extends Model{
    public $payments;
    public $payments_by_month;
    public $payment_totals_by_month;
    public $payments_this_month;
    public $payments_this_month_change_percentage;
    public $outstanding_invoices_total;
    public $payments_by_client;


    function __construct($parameters = null){
        $this->payments = array();
        $this->payments_by_month = array();
        $this->payment_totals_by_month = array();
        $this->payments_this_month = 0;
        $this->payments_this_month_change_percentage = 0;
        $this->payments_last_three_months = 0;
        $this->payments_last_three_months_change_percentage = 0;
        $this->outstanding_invoices_total = 0;

        parent::__construct($parameters);

    }


    function get(){
        $first_day_of_year = strtotime('Jan 1, ' . date('Y'));
        $last_day_of_year = strtotime('Dec 31, ' . date('Y'));

        $sql = "SELECT payments.*, clients.name as client_name, invoices.number as invoice_number FROM payments
                LEFT JOIN clients
                  ON clients.id = payments.client_id
                LEFT JOIN invoices
                  ON invoices.id = payments.invoice_id
                WHERE payment_date >= $first_day_of_year
                AND payment_date <= $last_day_of_year
                ORDER BY payment_date ASC";


        $this->payments_by_month = array();
        $this->payment_totals_by_month  = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);

        $this->payments = $this->select($sql);

        if(is_array($this->payments)){
            foreach($this->payments as $payment){
                $month_number = date('n', $payment['payment_date']);

                if(!isset($this->payments_by_month[$month_number]) || !is_array($this->payments_by_month[$month_number]))
                    $this->payments_by_month[$month_number] = array();

                $this->payments_by_month[$month_number][] = $payment;

                $this->payment_totals_by_month[$month_number - 1] += $payment->amount;
            }
        }

        $this->get_totals();

        $sql = "SELECT SUM(total)
                FROM invoices
                WHERE balance != 0";
        $outstanding_invoices = $this->select($sql);
        $this->outstanding_invoices_total = $outstanding_invoices[0]['SUM(total)'];

        if($this->outstanding_invoices_total == null)
            $this->outstanding_invoices_total = 0;


        $sql = "SELECT payments.client_id, SUM(payments.amount) as total_payments, clients.name
                FROM payments
                LEFT JOIN clients
                  ON clients.id = payments.client_id
                WHERE payments.payment_date >= $first_day_of_year
                AND payments.payment_date <= $last_day_of_year
                GROUP BY payments.client_id";

        $this->payments_by_client = $this->select($sql);
        return $this->to_array();

    }

    function get_totals(){
        //subtract 1 because the months array starts at 0
        $this_month = date('n', time()) - 1;

        $this->payments_this_month = $this->payment_totals_by_month[$this_month];

        $last_month = $this_month > 0 ? $this_month - 1 : false;
        if($last_month){
            $payments_last_month =  $this->payment_totals_by_month[$last_month];

            if($payments_last_month > 0)
                $this->payments_this_month_change_percentage = (($this->payments_this_month/$payments_last_month) - 1) * 100;
            else $this->payments_this_month_change_percentage = 0;
        }
    }
}