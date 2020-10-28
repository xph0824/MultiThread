## usage:

### 开启namespace下使用.

### 1.实现run方法

    use multiThread/MultiThread;

    class Eg extend MultiThread
    {

        // todo 实现父类run方法
        public function run()
        {
            // todo 调用具体业务处理 或 复杂业务抽象出来
            $this->doing($args)
            // do something ...
        }
   
        private function doing(...$args)
        {
            foreach((array)$args as $arg) {
                // TODO
            }
        }
    }

### 2.在符合场景的业务中调用刚刚实现的多进程类, 灵活配置符合业务的设置项, 以下仅是示例代码
    
    use .../Eg
    
    $instance = Eg::instance();
    $instance->setThreadNum(3);
    $instance->setChildOverNewCreate(true);
    $instance->setRunDurationExit(1);
    $instance->start();