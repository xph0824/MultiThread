<?php
/**
 * 多进程基类
 * DateTime: 2020/06/28 10:05 上午
 * Author: Songyl <skill1221@163.com>
 */

namespace multiThread;
abstract class MultiThread
{
    protected $masterPid = 0;
    // 子进程运行多久自动退出。0 不退出。0 用于多进程一次性运算业务。(单位:分钟)
    protected $runDurationExit = 0;
    // 子进程结束之后是否新创建。
    protected $isNewCreate = true;
    // 进程总数。
    protected $threadNum = 10;
    // 当前子进程数量。
    protected $childCount = 0;
    protected static $instance = null;
    // 子进程ID与子进程编号。
    protected static $childProcess = [];

    public function __construct()
    {
    }

    public function __clone()
    {
    }

    /**
     * 返回实例
     * @return static|null
     */
    public static function instance()
    {
        if (self::$instance == null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    final public function start()
    {
        if (function_exists('pcntl_fork')) {
            $this->masterPid = posix_getpid();
            $this->registerSignal();
            while (true) {
                $this->childCount++;
                if (($this->childCount <= $this->threadNum) || $this->isNewCreate) {
                    $pid = pcntl_fork();
                    if ($pid == -1) {
                        exit('could not fork');
                    } else if ($pid > 0) {
                        $this->pushChildProcessId($pid);
                        if ($this->childCount >= $this->threadNum) {
                            pcntl_wait($status);
                            if (!$this->isNewCreate && $this->childCount == $this->threadNum) {
                                exit(0);
                            }
                        }
                    } else {
                        $childProcessNum = $this->childCount % $this->threadNum;
                        $startTimeTsp = time();
                        $this->run($this->threadNum, $childProcessNum, $startTimeTsp);
                        exit(0);
                    }
                }
            }
            exit(0);
        }
    }

    final public function setRunDurationExit(int $runDurationExit)
    {
        $this->runDurationExit = $runDurationExit;
    }

    final public function detectMasterProcessAlive()
    {
        if (posix_kill($this->masterPid, 0)) {
            return true;
        } else {
            return false;
        }
    }

    final protected function isExit($startTimeTsp)
    {
        if ($this->isNewCreate && $this->runDurationExit > 0) {
            $diffTime = time() - $startTimeTsp;
            if ($diffTime >= $this->runDurationExit * 60) {
                exit(0);
            }
        }
        $status = $this->detectMasterProcessAlive();
        if (!$status) {
            exit(0);
        }
    }

    // 存储进程
    final protected function pushChildProcessId($pid)
    {
        $num = $this->detectChildProcessAlive();
        if ($num > 0) {
            self::$childProcess[$num] = $pid;
        } else {
            self::$childProcess[] = $pid;
        }
    }

    // 发现进程
    final protected function detectChildProcessAlive()
    {
        foreach (self::$childProcess as $num => $pid) {
            //posix_kill($pid, 0) 用来检测指定的进程PID是否存在,存在返回true, 反之返回false
            if (!posix_kill($pid, 0)) {
                return $num;
            } else {
                return 0;
            }
        }
    }

    
    final public function setChildOverNewCreate($isNewCreate)
    {
        $this->isNewCreate = $isNewCreate;
    }

     // 安装信号
    final public function registerSignal()
    {
        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGHUP, [$this, 'signalHandler']);
        pcntl_signal(SIGUSR1, [$this, 'signalHandler']);
        pcntl_signal(SIGCHLD, [$this, 'signalHandler']);
    }

    /**
     * 信号处理
     * @param $signo
     */
    final public function signalHandler($signo)
    {
        switch ($signo) {
            case SIGTERM:
            case SIGHUP:
            case SIGUSR1:
            case SIGCHLD:
            default:
                break;
        }
    }

    /**
     * 设置进程数量
     * @param $num
     */
    final public function setThreadNum($num)
    {
        $this->threadNum = $num;
    }

    /**
     * @desc todo 业务实现
     * @param $threadNum
     * @param $num 进程编号
     * @param $startTimeTsp 子进程启动时间戳
     * @return mixed
     */
    abstract public function run($threadNum, $num, $startTimeTsp);
}
