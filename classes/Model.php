<?php
use Illuminate\Support;  // https://laravel.com/docs/5.8/collections - provides the collect methods & collections class
use LSS\Array2Xml;
require_once('classes/Exporter.php');

class Model {

    public function __construct($args) {
        $this->args = $args;
    }

  
}