<?php
namespace Imi\Cache\Aop;

use Imi\Config;
use Imi\Aop\JoinPoint;
use Imi\Aop\PointCutType;
use Imi\Cache\CacheManager;
use Imi\Aop\AroundJoinPoint;
use Imi\Aop\Annotation\Around;
use Imi\Aop\Annotation\Aspect;
use Imi\Aop\Annotation\PointCut;
use Imi\Cache\Annotation\CacheEvict;
use Imi\Bean\Annotation\AnnotationManager;
use Imi\Util\ClassObject;

/**
 * @Aspect(priority=1023)
 */
class CacheEvictAop
{
    use TCacheAopHelper;

    /**
     * 处理 CacheEvict 注解
     * @PointCut(
     *         type=PointCutType::ANNOTATION,
     *         allow={
     *             \Imi\Cache\Annotation\CacheEvict::class,
     *         }
     * )
     * @Around
     * 
     * @param AroundJoinPoint $joinPoint
     * @return void
     */
    public function parseCacheEvict(AroundJoinPoint $joinPoint)
    {
        $class = get_parent_class($joinPoint->getTarget());

        // CacheEvict 注解列表
        $cacheEvicts = AnnotationManager::getMethodAnnotations($class, $joinPoint->getMethod(), CacheEvict::class);

        // 方法参数
        $args = ClassObject::convertArgsToKV($class, $joinPoint->getMethod(), $joinPoint->getArgs());
        
        foreach($cacheEvicts as $index => $cacheEvict)
        {
            if($cacheEvict->beforeInvocation)
            {
                $this->deleteCache($cacheEvict, $joinPoint, $args);
                unset($cacheEvicts[$index]);
            }
        }

        $result = $joinPoint->proceed();

        foreach($cacheEvicts as $cacheEvict)
        {
            $this->deleteCache($cacheEvict, $joinPoint, $args);
        }
        
        return $result;
    }

    private function deleteCache($cacheEvict, $joinPoint, $args)
    {
        // 缓存名
        $name = $cacheEvict->name;
        if(null === $name)
        {
            $name = Config::get('@currentServer.cache.default');
            if(null === $name)
            {
                throw new \RuntimeException('config "cache.default" not found');
            }
        }

        // 键
        $key = $this->getKey($joinPoint, $args, $cacheEvict);
        $cacheInstance = CacheManager::getInstance($name);

        $cacheInstance->delete($key);
    }

}