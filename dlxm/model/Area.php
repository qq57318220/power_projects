<?php
namespace app\admin\model;

use think\Model;
use think\Session;

class Area extends Model
{

  public function attachMent(){
        return $this->hasOne('Attachment','id');
   }

  public function getArea(){
  	 return Attachment::where('id=1')->find();
  }
}
