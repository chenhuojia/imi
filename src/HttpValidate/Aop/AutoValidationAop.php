<?php
namespace Imi\HttpValidate\Aop;

use Imi\Aop\JoinPoint;
use Imi\Aop\PointCutType;
use Imi\Bean\BeanFactory;
use Imi\Validate\Validator;
use Imi\Aop\AroundJoinPoint;
use Imi\Aop\Annotation\After;
use Imi\Aop\Annotation\Around;
use Imi\Aop\Annotation\Aspect;
use Imi\Aop\Annotation\PointCut;
use Imi\Bean\Annotation\AnnotationManager;
use Imi\HttpValidate\Annotation\ExtractData;
use Imi\Util\ObjectArrayHelper;
use Imi\Util\ClassObject;

/**
 * @Aspect
 */
class AutoValidationAop
{
    /**
     * 验证 Http 参数
     * @PointCut(
     *         type=PointCutType::ANNOTATION,
     *         allow={
     *             \Imi\HttpValidate\Annotation\HttpValidation::class
     *         }
     * )
     * @Around
     * @return mixed
     */
    public function validateHttp(AroundJoinPoint $joinPoint)
    {
        $controller = $joinPoint->getTarget();
        $className = BeanFactory::getObjectClass($controller);
        $methodName = $joinPoint->getMethod();

        $annotations = AnnotationManager::getMethodAnnotations($className, $methodName);
        if(isset($annotations[0]))
        {
            $data = ClassObject::convertArgsToKV($className, $methodName, $joinPoint->getArgs());

            $data['$get'] = $controller->request->get();
            $data['$post'] = $controller->request->post();
            $data['$body'] = $controller->request->getParsedBody();

            $validator = new Validator($data, $annotations);
            if(!$validator->validate())
            {
                $rule = $validator->getFailRule();
                $exception = $rule->exception;
                throw new $exception($validator->getMessage(), $rule->exCode);
            }

            foreach($annotations as $annotation)
            {
                if($annotation instanceof ExtractData)
                {
                    list($key, $name) = explode('.', $annotation->name, 2);
                    if(array_key_exists($annotation->to, $data))
                    {
                        $data[$annotation->to] = ObjectArrayHelper::get($data[$key], $name);
                    }
                }
            }

            unset($data['$get'], $data['$post'], $data['$body']);

            $data = array_values($data);
        }
        else
        {
            $data = null;
        }

        return $joinPoint->proceed($data);
    }

}