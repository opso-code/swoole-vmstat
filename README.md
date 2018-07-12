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
```

![bash](http://7xocls.com1.z0.glb.clouddn.com/bash.png)

## 展示

浏览器访问：http://192.168.33.10:9100

![展示](http://7xocls.com1.z0.glb.clouddn.com/swoole-vmstat.png)

## 参考

部分代码来自 https://github.com/toxmc/swoole-vmstat