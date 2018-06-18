<?php
/**
 * This file is part of workerman.
*
* Licensed under The MIT License
* For full copyright and license information, please see the MIT-LICENSE.txt
* Redistributions of files must retain the above copyright notice.
*
* @author walkor<walkor@workerman.net>
* @copyright walkor<walkor@workerman.net>
* @link http://www.workerman.net/
* @license http://www.opensource.org/licenses/mit-license.php MIT License
*/

use \Workerman\Worker;
use \Workerman\WebServer;
use \Workerman\Connection\TcpConnection;


// Unix user for command. Recommend nobody www etc. 
$user = `whoami`;
$user = str_replace($user, '\n', '');
define('USER', $user);

require_once __DIR__ . '/vendor/autoload.php';

$worker = new Worker("Websocket://0.0.0.0:7778");
$worker->name = 'phptty';
$worker->user = USER;
function runCmd($cmd, $connection) {
    $cmdRunnin = true;
    $descriptorspec = array(
        0=>array("pipe", "r"),
        1=>array("pipe", "w"),
        2=>array("pipe", "w")
    );
    
    unset($_SERVER['argv']);
    $env = array_merge(
        array('COLUMNS'=>130, 'LINES'=> 50), $_SERVER
    );
    $cmd = 'python cmd.py ' . $cmd;
    $connection->process = proc_open($cmd, $descriptorspec, $pipes, null, $env);
    $connection->pipes = $pipes;
    stream_set_blocking($pipes[0], 0);
    $connection->process_stdout = new TcpConnection($pipes[1]);
    $connection->process_stdout->onMessage = function($process_connection, $data)use($connection)
    {
        $connection->send($data);
    };
    $connection->process_stdout->onClose = function($process_connection)use($connection)
    {
        proc_terminate($connection->process);
        proc_close($connection->process);
        $connection->send('server:complete');
        $cmdRunnin = false;
    };
    $connection->process_stdin = new TcpConnection($pipes[2]);
    $connection->process_stdin->onMessage = function($process_connection, $data)use($connection)
    {
        $connection->send($data);
    };
}
$worker->onConnect = function($connection)
{
    
};

$worker->onMessage = function($connection, $data)
{
    if($cmdRunnin)
        fwrite($connection->pipes[0], $data);
        else
        runCmd($data, $connection);
};

$worker->onClose = function($connection)
{
    $connection->process_stdin->close();
    $connection->process_stdout->close();
    fclose($connection->pipes[0]);
    $connection->pipes = null;
    proc_terminate($connection->process);
    proc_close($connection->process);
    $connection->process = null;
};

$worker->onWorkerStop = function($worker)
{
    foreach($worker->connections as $connection)
    {
        $connection->close();
    }
};
Worker::runAll();