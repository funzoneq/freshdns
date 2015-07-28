<?php

class Services_JSON {
      public function encode($obj) {
      	     return json_encode($obj);
      }
      public function decode($str) {
             return json_decode($str, false);
      }
      public function print_json($obj) {
             header("Content-Type: application/json");
             echo $this->encode($obj);
      }
}