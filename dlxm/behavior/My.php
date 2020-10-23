<?php

namespace app\admin\behavior;

class My
{
    public function run(&$params)
    {
       foreach($params as $k=>$v){
			$params[$k] = "$k+$v"; 
	   }
    }
}
