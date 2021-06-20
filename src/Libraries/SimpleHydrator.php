<?php


namespace App\Libraries;

class SimpleHydrator
{

    public static function fillOut(object $entity, array $data, array $except = [], $sanitizers = []): object
    {
        foreach ($data as $field => $value) {
            if (in_array($field, $except)) {
                continue;
            }
            $method = "set" . ucfirst($field);
            if (!method_exists($entity, $method)) {
                continue;
            }
            
            if (isset($sanitizers[$field]) && is_callable($sanitizers[$field])) {
                $value = call_user_func($sanitizers[$field], $value);
            }
            $entity->$method($value);
        }
        
        return $entity;
    }

}