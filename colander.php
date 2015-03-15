<?php

class FactoryException extends Exception {}

class ValidationException extends Exception {}

/*
 * If the first parameter is false, throw exception
 */
function trueOrX($bool, $errmsg) {
    if (!$bool) throw new ValidationException($errmsg);
}

function _chkCallables($callables) {
    if (!is_array( $callables ) && ! $callables instanceof Traversable ) {
        throw new FactoryException(
            'The seq combinator expects a Traversable or array of callables. ' .
            'The given parameter is not Traversable, nor an array.'
        );
    }

    foreach ($callables as $callable) {
        if (!is_callable($callable, true)) {
            throw new FactoryException(
                'The seq combinator expects a Traversable of callables. ' .
                'An element of the input list is not callable.'
            );
        }
    }
}

/*
 * Combine callables into a sequence
 *
 * Combine exceptions along the way
 * 
 * seq([f1, f2, f3])($d) = f3(f2(f1($d)))
 */
function seq($callables) {
    _chkCallables($callables);

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

/*
 * Combine callables into a sequence
 *
 * Throw exception immediately at the first problem
 * 
 * seq([f1, f2, f3])($d) = f3(f2(f1($d)))
 */
function seq_($callables) {
    _chkCallables($callables);

    return function ($data) use ($callables) {
        $ret = $data;
        foreach ($callables as $callable) {
            $ret = $callable($ret);
        }
        return $ret;
    };
}

/*
 * Combine a map of validation functions into a map validator
 * 
 * map(['f1' => f1, 'f2' => f2], $d) = ['f1' => f1($d['f1'], 'f2' => f2($d['f2'])]
 * 
 * It returns fields with no filter rules unchanged.
 */
function map($callables) {
    _chkCallables($callables);

    return function ($data) use ($callables) {
        $ret = $data;

        $exceptions = [];

        foreach ($callables as $name => $callable) {
            try {
                $ret[$name] = $callable($ret[$name]);
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

/*
 * Combine a map of validation functions into a map validator
 * 
 * map(['f1' => f1, 'f2' => f2], $d) = ['f1' => f1($d['f1'], 'f2' => f2($d['f2'])]
 * 
 * Fields with no rules count as valudation errors.
 */
function map_() {
    _chkCallables($map);

    return function ($data) use ($map) {
        $ret = $data;
        foreach ($callables as $name => $callable) {
            $ret[$name] = $callable($ret[$name]);
        }
        return $ret;
    };
}

/*
 * Combine a tuple of filter functions into a tuple filter
 */
function tuple() {
    // TODO
}

/*
 * A more strict tuple validator 
 */
function tuple_() {
    // TODO
}

/*
 * Apply a validation rule to all elements of a list
 */
function lst() {
    // TODO
}

/*
 * Call a callable with a single parameter
 */
function call($callable, $param) {
    return call_user_func($callable, $param);
}

/*
 * Generates and/or loads PHP code
 * 
 * Most of the filter / factory functions is just mindless
 * boilerplate. This function takes care of that.
 */
function mkBolilerplate($bpfile = '/tmp/colander_generated.php') {

    if (is_file($bpfile)) {
        require_once $bpfile;
        return;
    }

    $php = "<?php\n\n";

    /*
     * PHP's is_* functions wrapped as filter functions
     */

    $a = [
        'array',
        'bool',
        'callable',
        'double',
        'float',
        'int',
        'integer',
        'long',
        'null',
        'numeric',
        'object',
        'real',
        'resource',
        'scalar',
        'string'
    ];

    foreach ($a as $name) {
        $filtname = 'is' . ucfirst($name);    // Filter function name
        $factname = 'f' . ucfirst($filtname); // Factory function name
        $pname    = 'is_' . $name;            // PHP function name

        $_d = '$d';

        $php .= 
            "function $filtname($_d) {\n" .
            "    trueOrX($pname($_d), \"not a $name\");\n" .
            "    return $_d;\n" .
            "}\n" .
            "function $factname() {\n" .
            "    return function($_d) { return $filtname($_d); };\n" .
            "}\n\n";
    }

    /*
     * Our validation functions
     */
     
    $a = [
        'maxLen'   => ['strlen($d) <= $p', 'longer than $p'],
        'minLen'   => ['strlen($d) >= $p', 'shorter than $p'],
        'maxCount' => ['count($d) <= $p', 'longer than $p'],
        'minCount' => ['count($d) >= $p', 'shorter than $p'],
        'maxNum'   => ['$d <= $p', 'greater than $p'],
        'minNum'   => ['$d >= $p', 'smaller than $p']
    ];
    
    foreach ($a as $name => list($cond, $errmsg)) {
        $filtname = $name;
        $factname = 'f' . ucfirst($name);

        $_d = '$d';
        $_p = '$p';

        $php .= 
            "function $filtname($_p, $_d) {\n" .
            "    trueOrX($cond, \"$errmsg\");\n" .
            "    return $_d;\n" .
            "}\n" .
            "function $factname($_p) {\n" .
            "    return function($_d) use ($_p) { return $filtname($_p, $_d); };\n" .
            "}\n\n";
    }

    file_put_contents($bpfile, $php);

    require_once $bpfile;
}
