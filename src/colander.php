<?php

class ValidationException extends \Exception {}

class IncompleteValidationException extends ValidationException {}

/**
 * If the first parameter is false, throw a ValidationException
 */
function trueOrX($bool, $errmsg) {
    if (!$bool) throw new ValidationException($errmsg);
}

/**
 * Combine callables into a sequence
 *
 * Combine exceptions along the way
 * 
 * seq([f1, f2, f3])($d) = f3(f2(f1($d)))
 */
function seq($callables) {
    return function ($data) use ($callables) {
        $ret = $data;

        $exceptions = [];

        foreach ($callables as $callable) {
            try {
                $ret = $callable($ret);
            } catch (ValidationException $e) {
                $exceptions[] = $e;
            }
        }

        if (count($exceptions) > 0) {
            throw new ValidationException(join(', ', array_map(function ($e) {
                return $e->getMessage();
            }, $exceptions)));
        }

        return $ret;
    };
}

/**
 * Combine callables into a sequence
 *
 * Throw exception immediately at the first problem
 */
function seq_($callables) {
    return function ($data) use ($callables) {
        $ret = $data;
        foreach ($callables as $callable) {
            $ret = $callable($ret);
        }
        return $ret;
    };
}

/**
 * Combine a map of validation functions into a map validator
 * 
 * Fields with no rules are returned unchanged.
 * Combine exceptions.
 *
 * map(['f1' => f1, 'f2' => f2], $d) = ['f1' => f1($d['f1'], 'f2' => f2($d['f2'])]
 */
function map($callables) {
    return function ($data) use ($callables) {
        $ret = $data;

        $exceptions = [];

        foreach ($callables as $name => $callable) {
            try {
                if (array_key_exists($name, $ret)) {
                    $ret[$name] = $callable($ret[$name]);
                }
            } catch (ValidationException $e) {
                $exceptions[$name] = $e;
            }
        }

        if (count($exceptions) > 0) {
            $messages = [];
            foreach ($exceptions as $name => $e) {
                $messages[] = "the field $name is " . $e->getMessage();
            }
            throw new ValidationException(join("\n", $messages));
        }

        return $ret;
    };
}

/**
 * Combine a map of validation functions into a map validator
 * 
 * Fields with no rules count as validation errors.
 * Combine exceptions.
 */
function mapS($callables) {
    return seq([
        function ($data) use ($callables) {
            $diff = array_diff(array_keys($data), array_keys($callables));
            if (count($diff) > 0) {
                throw new IncompleteValidationException("the following fields lack validation rules: " . implode(',', $diff));
            }
            return $data;
        },
        map($callables)
    ]);
}

/*
 * Combine a map of validation functions into a map validator
 *
 * Fields with no rules are returned unchanged.
 * Throw exception immediately at the first problem.
 */
function map_($callables) {
    return function ($data) use ($callables) {
        $ret = $data;
        foreach ($callables as $name => $callable) {
            if (array_key_exists($name, $ret)) {
                try {
                    $ret[$name] = $callable($ret[$name]);
                } catch (ValidationException $e) {
                    $class = get_class($e);
                    throw new $class("the field $name is " . $e->getMessage());
                }
            }
        }
        return $ret;
    };
}

/**
 * Combine a map of validation functions into a map validator
 * 
 * Fields with no rules count as validation errors.
 * Throw exception immediately at the first problem.
 */
function map_S($callables) {
    return seq_([
        function ($data) use ($callables) {
            $diff = array_diff(array_keys($data), array_keys($callables));
            if (count($diff) > 0) {
                throw new IncompleteValidationException("the following fields lack validation rules: " . implode(',', $diff));
            }
            return $data;
        },
        map_($callables)
    ]);
}

/*
 * Combine a tuple of filter functions into a tuple filter
 * 
 * If the array of callables is shorter than the input tuple, the dangling
 * elements remain unchecked. If the input tuple is shorter than the callable
 * array, the dangling validation functions don't execute.
 * 
 * Combine exceptions.
 */
function tpl($callables) {
    return function ($data) use ($callables) {
        $ret = $data;

        $exceptions = [];

        foreach ($callables as $i => $callable) {
            try {
                $ret[$i] = $callable($ret[$i]);
            } catch (ValidationException $e) {
                $exceptions[$i] = $e;
            }
        }

        if (count($exceptions) > 0) {
            $messages = [];
            foreach ($exceptions as $i => $e) {
                $messages[] = "the value $i is " . $e->getMessage();
            }
            throw new ValidationException(join("\n", $messages));
        }

        return $ret;
    };
}

/**
 * Combine a tuple of filter functions into a tuple filter
 * 
 * If the array of callables is shorter than the input tuple, the dangling
 * elements remain unchecked. If the input tuple is shorter than the callable
 * array, the dangling validation functions don't execute.
 * 
 * Throw exception immediately at the first problem.
 */
function tpl_($callables) {
    return function ($data) use ($callables) {
        $ret = $data;

        foreach ($callables as $i => $callable) {
            try {
                $ret[$i] = $callable($ret[$i]);
            } catch (ValidationException $e) {
                $class = get_class($e);
                throw new $class("the value $i is " . $e->getMessage());
            }
        }

        return $ret;
    };
}

/*
 * Apply a validation rule to all elements of a list
 * 
 * Combine exceptions.
 */
function lst($callable) {
    return function ($data) use ($callable) {
        $ret        = [];
        $exceptions = [];
        foreach ($data as $i => $value) {
            try {
                $ret[$i] = $callable($data[$i]);
            } catch (ValidationException $e) {
                $exceptions[$i] = $e;
            }
        }

        if (count($exceptions) > 0) {
            $messages = [];
            foreach ($exceptions as $i => $e) {
                $messages[] = "the value $i is " . $e->getMessage();
            }
            throw new ValidationException(join("\n", $messages));
        }

        return $ret;
    };
}

/*
 * Apply a validation rule to all elements of a list
 * 
 * Throw exception immediately at the first problem.
 */
function lst_($callable) {
    return function ($data) use ($callable) {
        $ret = [];
        foreach ($data as $i => $value) {
            try {
                $ret[$i] = $callable($data[$i]);
            } catch (ValidationException $e) {
                $class = get_class($e);
                throw new $class("the value $i is " . $e->getMessage());
            }
        }

        return $ret;
    };
}

/**
 * Call a callable with a single parameter
 */
function call($callable, $param) {
    return call_user_func($callable, $param);
}

function isEmpty($d) {
    trueOrX(empty($d), "not empty");
    return $d;
}

function fIsEmpty() {
    return function() { return isEmpty($d); };
}

function hasKey($p, $d) {
    trueOrX(array_key_exists($p, $d), "doesn't have key $p");
    return $d;
}

function fHasKey($p) {
    return function ($d) use ($p) { return hasKey($p, $d); };
}

function isArray($d) {
    trueOrX(is_array($d), "not a array");
    return $d;
}

function fIsArray() {
    return function($d) { return isArray($d); };
}

function isBool($d) {
    trueOrX(is_bool($d), "not a bool");
    return $d;
}

function fIsBool() {
    return function($d) { return isBool($d); };
}

function isCallable($d) {
    trueOrX(is_callable($d), "not a callable");
    return $d;
}

function fIsCallable() {
    return function($d) { return isCallable($d); };
}

function isDouble($d) {
    trueOrX(is_double($d), "not a double");
    return $d;
}

function fIsDouble() {
    return function($d) { return isDouble($d); };
}

function isFloat($d) {
    trueOrX(is_float($d), "not a float");
    return $d;
}

function fIsFloat() {
    return function($d) { return isFloat($d); };
}

function isInt($d) {
    trueOrX(is_int($d), "not a int");
    return $d;
}

function fIsInt() {
    return function($d) { return isInt($d); };
}

function isInteger($d) {
    trueOrX(is_integer($d), "not a integer");
    return $d;
}

function fIsInteger() {
    return function($d) { return isInteger($d); };
}

function isLong($d) {
    trueOrX(is_long($d), "not a long");
    return $d;
}

function fIsLong() {
    return function($d) { return isLong($d); };
}

function isNull($d) {
    trueOrX(is_null($d), "not a null");
    return $d;
}

function fIsNull() {
    return function($d) { return isNull($d); };
}

function isNumeric($d) {
    trueOrX(is_numeric($d), "not a numeric");
    return $d;
}

function fIsNumeric() {
    return function($d) { return isNumeric($d); };
}

function isObject($d) {
    trueOrX(is_object($d), "not a object");
    return $d;
}

function fIsObject() {
    return function($d) { return isObject($d); };
}

function isReal($d) {
    trueOrX(is_real($d), "not a real");
    return $d;
}

function fIsReal() {
    return function($d) { return isReal($d); };
}

function isResource($d) {
    trueOrX(is_resource($d), "not a resource");
    return $d;
}

function fIsResource() {
    return function($d) { return isResource($d); };
}

function isScalar($d) {
    trueOrX(is_scalar($d), "not a scalar");
    return $d;
}

function fIsScalar() {
    return function($d) { return isScalar($d); };
}

function isString($d) {
    trueOrX(is_string($d), "not a string");
    return $d;
}

function fIsString() {
    return function($d) { return isString($d); };
}

function maxLen($p, $d) {
    trueOrX(strlen($d) <= $p, "longer than $p");
    return $d;
}

function fMaxLen($p) {
    return function($d) use ($p) { return maxLen($p, $d); };
}

function minLen($p, $d) {
    trueOrX(strlen($d) >= $p, "shorter than $p");
    return $d;
}

function fMinLen($p) {
    return function($d) use ($p) { return minLen($p, $d); };
}

function maxCount($p, $d) {
    trueOrX(count($d) <= $p, "longer than $p");
    return $d;
}

function fMaxCount($p) {
    return function($d) use ($p) { return maxCount($p, $d); };
}

function minCount($p, $d) {
    trueOrX(count($d) >= $p, "shorter than $p");
    return $d;
}

function fMinCount($p) {
    return function($d) use ($p) { return minCount($p, $d); };
}

function maxNum($p, $d) {
    trueOrX($d <= $p, "greater than $p");
    return $d;
}

function fMaxNum($p) {
    return function($d) use ($p) { return maxNum($p, $d); };
}

function minNum($p, $d) {
    trueOrX($d >= $p, "smaller than $p");
    return $d;
}

function fMinNum($p) {
    return function($d) use ($p) { return minNum($p, $d); };
}

function ensureKey($p, $d) {
    return array_key_exists($p, $d) ? $d : array_merge($d, [$p => null]);
}

function fEnsureKey($p) {
    return function($d) use ($p) { return ensureKey($p, $d); };
}

function defaultValue($p, $d) {
    return isNull($d) ? $p : $d;
}

function fDefaultValue($p) {
    return function($d) use ($p) { return defaultValue($p, $d); };
}
