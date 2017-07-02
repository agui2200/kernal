<?php
namespace PhalApi;

use PhalApi\Loader;
use PhalApi\Config\FileConfig;
use PhalApi\Logger\ExplorerLogger;
use PhalApi\Filter;
use PhalApi\Exception\BadRequestException;
use PhalApi\Response\JsonResponse;
use PhalApi\Response\JsonpResponse;

/**
 * 接口统一入口
 * @author: dogstar 2014-10-04
 */
 
/** ---------------- 根目录定义，自动加载 ---------------- **/

defined('API_ROOT') || define('API_ROOT', dirname(__FILE__));

require API_ROOT . '/../vendor/autoload.php';

$loader = new Loader(API_ROOT);

date_default_timezone_set('Asia/Shanghai');

SL('zh_cn');

/** ---------------- 注册&初始化服务组件 ---------------- **/

DI()->loader = $loader;

DI()->config = new FileConfig(dirname(__FILE__) . '/Config');

DI()->logger = new ExplorerLogger(
		Logger::LOG_LEVEL_DEBUG | Logger::LOG_LEVEL_INFO | Logger::LOG_LEVEL_ERROR);

//DI()->notorm = function() {
//    $notorm = new PhalApi_DB_NotORM(DI()->config->get('dbs'), true);
//    return $notorm;
//};

DI()->cache = function() {
    //$mc = new PhalApi_Cache_Memcached(DI()->config->get('sys.mc'));
    $mc = new MemcachedMock();
	return $mc;
};

class MemcachedMock {
    public $data = array();

    public function __call($method, $params)
    {
        echo 'Memcached::' . $method . '() with: ', json_encode($params), " ... \n";
    }

    public function get($key)
    {
        echo "Memcached::get($key) ... \n";
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function set($key, $value, $expire)
    {
        echo "Memcached::get($key, ", json_encode($value), ", $expire) ... \n";
        $this->data[$key] = $value;
    }

    public function delete($key)
    {
        unset($this->data[$key]);
    }
}

if (!class_exists('Memcached')) {
    class Memcached extends MemcachedMock {
    }
}

if (!class_exists('Redis')) {

    class Redis {

        public function __call($method, $params) {
            echo 'Redis::' . $method . '() with: ', json_encode($params), " ... \n";
        }

    }
}

//加密，测试情况下为防止本地环境没有mcrypt模块 这里作了替身
DI()->crypt = function() {
	//return new MockCrypt();
	// TODO return new PhalApi_Crypt_MultiMcrypt(DI()->config->get('sys.crypt.mcrypt_iv'));
};

class MockCrypt implements Crypt
{
	public function encrypt($data, $key)
	{
		echo "Crypt_Mock::encrypt($data, $key) ... \n";
		return $data;
	}
	
	public function decrypt($data, $key)
		{
		echo "Crypt_Mock::decrypt($data, $key) ... \n";
		return $data;
	}
}

/** ---------------- 公共的测试替身或桩 ---------------- **/

class JsonResponseMock extends JsonResponse {

    protected function handleHeaders($headers) {
    }
}

class JsonpResponseMock extends JsonpResponse {

    protected function handleHeaders($headers) {
    }
}

class ApiImpl extends Api {

    public function getRules() {
        return array(
            '*' => array( 
                'version' => array('name' => 'version'),
            ),
            'add' => array(
                'left' => array('name' => 'left', 'type' => 'int'),
                'right' => array('name' => 'right', 'type' => 'int'),
            ),
        );
    }

    public function add()
    {
        return $this->left + $this->right;
    }

    public function getTime()
    {
        return time();
    }
}

class ImplFilter implements Filter {

    public function check() {

    }
}

class ImplExceptionFilter implements Filter {

    public function check() {
        throw new BadRequestException('just for test');
    }
}

if (!class_exists('Yaconf', false)) {
    class Yaconf {
        public static function __callStatic($method, $params) {
            echo "Yaconf::$method()...\n";
        }
    }
}
