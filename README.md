# swoole-vmstat
基于swoole_websocket_server实现的实时vmstat数据展示

- 基于 `swoole_websocket_server` 实现websocket实时推送给客户端
- 同时具有`http`服务器功能，浏览器直接访问 `http://192.168.33.10:9100` 将展示`public/index.html`

## 运行

默认设定 `websocket` 端口为 `ws://192.168.33.10:9100`

```bash
$ /usr/local/php/bin/php run.php
```

## systemd守护进程

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
           ├─11002 /usr/local/php/bin/php /data/wwwroot/swoole/vmstat/run.php
           ├─11009 swoole-vmstat master-11002                                
           ├─11012 swoole-vmstat worker-11012-0                              
           └─11013 /usr/bin/vmstat 1 3600

```