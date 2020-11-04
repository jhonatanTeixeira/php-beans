<?php


namespace PhpBeans\Metadata;


trait ParamResolverTrait
{
    /**
     * @param \ReflectionParameter[] $params
     *
     * @return \Generator
     */
    private function resolveParams(array $params) {
        foreach ($params as $param) {
            $class = $param->getClass();
            $name = $param->getName();

            yield [
                "className" => $class ? $class->getName() : null,
                "name"      => $name
            ];
        }

    }
}