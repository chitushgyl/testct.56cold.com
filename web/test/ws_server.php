<?php 

//创建websocket服务器对象，监听0.0.0.0:9502端口
$ws = new Swoole\WebSocket\Server("0.0.0.0", 9502);

//监听WebSocket连接打开事件
$ws->on('open', function ($ws, $request) {
    var_dump($request->fd);
    var_dump($request->get);
    var_dump($request->server);
    $data = $request->get;
    $id = $data['id'];
    echo 'id:'.$id;
    $ws->push($request->fd, $id.":hello2, welcome\n");
    // $ws->push($request->fd, "hello, welcome\n");
});

//监听WebSocket消息事件
$ws->on('message', function ($ws, $frame) {
	// $message = json_decode($frame->data);
 //      switch ($message['type']) {
 //        case 'setSocketId':
 //          $ws->socketId = $message['data'];
 //          break;
 //      }
    echo "Message: {$frame->data}\n";
    echo "Message: {$frame->fd}\n";
    // $ws->push($message->data, "server: {$frame->data}");
    $ws->push($frame->fd, "server2: {$frame->data}");
});

//监听WebSocket连接关闭事件
$ws->on('close', function ($ws, $fd) {
    echo "client-{$fd} is closed\n";
});

$ws->start();