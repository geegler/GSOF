<?php

	namespace Geegler\Min{
        use Geegler\Helpers\SingletonHelper;
        use \ReflectionClass;
        use \InvalidArgumentException;
    Class Geegler{
            /* for the url helper*/
            private $requested_url;private $exts;private $modified_url;public $base_url;public $url;public $full_url; public $app_path;public $request='';
            /* end of url helper */
            /* router */
            protected static $allow_query=true;protected static $routes=array();
            /* end of router */
            /* dispatcher */
             protected $params = array();
             //protected $routes = array();
             

    public function __construct($routes=null){
        /* initialize the uri */
        $this->urlInit();
        //clean the url
        $this->cleanUrl();
        //set the base url
        $this->base_url = self::baseUrl();//$this->baseUrl();
        //get the full url
        $this->full_url = parse_url($this->base_url);
        //get the application path related to the domain name
        $this->app_path=$this->full_url['path'];
        //set the request
        $this->setRequest();
        //break the request into uri segments, will result into controller/method/params
        $this->setUriSegments($this->route($routes,$this->request));
        //serve the request by calling the appropriate controller file, class, method, and params
        $this->serveRequest();

        }

        /*
        * initialize the request
        *
        *
        */
     public function urlInit(){ $this->requested_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; $this->url = $_SERVER['REQUEST_URI']; $this->exts = ['.php','.html','.tpl','.cgi','.html','.inc']; $this->modified_url = ''; $this->base_url = ''; return $this; } /* * * clean the url */ public function cleanUrl(){ if(substr($this->requested_url, -1) !=='/'){ header('location: '. $this->requested_url .'/'); die(); }foreach ($this->exts as $ext) { $ext = trim($ext); if (strpos($this->requested_url, $ext) !== false) { $this->modified_url = trim(str_replace($ext, '', $this->requested_url)); if(substr($this->modified_url,-1) !== '/'){ header('Location: ' . $this->modified_url .'/' ); die(); } else{ header('location: '. $this->modified_url); die(); } } } } /* * base url */ public static function baseUrl(){ if (isset($_SERVER['HTTP_HOST'])) { $baseUrl = (empty($_SERVER['HTTPS']) or strtolower($_SERVER['HTTPS']) === 'off') ? 'http' : 'https'; $baseUrl .= '://' . $_SERVER['HTTP_HOST']; $baseUrl .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']); } else { $baseUrl = 'http://localhost/'; } return ($baseUrl);}   
     /* router methods */

     public function add($src,$dest=null){if(is_array($src)){foreach($src as $key=>$val){static::$routes[$key]=$val;}}elseif($dest){static::$routes[$src]=$dest;}}public function route($src,$uri,$dest=null){$this->add($src);$qs='';if(static::$allow_query&&strpos($uri,'?')!==false){$qs='?'.parse_url($uri,PHP_URL_QUERY);$uri=str_replace($qs,'',$uri);}if(isset(static::$routes[$uri])){return static::$routes[$uri].$qs;}foreach(static::$routes as $key=>$val){$key=str_replace(':any','.+',$key);$key=str_replace(':num','[0-9]+',$key);$key=str_replace(':nonum','[^0-9]+',$key);$key=str_replace(':alpha','[A-Za-z]+',$key);$key=str_replace(':alnum','[A-Za-z0-9]+',$key);$key=str_replace(':hex','[A-Fa-f0-9]+',$key);if(preg_match('#^'.$key.'$#',$uri)){if(strpos($val,'$')!==false&&strpos($key,'(')!==false){$val=preg_replace('#^'.$key.'$#',$val,$uri);}return $val.$qs;}}return $uri.$qs;}public function reverseRoute($controller,$root="/"){$index=array_search($controller,static::$routes);if($index===false)return null;return $root.static::$routes[$index];}public static function testRouter(){echo 'This is an output from a test of :'.__class__.'<br/>';}
     /* end of router methods */
     /*set Request before dispatcher
     * returns request
     */
     public function setRequest(){ if ($this->app_path !== '/') { $this->request = trim(str_replace($this->app_path, '', $this->url)); } else { $this->request = ltrim(trim($url), '/'); } return $this; }
     /*error helper */
     public function getErrorPage(){ 
        
        echo (str_replace('{{ site_url }}', $this->base_url,file_get_contents(ERROR_PAGE))); 
       /*
        $back_url = $this->base_url;
        include_once(ERROR_PAGE);
        */
       }

     /* dispatcher */

    public function setUriSegments($request){@list($controller,$method,$params)=explode('/',$request,3);if(isset($controller)){$this->requestedController($controller);}if(isset($method)){$this->requestedMethod($method);}if(isset($params)){$params=array_filter(explode("/",$params));$this->requestedParams($params);}}public function requestedController($controller){if(file_exists(APPPATH.'controllers/'.strtolower($controller).'.php')){require_once(APPPATH.'controllers/'.strtolower($controller).'.php');if(!class_exists($controller)){$this->getErrorPage();die();}$this->controller=$controller;return $this;}else{$this->getErrorPage();die();}}public function requestedMethod($method){$reflector=new ReflectionClass($this->controller);if(!$reflector->hasMethod($method)){$this->getErrorPage();die();}$this->method=$method;return $this;}public function requestedParams(array $params){$this->params=$params;return $this;}public function serveRequest(){call_user_func_array(array(SingletonHelper::instance($this->controller),$this->method),$this->params);}




        public function test(){
            if(isset($this->url)){
                echo $this->url;
            }
        }
    }

}

namespace Geegler\Helpers{ 

/* singleton helper Class */
final class SingletonHelper{ private static $instances = array(); private function __construct(){} public static function instance($class) { self::create($class); return self::$instances[$class]; } private static function create($class) { if (!array_key_exists($class , self::$instances)) { self::$instances[$class] = new $class; } } }
/* Csrf Helper Class */
class CsrfHelper { public function __construct(){ } public function getTokenId() { if(isset($_SESSION['token_id'])) { return $_SESSION['token_id']; } else { $token_id = $this->random(10); $_SESSION['token_id'] = $token_id; return $token_id; } } public function check_valid($method) { if($method == 'post' || $method == 'get') { $post = $_POST; $get = $_GET; if(isset(${$method}[$this->get_token_id()]) && (${$method}[$this->get_token_id()] == $this->get_token())) { return true; } else { return false; } } else { return false; } } public function form_names($names, $regenerate) { $values = array(); foreach ($names as $n) { if($regenerate == true) { unset($_SESSION[$n]); } $s = isset($_SESSION[$n]) ? $_SESSION[$n] : $this->random(10); $_SESSION[$n] = $s; $values[$n] = $s; } return $values; } public static function randomString() { if (function_exists('openssl_random_pseudo_bytes')) { $string = base64_encode(openssl_random_pseudo_bytes(30)); } else{ $string = random_bytes(30); } $find = array('+','/'); $rep = array('P','S'); return(str_replace($find,$rep,$string)); } }
use \cURL;class CurlHelper { public function __construct() { } public static function getRemoteFile($url) { $curl = curl_init(); $userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)'; curl_setopt($curl, CURLOPT_URL, $url); curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5); curl_setopt($curl, CURLOPT_USERAGENT, $userAgent); curl_setopt($curl, CURLOPT_FAILONERROR, true); curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); curl_setopt($curl, CURLOPT_AUTOREFERER, true); curl_setopt($curl, CURLOPT_TIMEOUT, 10); $contents = curl_exec($curl); curl_close($curl); return $contents; } public static function testCurlHelper(){ echo 'This is from '. __CLASS__ .'<br>'; } }

class ImageHelper{public static function reduceImage($image,$final_width,$final_height,$quality,$save_image_as){$img_dim=getimagesize($image);$ext=image_type_to_extension($img_dim[2]);list($x,$y)=$img_dim;$f_size=self::resizeImage($final_width,$final_height,$image);$canvas=imagecreatetruecolor($f_size['width'],$f_size['height']);switch($ext){case '.jpeg':$preparedImage=imagecreatefromjpeg($image);break;case '.jpg':$preparedImage=imagecreatefromjpeg($image);break;case '.png':$preparedImage=imagecreatefrompng($image);break;case '.bmp':$preparedImage=imagecreatefromwbmp($image);break;}imagecopyresampled($canvas,$preparedImage,0,0,0,0,$f_size['width'],$f_size['height'],$x,$y);imagejpeg($canvas,$save_image_as,$quality);imagedestroy($canvas);imagedestroy($preparedImage);return true;}public static function ForceResizeImage($f_w,$f_h,$force_width=null,$force_height=null,$img_location){$image=getimagesize($img_location);$ext=image_type_to_extension($image[2]);list($x,$y)=$image;if($force_width){$y_ratio=($f_w/$y);$height=round($y*$y_ratio);$width=$f_w;}elseif($force_height){$x_ratio=($f_h/$x);$f_width=round($x*$x_ratio);$width=($f_width>$f_w?$f_w:$f_width);$height=$f_h;}else{$height=$f_h;$width=$f_w;}return(array('width'=>$width,'height'=>$height));}public static function resizeImage($f_x,$f_y,$image_loc){$image=(getimagesize($image_loc));$ext=image_type_to_extension($image[2]);list($x,$y)=$image;if($x>$y){$x_ratio=($f_x/$x);$width=round($x*$x_ratio);$height=round((round($y*$x_ratio)>$f_y)?$f_y:$y*$x_ratio);}elseif($x<$y){$y_ratio=($f_y/$y);$width=round(($x*$y_ratio>$f_x)?$f_x:$x*$y_ratio);$height=round($y*$y_ratio);}else{$x_ratio=$f_x/$x;$y_ratio=$f_y/$y;$width=round($x*$x_ratio>$f_x?$f_x:$x*$x_ratio);$height=round($y*$y_ratio>$f_y?$f_y:$y*$y_ratio);}return array('width'=>$width,'height'=>$height,'ext'=>$ext);}}
}

namespace Geegler\Library{
    require_once('tbs_class.php');
use \PDO;class PdoClass{private $db;public function __construct(array $dbcredits){if(is_array($dbcredits)){$host=$dbcredits['host'];$db_name=$dbcredits['dbname'];$db_user=$dbcredits['dbuser'];$db_pass=$dbcredits['dbpass'];}try{$this->db=new PDO('mysql:host='.$host.';dbname='.$db_name.';charset=utf8',$db_user,$db_pass,array(PDO::ATTR_PERSISTENT=>true));$this->db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_SILENT);return true;}catch(PDOException $e){return false;die('There is a connection error: '.$e->getMessage());}}public function dbCon(){return $this->db;}public function close(){if(self::dbCon()){$this->db=null;}}public function query($query,$type){if(self::dbCon()){try{$this->stmt=$this->db->prepare($query);$this->stmt->execute();if($type='select'){return $this->stmt->fetch(PDO::FETCH_ASSOC);}}catch(PDOException $e){return false;}}else{return false;}}public function getItem($query){if(self::dbCon()){try{$this->stmt=$this->db->prepare($query);$this->stmt->execute();$row_count=$this->stmt->rowCount();$result=$this->stmt->fetchAll();return array($result,$row_count);}catch(PDOException $e){return false;}}else{return false;}}public function dbSelect($table,$fieldname=null,$id=null){self::dbCon();$sql="SELECT * FROM `$table` WHERE `$fieldname`=:id";$stmt=$this->db->prepare($sql);$stmt->bindParam(':id',$id);$stmt->execute();$row_count=$stmt->rowCount();$result=$stmt->fetch();return array($result,$row_count);}public function get_items($query){if(self::dbCon()){try{$this->stmt=$this->db->prepare($query);$this->stmt->execute();$row_count=$this->stmt->rowCount();$result=$this->stmt->fetchAll();return array($result,$row_count);}catch(PDOException $e){return false;}}else{return false;}}public function get_single($query){if(self::dbCon()){try{$this->stmt=$this->db->prepare($query);$this->stmt->execute();return $this->stmt->fetch();}catch(PDOException $e){return false;}}else{return false;}}public function insert_items($table,$values=array()){if(self::dbCon()){try{$fieldnames=array_keys($values);$size=sizeof($fieldnames);$sql="INSERT INTO $table";$fields='( '.implode(' ,',$fieldnames).' )';$bound='(:'.implode(', :',$fieldnames).' )';$sql.=$fields.' VALUES '.$bound;$stmt=$this->db->prepare($sql);$stmt->execute(($values));}catch(PDOException $e){return false;die('There is a connection error: '.$e->getMessage());}}}public function dbUpdate($table,$fieldname,$value,$pk,$id){self::dbCon();$sql="UPDATE `$table` SET `$fieldname`='{$value}' WHERE `$pk` = :id";$stmt=$this->db->prepare($sql);$stmt->bindParam(':id',$id,PDO::PARAM_STR);$stmt->execute();}public function dbUpdateArray($table,$fieldnames=array(),$sort_fieldname){if(is_array($fieldnames)){$q_a='UPDATE '.$table.' SET ';$q_b='';foreach($fieldnames as $field=>$value){if($field==$sort_fieldname){}else{$q_b.=$field.'=:'.$field.', ';}}$q_b.=substr(trim($q_b),0,-1);$q_c=' WHERE '.$sort_fieldname.'=:'.$sort_fieldname;$query=$q_a.$q_b.$q_c;$stmt=$this->db->prepare($query);$stmt->execute($fieldnames);}}public function dbDelete($table,$fieldname,$id){if(self::dbCon()){$sql="DELETE FROM `$table` WHERE `$fieldname` = :id";$stmt=$this->db->prepare($sql);$stmt->bindParam(':id',$id,PDO::PARAM_STR);$stmt->execute();return true;}else{return false;}}public function insert($table,$data){if(self::dbCon()){try{$q="INSERT INTO `$table` ";$v='';$n='';foreach($data as $key=>$val){$n.="`$key`, ";if(strtolower($val)=='null')$v.="NULL, ";elseif(strtolower($val)=='now()')$v.="NOW(), ";else $v.="'".$this->clean($val)."', ";}$q.="(".rtrim($n,', ').") VALUES (".rtrim($v,', ').");";$this->stmt=$this->db->prepare($q);$this->stmt->execute();$id=$this->db->lastInsertId();return array($this->stmt->rowCount(),$id);}catch(PDOException $e){return false;}}}public function joinQuery($query=array(),$table_a,$table_b,$bind_value){if(self::dbCon()){try{if(count($query)==6){$keys=array_keys($query);$table_a=((isset($keys[0])&&$keys[0]==$table_a)?$keys[0]:'');$table_b=$keys[1];$on=((isset($keys[2])&&$keys[2]=='ON')?$keys[2]:'');$where=((isset($keys[3])&&$keys[3]=='WHERE')?$keys[3]:'');$and=((isset($keys[4])&&$keys[4]=='AND')?$keys[4]:'');$bind=((isset($keys[5])&&$keys[5]=='BIND'&&(isset($bind_value)))?$keys[5]:'');$aa=$table_a[0];$ab=$table_b[0];$a="SELECT ";$x=0;if($query[$table_a]&&is_array($query[$table_a])){foreach($query[$table_a]as $first){$a.=' '.$aa.'.'.$first.',';}}if($query[$table_b]&&is_array($query[$table_b])){foreach($query[$table_b]as $sec){$a.=' '.$ab.'.'.$sec.',';}}$af=substr(trim($a),0,-1);$af.=' FROM '.$table_a.' '.$aa.' JOIN '.$table_b.' '.$ab.' '.$on.' ';if($on=='ON'&&(is_array($query[$on]))){foreach($query[$on]as $k=>$v){$af.=$aa.'.'.$k.' = '.$ab.'.'.$v;}}if($where=='WHERE'&&is_array($query[$where])){$af.=' WHERE ';foreach($query[$where]as $kk=>$vv){$af.=$ab.'.'.$kk.' = '.$vv.' ';}}if($and=='AND'&&(isset($query[$and]))){$af.='AND';if($bind=='BIND'&&($query[$and]==$query[$bind])){$af.=' '.$aa.'.'.$query[$bind].' =:'.$query[$bind];}}$stmt=$this->db->prepare($af);$stmt->bindParam(':'.$query[$bind],$bind_value);$stmt->execute();$res=$stmt->fetch(PDO::FETCH_ASSOC);return $res;}}catch(PDOException $e){return false;}}}private function clean($string){return filter_var($string,FILTER_SANITIZE_STRING);}}
class LibTest{ public static function test(){echo 'test Lib<br/>';} }
class TemplateClass{public function __construct(){}public static function render($data,$themefile,$cache=null,$cache_time=null){$cachefile_name=md5($_SERVER['REQUEST_URI']).'__'.$themefile;$deftheme=TPL_PHP.$themefile.'.php';if($cache&&$cache_time){$cacheduration=$cache_time;$cachefile=TPL_PHP_CACHE.$cachefile_name.'.php';if(file_exists($cachefile)&&(time()-$cacheduration<filemtime($cachefile))){readfile($cachefile);exit();}else{ob_start();header('Content-Type: text/html; charset: UTF-8');$tpl_data=$data;include $deftheme;$fp=fopen($cachefile,'w');fwrite($fp,ob_get_contents());fclose($fp);}ob_end_flush();}else{ob_start();header('Content-Type: text/html; charset: UTF-8');$tpl_data=$data;include $deftheme;ob_end_flush();}}public static function testTemplateClass(){echo 'test result from '.__CLASS__.'<br/>';}}

class GeeglerTbs extends \clsTinyButStrong { private $tbs; private $extension; private $chr_open; private $chr_close; public function __construct() { parent::__construct(); $config = array('ldel' => '{%|', 'rdel' => '|%}', 'tpl_extension' => '.html'); $this->chr_open = str_replace('|',' ',$config['ldel']); $this->chr_close = str_replace('|',' ',$config['rdel']); $this->SetOption(array('chr_open' => $this->chr_open, 'chr_close' => $this->chr_close )); $this->extension = '.tpl'; } public function view( $data = array(),$template, $data2 = null, $data3 = null) { parent::LoadTemplate(TPL_DEFAULT . $template . $this->extension); parent::MergeBlock('content', $data); if($data2 && is_array($data2)){ parent::MergeBlock('content2',$data2); } if($data3 && is_array($data3)){ parent::MergeBlock('content3',$data3); } parent::Show(); return true; } }
}