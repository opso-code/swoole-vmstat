# swoole-vmstat

基于Swoole的swoole_websocket_server实现的实时vmstat数据展示服务

- 基于 `swoole_websocket_server` 实现websocket实时推送给客户端
- 使用`process`的`exec`方法，运行`vmstat`命令，再将结果广播到`websocket`
- 同时具有`http`服务器功能，浏览器直接访问 `http://192.168.33.10:9100` 将展示`public/index.html`
- 进程命名格式  swoole-vmstat  master/manager/worker/task-PID-[编号]

## 运行

默认设定 `websocket` 端口为 `ws://192.168.33.10:9100`，可按需求修改`run.php`和`public/stats.js`中的ip和端口。

**注意**： 日志文件目录为`logs`，需要开启写权限

### 手动命令运行

```bash
$ /usr/local/php/bin/php run.php
```

### systemctl运行

准备了一个创建`systemd`守护的脚本，可以按照实际项目路径修改

```bash
$ sudo cp swoole-vmstat.service /etc/systemd/system/
$ cd /etc/systemd/system/
$ sudo systemctl enable swoole-vmstat.service
$ sudo systemctl start swoole-vmstat.service
$ sudo systemctl status swoole-vmstat.service
● swoole-vmstat.service - Vmsat Http Server By Swoole
   Loaded: loaded (/etc/systemd/system/swoole-vmstat.service; enabled; vendor preset: enabled)
   Active: active (running) since Wed 2018-07-11 12:14:29 CST; 17min ago
 Main PID: 11002 (php)
    Tasks: 6
   Memory: 33.1M
      CPU: 337ms
   CGroup: /system.slice/swoole-vmstat.service
           ├─5135 swoole-vmstat master-5135                               
           ├─5143 swoole-vmstat manager-5143                             
           ├─5146 swoole-vmstat worker-5146-0                             
           └─5147 /usr/bin/vmstat 1 3600
```

## 展示

浏览器访问：http://192.168.33.10:9100


## 参考

部分代码来自 https://github.com/toxmc/swoole-vmstat