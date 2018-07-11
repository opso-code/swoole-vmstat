<?php

/**
 * 定时执行vmstat，将结果用websocket推送给客户端
 * 进程命名规律：服务名称-master/worker/task-PID-编号
 */

class StatServer
{
    /**
     * @var swoole_websocket_server
     */
    private $server;
    private $onEvents;
    private $server_name;
    /**
     * @var swoole_process
     */
    private $process;

    public function __construct() {
        $this->server_name = 'swoole-vmstat';
        $this->onEvents = array('start', 'workerstart', 'managerstart', 'managerstop','message', 'open', 'close', 'request', 'shutdown', 'task', 'finish');
        $this->init();
    }

    public function init() {
        $this->server = new swoole_websocket_server(WS_HOST, WS_PORT);
        $this->server->set([
            'worker_num' => 1,
            'heartbeat_idle_time' => 30,       // 超过闲置时间关闭
            'heartbeat_check_interval' => 10,   // 每隔多长时间遍历一次客户端fd
            'enable_static_handler' => true,
            'document_root' => PUBLIC_PATH,     // 设置后，所有静态资源都会访问public文件夹
        ]);
        foreach ($this->onEvents as $event) {
            $fun = 'on' . ucfirst(strtolower($event));
            if (method_exists($this, $fun)) {
                $this->server->on($event, array($this, $fun));
            }
        }

        // 由manager管理进程，执行完命令会被再次拉起
        $this->process = new swoole_process(function (swoole_process $child_process) {
            $name = "{$this->server_name} process-{$child_process->pid}";
            cli_set_process_title($name);
            $this->putLog("{$name} start");
            sleep(1); // 保证线程被重新拉起的时候隔一秒
            // vmstat 间隔秒 重复次数
            $child_process->exec('/usr/bin/vmstat', array(1, 3600));
        }, true);

        swoole_process::wait(false);
        // process 加入Master管理
        $this->server->addProcess($this->process);
    }

    /**
     * 启动总进程
     */
    public function run() {
        $this->server->start();
    }

    public function onStart(swoole_websocket_server $serv) {
        $name = "{$this->server_name} master-{$serv->master_pid}";
        cli_set_process_title($name);
        $this->putLog("{$name} start");
    }

    public function onShutdown(swoole_websocket_server $serv) {
        $name = "{$this->server_name} master-{$serv->master_pid}";
        $this->putLog("{$name} shutdown");
    }

    public function onManagerStart(swoole_websocket_server $serv) {
        $name = "{$this->server_name} manager-{$serv->manager_pid}";
        cli_set_process_title($name);
        $this->putLog("{$name} start");
    }

    /**
     * 子进程启动
     * @param swoole_websocket_server $serv
     * @param $worker_id
     */
    public function onWorkerStart(swoole_websocket_server $serv, $worker_id) {
        $type = 'worker';
        if ($serv->taskworker) {
            $type = 'task';
        }
        $name = "{$this->server_name} {$type}-{$serv->worker_pid}-{$worker_id}";
        cli_set_process_title($name);
        $this->putLog("{$name} start");

        if ($worker_id == 0) {
            $process = $this->process;
            // 读数据
            swoole_event_add($process->pipe, function ($pipe) use ($process, $serv) {
                $data = trim($process->read());
                $explodeArr = explode(PHP_EOL, $data);
                $info = preg_split('/ +/', trim(end($explodeArr)));
                if (!is_numeric($info[0])) {
                    return;
                }
                $line = implode(',', $info);
//                $this->putLog($line);
                unset($info, $explodeArr, $data);
                // 维持的连接列表
                $conn_list = $serv->connection_list();
                if (empty($conn_list)) {
                    return;
                }
                foreach ($conn_list as $fd) {
                    $conn_info = $serv->connection_info($fd);
                    // 排除非websocket连接
                    if (isset($conn_info['websocket_status']) && $conn_info['websocket_status'] > 0) {
                        $serv->push($fd, $line);
                    }
                }
            });
        }
    }

    /**
     * 客户端握手后回调
     * @param swoole_websocket_server $serv
     * @param swoole_http_request $request
     */
    public function onOpen(swoole_websocket_server $serv, swoole_http_request $request) {
        $this->putLog("client {$request->fd} handshake success");
    }

    /**
     * 处理接收到的消息
     * @param swoole_websocket_server $serv
     * @param swoole_websocket_frame $frame
     */
    public function onMessage(swoole_websocket_server $serv, swoole_websocket_frame $frame) {
        $this->putLog("client {$frame->fd}:{$frame->data}");
        if (strtolower($frame->data) == 'ping') {
            $serv->push($frame->fd, 'pong');
        }
    }

    /**
     * 连接关闭
     * @param swoole_websocket_server $serv
     * @param $fd
     */
    public function onClose(swoole_websocket_server $serv, $fd) {
        $this->putLog("client {$fd} closed");
    }

    /**
     * 响应http请求
     * @param swoole_http_request $request
     * @param swoole_http_response $response
     */
    public function onRequest(swoole_http_request $request, swoole_http_response $response) {
        // 浏览器每刷新一次 收到两次请求的原因
        $path_info = $request->server['path_info'];
        if ($path_info == '/favicon.ico') {
            $response->status(404);
            $response->end('Page not found');
            return;
        }

        // 所有请求都转向index.html
        $file = PUBLIC_PATH . DS . 'index.html';
        if (is_file($file)) {
            $html = trim(file_get_contents($file));
            $response->end($html);
            return;
        }

        $response->status(404);
        $response->end('Page not found');
        return;
    }

    /**
     * 日志输出
     * @param $msg string|array
     * @param string $file
     * @param int $max_file_size 文件超过大小后清空
     * @param bool $backup
     */
    public function putLog($msg, $file = null, $max_file_size = 1, $backup = false) {
        $file = is_null($file) ? LOG_PATH . '/debug.log' : $file;
        $path = dirname($file);
        if (!is_dir($path)) {
            mkdir($path, 755);
        }
        if (is_file($file) && filesize($file) >= $max_file_size * 1024000) {
            unlink($file);
        }
        if (is_array($msg)) {
            $msg = json_encode($msg);
        }
        $dateStr = "[" . date('Y-m-d H:i:s') . "]\t";
        $line = $dateStr . $msg . PHP_EOL;
        file_put_contents($file, $line, FILE_APPEND);
    }

}