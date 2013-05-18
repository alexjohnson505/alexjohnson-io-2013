<?php

use phpSweetPDO\SQLHelpers\Basic as Helpers;

Class InvoiceItem extends Model
{
    public $item;
    public $quantity;
    public $rate;
    public $subtotal;
    public $invoice_id;
    public $task;
    public $task_id;

    function validate(){
        $this->validator_tests = array(
            'item' => 'required'
        );

        return parent::validate();
    }

    function get($invoice_id){
        $sql = "SELECT * FROM invoice_items WHERE invoice_id = " . $invoice_id;
        return parent::get($sql);
    }

    function save(){
        $this->import_parameters();

        if(!isset($this->rate))
            $this->set('rate', 0);

        return parent::save();
    }

    function delete(){
        $this->import_parameters();

        //if the id isn't set, then this item hasn't been saved the database yet so there would be nothing to do
        if(isset($this->id)){
            $result = parent::delete();

            //the total will change on this invoice since we're deleting an item
            $invoice = new Invoice($this->invoice_id);
            $invoice->calculate_total(true);

            return $result;
        }
        else return false;
    }

    function set_subtotal(){}
}
 
