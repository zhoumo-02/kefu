<?php
// 这里填写客服系统域名，前面带http://，用于pusher系统通知客服平台客户或者客服上下线
$domain = 'http://127.0.0.1';

// App_key，客服系统与pusher通讯的key
$app_key = 'fr1jg6zr1lorl6ze';

// App_secret，客服系统与pusher通讯的密钥
$app_secret = 'fnak4wp4zzjw2cwwpz8uwj9bqrw2q1b4';

// App id
$app_id = 232;

// websocket 端口，客服系统网页会连这个端口
$websocket_port = 9090;

// 一般情况下与websocket端口一只，出现反响代理时，调整此端口
$proxy_port = 80;

// Api 端口，用于后端与pusher通讯
$api_port = 2080;

