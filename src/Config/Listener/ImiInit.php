<?php

namespace Imi\Config\Listener;

use Imi\Bean\Annotation\Listener;
use Imi\Config;
use Imi\Event\EventParam;
use Imi\Event\IEventListener;

/**
 * @Listener(eventName="IMI.INITED",priority=Imi\Util\ImiPriority::IMI_MAX)
 * @Listener(eventName="IMI.INIT.WORKER.BEFORE",priority=Imi\Util\ImiPriority::IMI_MAX)
 */
class ImiInit implements IEventListener
{
    /**
     * 事件处理方法.
     *
     * @param EventParam $e
     *
     * @return void
     */
    public function handle(EventParam $e)
    {
        // 加载 .env 配置
        foreach ($_ENV as $name => $value)
        {
            Config::set($name, $value);
        }
    }
}
