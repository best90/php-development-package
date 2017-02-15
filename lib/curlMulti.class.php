<?php
/**
 * Chrome	Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11
 * IE6		Mozilla/5.0 (Windows NT 6.1; rv:9.0.1) Gecko/20100101 Firefox/9.0.1
 * FF		Mozilla/5.0 (Windows NT 6.1; WOW64; rv:24.0) Gecko/20100101 Firefox/24.0
 * 
 * more useragent:http://www.useragentstring.com/
 *
 * @author admin@curlmulti.com
 *        
 */
class CurlMulti {
	// url
	const TASK_ITEM_URL = 0x01;
	// file
	const TASK_ITEM_FILE = 0x02;
	// arguments
	const TASK_ITEM_ARGS = 0x03;
	// operation, task level
	const TASK_ITEM_OPT = 0x04;
	// control options
	const TASK_ITEM_CTL = 0x05;
	// file pointer
	const TASK_FP = 0x06;
	// success callback
	const TASK_PROCESS = 0x07;
	// curl fail callback
	const TASK_FAIL = 0x08;
	// tryed times
	const TASK_TRYED = 0x09;
	// handler
	const TASK_CH = 0x0A;
	
	// global max thread num
	public $maxThread = 10;
	// Max thread by task type.Task type is specified in $item['ctl'] in add().If task has no type,$this->maxThreadNoType is maxThread-sum(maxThreadType).If less than 0 $this->maxThreadNoType is set to 0.
	public $maxThreadType = array ();
	// retry time(s) when task failed
	public $maxTry = 3;
	// operation, class level curl opt
	public $opt = array ();
	// cache options,dirLevel values is less than 3
	public $cache = array (
			'on' => false,
			'dir' => null,
			'expire' => 86400,
			'dirLevel' => 1 
	);
	// task callback,add() should be called in callback
	public $cbTask = null;
	// status callback
	public $cbInfo = null;
	// user callback
	public $cbUser = null;
	// common fail callback, called if no one specified
	public $cbFail = null;
	
	// max thread num no type
	private $maxThreadNoType = null;
	// all added task was saved here first
	private $taskPool = array ();
	// running task(s)
	private $taskRunning = array ();
	// failed task need to retry
	private $taskFail = array ();
	// handle of multi-thread curl
	private $mh = null;
	// is process running
	private $isRunning = false;
	// user error
	private $userError = null;
	// if __construct called
	private $isConstructCalled = false;
	// running info
	private $info = array (
			'all' => array (
					// process start time
					'startTime' => null,
					// download start time
					'startTimeDownload' => null,
					// the real multi-thread num
					'activeNum' => null,
					// finished task in the queue
					'queueNum' => null,
					// byte
					'downloadSize' => 0,
					// finished task number,include failed task and cache
					'finishNum' => 0,
					// The number of cache used
					'cacheNum' => 0,
					// completely failed task number
					'failNum' => 0,
					// task num has added
					'taskNum' => 0,
					// task running num by type,
					'taskRunningNumType' => array (),
					// task ruuning num no type
					'taskRunningNumNoType' => 0,
					// $this->taskPool size
					'taskPoolNum' => 0,
					// $this->taskRunning size
					'taskRunningNum' => 0,
					// $this->taskFail size
					'taskFailNum' => 0,
					// finish percent
					'finishPercent' => 0,
					// time cost
					'timeSpent' => 0,
					// download time cost
					'timeSpentDownload' => 0,
					// curl task speed
					'taskSpeedNoCache' => 0,
					// network speed, bytes
					'downloadSpeed' => 0 
			),
			'running' => array () 
	);
	public function __construct() {
		$this->isConstructCalled = true;
		if (version_compare ( PHP_VERSION, '5.1.0' ) < 0) {
			throw new ErrorException ( 'PHP 5.1.0+ is needed' );
		}
	}
	
	/**
	 * add a task to taskPool
	 *
	 * @param array $item
	 *        	array('url'=>'',['file'=>'',['opt'=>array(),['args'=>array(),['ctl'=>array('type'=>'','useCache'=>true))]]]])
	 * @param mixed $process
	 *        	success callback,for callback first param array('info'=>,'content'=>), second param $item[args]
	 * @param mixed $fail
	 *        	curl fail callback,for callback first param array('error'=>array(0=>code,1=>msg),'info'=>array),second param $item[args];
	 * @throws ErrorException
	 * @return \frame\lib\CurlMulti
	 */
	public function add(array $item, $process = null, $fail = null) {
		// check
		if (! is_array ( $item )) {
			throw new ErrorException ( 'item must be array, item is ' . gettype ( $item ), 0, E_USER_WARNING );
		}
		$item ['url'] = trim ( $item ['url'] );
		if (empty ( $item ['url'] )) {
			throw new ErrorException ( "url can't be empty, url=$item[url]", 0, E_USER_WARNING );
		} else {
			// replace space with + to avoid some curl problems
			$item ['url'] = str_replace ( ' ', '+', $item ['url'] );
			// fix
			if (empty ( $item ['file'] ))
				$item ['file'] = null;
			if (empty ( $item ['opt'] ))
				$item ['opt'] = array ();
			if (empty ( $item ['args'] ))
				$item ['args'] = array ();
			if (empty ( $item ['ctl'] ))
				$item ['ctl'] = array ();
			if (empty ( $process )) {
				$process = null;
			}
			if (empty ( $fail )) {
				$fail = null;
			}
			$task = array ();
			$task [self::TASK_ITEM_URL] = $item ['url'];
			$task [self::TASK_ITEM_FILE] = $item ['file'];
			$task [self::TASK_ITEM_ARGS] = array (
					$item ['args'] 
			);
			$task [self::TASK_ITEM_OPT] = $item ['opt'];
			$task [self::TASK_ITEM_CTL] = $item ['ctl'];
			$task [self::TASK_PROCESS] = $process;
			$task [self::TASK_FAIL] = $fail;
			$task [self::TASK_TRYED] = 0;
			$task [self::TASK_CH] = null;
			$this->taskPool [] = $task;
			$this->info ['all'] ['taskNum'] ++;
		}
		return $this;
	}
	
	/**
	 * Perform the actual task(s).
	 */
	public function start() {
		if ($this->isRunning) {
			throw new ErrorException ( __CLASS__ . ' is running !', 0, E_USER_ERROR );
		}
		if (false === $this->isConstructCalled) {
			throw new ErrorException ( __CLASS__ . ' __construct is not called', 0, E_USER_ERROR );
		}
		$this->mh = curl_multi_init ();
		$this->info ['all'] ['startTime'] = time ();
		$this->info ['all'] ['timeStartDownload'] = null;
		$this->info ['all'] ['downloadSize'] = 0;
		$this->info ['all'] ['finishNum'] = 0;
		$this->info ['all'] ['cacheNum'] = 0;
		$this->info ['all'] ['failNum'] = 0;
		$this->info ['all'] ['taskNum'] = 0;
		$this->info ['all'] ['taskRunningNumNoType'] = 0;
		$this->setThreadData ();
		$this->isRunning = true;
		$this->addTask ();
		do {
			$this->exec ();
			curl_multi_select ( $this->mh );
			$this->callCbInfo ();
			if (isset ( $this->cbUser )) {
				call_user_func ( $this->cbUser );
			}
			while ( false != ($curlInfo = curl_multi_info_read ( $this->mh, $this->info ['all'] ['queueNum'] )) ) {
				$ch = $curlInfo ['handle'];
				$info = curl_getinfo ( $ch );
				$this->info ['all'] ['downloadSize'] += $info ['size_download'];
				$task = $this->taskRunning [( int ) $ch];
				if (empty ( $task )) {
					throw new ErrorException ( "can't get running task", 0, E_USER_ERROR );
				}
				$callFail = false;
				if ($curlInfo ['result'] == CURLE_OK) {
					$param = array ();
					$param ['info'] = $info;
					if (! isset ( $task [self::TASK_ITEM_FILE] ))
						$param ['content'] = curl_multi_getcontent ( $ch );
					array_unshift ( $task [self::TASK_ITEM_ARGS], $param );
					// write cache
					if ($this->cache ['on'] and ! isset ( $task [self::TASK_ITEM_FILE] )) {
						$this->cache ( $task [self::TASK_ITEM_URL], $param );
					}
				}
				curl_multi_remove_handle ( $this->mh, $ch );
				curl_close ( $ch );
				if (isset ( $task [self::TASK_FP] )) {
					fclose ( $task [self::TASK_FP] );
				}
				// if is a download task, call user function here can ensure file downloaded completely.
				if ($curlInfo ['result'] == CURLE_OK) {
					if (isset ( $task [self::TASK_PROCESS] )) {
						call_user_func_array ( $task [self::TASK_PROCESS], $task [self::TASK_ITEM_ARGS] );
					}
					array_shift ( $task [self::TASK_ITEM_ARGS] );
				}
				// error handle
				if ($curlInfo ['result'] !== CURLE_OK || isset ( $this->userError )) {
					if ($task [self::TASK_TRYED] >= $this->maxTry) {
						// user error
						if (isset ( $this->userError )) {
							$err = array (
									'error' => $this->userError 
							);
						} else {
							$err = array (
									'error' => array (
											$curlInfo ['result'],
											curl_error ( $ch ) 
									) 
							);
						}
						$err ['info'] = $info;
						if (isset ( $task [self::TASK_FAIL] ) || isset ( $this->cbFail )) {
							array_unshift ( $task [self::TASK_ITEM_ARGS], $err );
							$callFail = true;
						} else {
							echo "\nError " . implode ( ', ', $err ['error'] ) . ", url=$info[url]\n";
						}
						$this->info ['all'] ['failNum'] ++;
					} else {
						$task [self::TASK_TRYED] ++;
						$task [self::TASK_ITEM_CTL] ['useCache'] = false;
						$this->taskFail [] = $task;
						$this->info ['all'] ['taskNum'] ++;
					}
					if(isset( $this->userError )){
						unset ( $this->userError );
					}
				}
				if ($callFail) {
					if (isset ( $task [self::TASK_FAIL] )) {
						call_user_func_array ( $task [self::TASK_FAIL], $task [self::TASK_ITEM_ARGS] );
					} elseif (isset ( $this->cbFail )) {
						call_user_func_array ( $this->cbFail, $task [self::TASK_ITEM_ARGS] );
					}
				}
				unset ( $this->taskRunning [( int ) $ch] );
				if (array_key_exists ( 'type', $task [self::TASK_ITEM_CTL] )) {
					$this->info ['all'] ['taskRunningNumType'] [$task [self::TASK_ITEM_CTL] ['type']] --;
				} else {
					$this->info ['all'] ['taskRunningNumNoType'] --;
				}
				$this->info ['all'] ['finishNum'] ++;
				$this->addTask ();
				// if $this->info['all']['queueNum'] grow very fast there will be no efficiency lost,because outer $this->exec() won't be executed.
				$this->exec ();
				$this->callCbInfo ();
				if (isset ( $this->cbUser )) {
					call_user_func ( $this->cbUser );
				}
			}
		} while ( $this->info ['all'] ['activeNum'] || $this->info ['all'] ['queueNum'] || ! empty ( $this->taskFail ) || ! empty ( $this->taskRunning ) || ! empty ( $this->taskPool ) );
		$this->callCbInfo ( true );
		curl_multi_close ( $this->mh );
		unset ( $this->mh );
		$this->isRunning = false;
	}
	
	/**
	 * call $this->cbInfo
	 */
	private function callCbInfo($force = false) {
		static $lastTime;
		if (! isset ( $lastTime )) {
			$lastTime = time ();
		}
		$now = time ();
		if (($force || $now - $lastTime > 0) && isset ( $this->cbInfo )) {
			$lastTime = $now;
			$this->info ['all'] ['taskPoolNum'] = count ( $this->taskPool );
			$this->info ['all'] ['taskRunningNum'] = count ( $this->taskRunning );
			$this->info ['all'] ['taskFailNum'] = count ( $this->taskFail );
			if ($this->info ['all'] ['taskNum'] > 0) {
				$this->info ['all'] ['finishPercent'] = round ( $this->info ['all'] ['finishNum'] / $this->info ['all'] ['taskNum'], 4 );
			}
			$this->info ['all'] ['timeSpent'] = time () - $this->info ['all'] ['startTime'];
			if (isset ( $this->info ['all'] ['timeStartDownload'] )) {
				$this->info ['all'] ['timeSpentDownload'] = time () - $this->info ['all'] ['timeStartDownload'];
			}
			if ($this->info ['all'] ['timeSpentDownload'] > 0) {
				$this->info ['all'] ['taskSpeedNoCache'] = round ( ($this->info ['all'] ['finishNum'] - $this->info ['all'] ['cacheNum']) / $this->info ['all'] ['timeSpentDownload'], 2 );
				$this->info ['all'] ['downloadSpeed'] = round ( $this->info ['all'] ['downloadSize'] / $this->info ['all'] ['timeSpentDownload'], 2 );
			}
			// running
			$this->info ['running'] = array();
			foreach ( $this->taskRunning as $k => $v ) {
				$this->info ['running'] [$k] = curl_getinfo ( $v [self::TASK_CH] );
			}
			call_user_func_array ( $this->cbInfo, array (
					$this->info 
			) );
		}
	}
	
	/**
	 * set $this->maxThreadNoType, $this->info['all']['taskRunningNumType'], $this->info['all']['taskRunningNumNoType'] etc
	 */
	private function setThreadData() {
		$this->maxThreadNoType = $this->maxThread - array_sum ( $this->maxThreadType );
		if ($this->maxThreadNoType < 0) {
			$this->maxThreadNoType = 0;
		}
		// unset none exitst type num
		foreach ( $this->info ['all'] ['taskRunningNumType'] as $k => $v ) {
			if ($v == 0 && ! array_key_exists ( $k, $this->maxThreadType )) {
				unset ( $this->info ['all'] ['taskRunningNumType'] [$k] );
			}
		}
		// init type num
		foreach ( $this->maxThreadType as $k => $v ) {
			if ($v == 0) {
				user_error ( 'maxThreadType[' . $k . '] is 0, task of this type will never be added!', E_USER_WARNING );
			}
			if (! array_key_exists ( $k, $this->info ['all'] ['taskRunningNumType'] )) {
				$this->info ['all'] ['taskRunningNumType'] [$k] = 0;
			}
		}
	}
	
	/**
	 * curl_multi_exec()
	 */
	private function exec() {
		while ( curl_multi_exec ( $this->mh, $this->info ['all'] ['activeNum'] ) === CURLM_CALL_MULTI_PERFORM ) {
		}
	}
	
	/**
	 * add a task to curl, keep $this->maxThread concurrent automatically
	 */
	private function addTask() {
		$c = $this->maxThread - count ( $this->taskRunning );
		while ( $c > 0 ) {
			$task = array ();
			// search failed first
			if (! empty ( $this->taskFail )) {
				$task = array_pop ( $this->taskFail );
			} else {
				if (0 < ( int ) ($this->maxThread - count ( $this->taskPool )) and ! empty ( $this->cbTask )) {
					call_user_func_array ( $this->cbTask [0], array (
							$this->cbTask [1] 
					) );
				}
				if (! empty ( $this->taskPool ))
					$task = array_pop ( $this->taskPool );
			}
			$noAdd = false;
			$cache = null;
			if (! empty ( $task )) {
				if ($this->cache ['on'] == true && ! isset ( $task [self::TASK_ITEM_FILE] ) && (! isset ( $task [self::TASK_ITEM_CTL] ['useCache'] ) || true === $task [self::TASK_ITEM_CTL] ['useCache'])) {
					$cache = $this->cache ( $task [self::TASK_ITEM_URL] );
					if (null !== $cache) {
						array_unshift ( $task [self::TASK_ITEM_ARGS], $cache );
						$this->info ['all'] ['finishNum'] ++;
						$this->info ['all'] ['cacheNum'] ++;
						if (isset ( $task [self::TASK_PROCESS] )) {
							call_user_func_array ( $task [self::TASK_PROCESS], $task [self::TASK_ITEM_ARGS] );
						}
						array_shift ( $task [self::TASK_ITEM_ARGS] );
						$this->callCbInfo ();
					}
				}
				if (! $cache) {
					$this->setThreadData ();
					if (array_key_exists ( 'type', $task [self::TASK_ITEM_CTL] ) && ! array_key_exists ( $task [self::TASK_ITEM_CTL] ['type'], $this->maxThreadType )) {
						user_error ( 'task was set to notype because type was not set in $this->maxThreadType, type=' . $task [self::TASK_ITEM_CTL] ['type'], E_USER_WARNING );
						unset ( $task [self::TASK_ITEM_CTL] ['type'] );
					}
					if (array_key_exists ( 'type', $task [self::TASK_ITEM_CTL] )) {
						$maxThread = $this->maxThreadType [$task [self::TASK_ITEM_CTL] ['type']];
						$isNoType = false;
					} else {
						$maxThread = $this->maxThreadNoType;
						$isNoType = true;
					}
					if ($isNoType && $maxThread == 0) {
						user_error ( 'task was disgarded because maxThreadNoType=0, url=' . $task [self::TASK_ITEM_URL], E_USER_WARNING );
					}
					if (($isNoType && $this->info ['all'] ['taskRunningNumNoType'] < $maxThread) || (! $isNoType && $this->info ['all'] ['taskRunningNumType'] [$task [self::TASK_ITEM_CTL] ['type']] < $maxThread)) {
						$task [self::TASK_CH] = $this->curlInit ( $task [self::TASK_ITEM_URL] );
						if (is_resource ( $task [self::TASK_CH] )) {
							// is a download task?
							if (isset ( $task [self::TASK_ITEM_FILE] )) {
								// curl can create the last level directory
								$dir = dirname ( $task [self::TASK_ITEM_FILE] );
								if (! file_exists ( $dir ))
									mkdir ( $dir, 0777 );
								$task [self::TASK_FP] = fopen ( $task [self::TASK_ITEM_FILE], 'w' );
								curl_setopt ( $task [self::TASK_CH], CURLOPT_FILE, $task [self::TASK_FP] );
							}
							// single task curl option
							if (isset ( $task [self::TASK_ITEM_OPT] )) {
								foreach ( $task [self::TASK_ITEM_OPT] as $k => $v ) {
									curl_setopt ( $task [self::TASK_CH], $k, $v );
								}
							}
							curl_multi_add_handle ( $this->mh, $task [self::TASK_CH] );
							$this->taskRunning [( int ) $task [self::TASK_CH]] = $task;
							if (! isset ( $this->info ['all'] ['timeStartDownload'] )) {
								$this->info ['all'] ['timeStartDownload'] = time ();
							}
							if ($isNoType) {
								$this->info ['all'] ['taskRunningNumNoType'] ++;
							} else {
								$this->info ['all'] ['taskRunningNumType'] [$task [self::TASK_ITEM_CTL] ['type']] ++;
							}
						} else {
							throw new ErrorException ( '$ch is not resource,curl_init failed.', 0, E_USER_WARNING );
						}
					} else {
						// rotate task to pool
						if ($task [self::TASK_TRYED] > 0) {
							array_unshift ( $this->taskFail, $task );
						} else {
							array_unshift ( $this->taskPool, $task );
						}
						$noAdd = true;
					}
				}
			}
			if (! $cache || $noAdd) {
				$c --;
			}
		}
	}
	
	/**
	 * set or get file cache
	 *
	 * @param string $url        	
	 * @param mixed $content
	 *        	null : get cache
	 * @return return read:content or false, write: true or false
	 */
	private function cache($url, $content = null) {
		if (! isset ( $this->cache ['dir'] ))
			throw new ErrorException ( 'Cache dir is not defined', 0, E_USER_ERROR );
		$key = md5 ( $url );
		$dir = $this->cache ['dir'];
		if (isset ( $this->cache ['dirLevel'] ) && $this->cache ['dirLevel'] != 0) {
			if ($this->cache ['dirLevel'] == 1) {
				$dir .= DIRECTORY_SEPARATOR . substr ( $key, 0, 3 );
				$file = $dir . DIRECTORY_SEPARATOR . substr ( $key, 3 );
			} elseif ($this->cache ['dirLevel'] == 2) {
				$dir .= DIRECTORY_SEPARATOR . substr ( $key, 0, 3 ) . DIRECTORY_SEPARATOR . substr ( $key, 3, 3 );
				$file = $dir . DIRECTORY_SEPARATOR . substr ( $key, 6 );
			} else {
				throw new ErrorException ( 'cache dirLevel is invalid, dirLevel=' . $this->cache ['dirLevel'], 0, E_USER_ERROR );
			}
		} else {
			$file = $dir . DIRECTORY_SEPARATOR . $key;
		}
		if (! isset ( $content )) {
			if (file_exists ( $file )) {
				if ((time () - filemtime ( $file )) < $this->cache ['expire']) {
					return unserialize ( file_get_contents ( $file ) );
				} else {
					unlink ( $file );
				}
			}
		} else {
			$r = false;
			// check main cache directory
			if (! is_dir ( $this->cache ['dir'] )) {
				throw new ErrorException ( "Cache dir doesn't exists", 0, E_USER_ERROR );
			} else {
				// level 1 subdir
				if (isset ( $this->cache ['dirLevel'] ) && $this->cache ['dirLevel'] > 1) {
					$dir1 = dirname ( $dir );
					if (! is_dir ( $dir1 ) && ! mkdir ( $dir1 )) {
						throw new ErrorException ( 'Create dir failed, dir=' . $dir1, 0, E_USER_WARNING );
					}
				}
				if (! is_dir ( $dir ) && ! mkdir ( $dir )) {
					throw new ErrorException ( 'Create dir failed, dir=' . $dir, 0, E_USER_WARNING );
				}
				$content = serialize ( $content );
				if (file_put_contents ( $file, $content, LOCK_EX ))
					$r = true;
				else
					throw new ErrorException ( 'Write cache file failed', 0, E_USER_WARNING );
			}
			return $r;
		}
	}
	
	/**
	 * user error for latest callback, not curl error,must be called in process callback
	 *
	 * @param unknown $msg        	
	 */
	public function error($msg) {
		$this->userError = array (
				CURLE_OK,
				$msg 
		);
	}
	
	/**
	 * get curl handle
	 *
	 * @param string $url        	
	 * @return resource
	 */
	private function curlInit($url) {
		$ch = curl_init ();
		$opt = array ();
		$opt [CURLOPT_URL] = $url;
		$opt [CURLOPT_HEADER] = false;
		$opt [CURLOPT_CONNECTTIMEOUT] = 10;
		$opt [CURLOPT_TIMEOUT] = 30;
		$opt [CURLOPT_AUTOREFERER] = true;
		$opt [CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11';
		$opt [CURLOPT_RETURNTRANSFER] = true;
		$opt [CURLOPT_FOLLOWLOCATION] = true;
		$opt [CURLOPT_MAXREDIRS] = 10;
		// user defined opt
		if (! empty ( $this->opt ))
			foreach ( $this->opt as $k => $v )
				$opt [$k] = $v;
		curl_setopt_array ( $ch, $opt );
		return $ch;
	}
}
