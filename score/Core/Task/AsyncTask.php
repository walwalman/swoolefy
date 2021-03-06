<?php
namespace Swoolefy\Core\Task;

use Swoolefy\Core\Swfy;
use Swoolefy\Core\Application;
use Swoolefy\Core\Task\AsyncTaskInterface;

class AsyncTask implements AsyncTaskInterface {

    /**
     * registerTask 注册实例任务并调用异步任务，创建一个访问实例，用于处理复杂业务
     * @param   string  $route
     * @param   array   $data
     * @return    int|boolean
     */
    public static function registerTask($callable, $data = []) {
        if(is_string($callable)) {
            throw new \Exception("$callable must be array", 1);
        }
        $callable[0] = str_replace('/', '\\', trim($callable[0],'/'));
        $fd = Application::$app->fd;
        // 只有在worker进程中可以调用异步任务进程，异步任务进程中不能调用异步进程
        if(self::isWorkerProcess()) {
            $task_id = Swfy::$server->task(swoole_pack([$callable, $data, $fd]));
            unset($callable, $data, $fd);
            return $task_id;
        }
        return false;
    }

    /**
     * finish 异步任务完成并退出到worker进程
     * @param   mixed  $data
     * @return    void
     */
    public static function registerTaskfinish($callable, $data) {
        return Swfy::$server->finish([$callable, $data]);
    }

    /**
     * getCurrentWorkerId 获取当前执行进程的id
     * @return int
     */
    public static function getCurrentWorkerId() {
        return Swfy::$server->worker_id;
    }

    /**
     * isWorkerProcess 判断当前进程是否是worker进程
     * @return boolean
     */
    public static function isWorkerProcess() {
        $worker_id = self::getCurrentWorkerId();
        $max_worker_id = (Swfy::$config['setting']['worker_num']) - 1;
        return ($worker_id <= $max_worker_id) ? true : false;
    }

    /**
     * isTaskProcess 判断当前进程是否是异步task进程
     * @return boolean
     */
    public static function isTaskProcess() {
        return (self::isWorkerProcess()) ? false : true;
    }
}