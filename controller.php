<?php

$ins = new install();

class install
{
	public $msg;
	
	public function __construct()
	{
		if($_POST['option'] == "install")
		{
                    if (!is_dir("../app/code/local/Cenpos")) {
                        mkdir("../app/code/local/Cenpos",0777,true);
                    }
			$this->rcopy("files/module/Cenpos/Simplewebpay","../app/code/local/Cenpos/Simplewebpay");
			$this->rcopy("files/admin/simplewebpay","../app/design/adminhtml/default/default/template/simplewebpay");
			$this->rcopy("files/admin/simplewebpay.xml","../app/design/adminhtml/default/default/layout/simplewebpay.xml");
			$this->rcopy("files/front/simplewebpay.xml","../app/design/frontend/base/default/layout/simplewebpay.xml");
			$this->rcopy("files/front/simplewebpay","../app/design/frontend/base/default/template/simplewebpay");
			$this->rcopy("files/front/js/simplewebpay.js","../js/simplewebpay.js");
			$this->rcopy("files/Cenpos_Simplewebpay.xml","../app/etc/modules/Cenpos_Simplewebpay.xml");
                        $this->rcopy("files/simplewebpay","../skin/frontend/base/default/images/simplewebpay");
                        $this->rcopy("files/cenpossimplewebpay.php","../cenpossimplewebpay.php");
			$msg = "installation";
		}elseif ($_POST["option"] == "uninstall")
		{
			$this->rrmdir("../app/code/local/Cenpos/Simplewebpay");
			$this->rrmdir("../app/design/adminhtml/default/default/template/simplewebpay");
			$this->rrmdir("../app/design/adminhtml/default/default/layout/simplewebpay.xml");
			$this->rrmdir("../app/design/frontend/base/default/layout/simplewebpay.xml");
			$this->rrmdir("../js/simplewebpay.js");
			$this->rrmdir("../app/design/frontend/base/default/template/simplewebpay");
			$this->rrmdir("../app/etc/modules/Cenpos_Simplewebpay.xml");
                        $this->rrmdir("../skin/frontend/base/default/images/simplewebpay");
                        $this->rrmdir("../cenpossimplewebpay.php");
			$msg = "removal";
		}
		
		echo $this->msg. "<br/> The $msg was successful";
	}
	
	public function rcopy($src, $dst) 
	{
	  if (file_exists($dst)) $this->rrmdir($dst);
	  if (is_dir($src)) {
	    mkdir($dst,0777,true);
            
	    $this->msg .= "The directory $dst was created <br/> <br/>";
	    $files = scandir($src);
	    foreach ($files as $file)
	    if ($file != "." && $file != "..") $this->rcopy("$src/$file", "$dst/$file"); 
	  }
	  else if ($this->checkfile($src))
	  {
	  	 copy($src, $dst);
	  	 $this->msg .= "The file $dst was created <br/> <br/>";
	  }
	}
	
	public function rrmdir($dir) {
	  if (is_dir($dir)) {
	    $files = scandir($dir);
	    foreach ($files as $file)
	    if ($file != "." && $file != "..") $this->rrmdir("$dir/$file");
	    rmdir($dir);
	    $this->msg .= "The directory $dir was deleted <br/> <br/>";
	  }
	  else if ($this->checkfile($dir))
	  {
	  	 unlink($dir);
	  	 $this->msg .= "The file $dir was deleted <br/> <br/>";
	  }
	} 
	
	public function checkfile($file)
	{
		if (file_exists($file))
		{
			return true;
		}
		else
		{
			die($this->msg ."<br/>The rute ".$file." is not a exists");	
		}
	}
}
		
?>
