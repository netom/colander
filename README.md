Colander
==

Colander is an input filter and validator library for PHP with a functional twist.

Colander lets you define (input) data filter and validator functions. In PHP or
similar imperative, dynamic programming languages it is usually done by
defining a DSL (domain specific language), and describing the problem using
that DSL, usually using strings or text files (config files).

Such solutions are either perform poorly in terms of speed, or are complicated.
Performance is usually poor because the configuration has to be parsed each
time a request is made, and compications can arise if someone attempts to
cache the results.

Colander solves this problem by NOT using a separate DSL, but defining an
EDSL (embedded DSL) that exploits the features of PHP itself.

The code describing data validation still looks declarative e.g. something like
a configuration file instead of an imperative program, but it consists entirelly
of native PHP expressions.

Colander therefore brings the best of the two worlds (first one being config file
land, other being imperative check-this-and-that kingdom). You can have nice
looking description of data filters and validators (should I say 'processors' ?),
and your opcache will be happy.

So,

## How does a data processor description look like?

Let's cut the chase:

    $processor = map([
        'username' => seq([fMinLength(3), fmaxLength(64)]),
        'age' => seq([fInteger(), fMin(13)])
    ]);

The expression above assigns the `$processor` variable a _function_.
That's right, the variable will contain a function that can be called with
a single parameter containing data to validate.

Colander was designed so none of it's resulting functions has any _side effects_,
that is: it does not print or read anything, doesn't set global variables, etc.
This is important because you can re-use these generated functions as many
times as you want, and the results will always be the same for the same input.

Let's take a look at the definition above, but I'm sure you already have a
pretty good idea what the generated function will do.

## Factory functions

In the example `fMinLength`, `fInteger`, etc. are _factories_. They themselves
return functions.

`fInteger()` will return a function that takes a single argument, and if it's an
integer, it returns it, if it's not, it throws a ValidationException.

All factory functions work very similarly. They took some (or no) arguments
and return you a validation or filter function (_processor_ from now on please).

## Validation and Filter processors

A processor is said to be a validation processor if it returns it's input unaltered,
but it might throw an exception.

A filter processor might return a value other than it's input.

## Using the processors

The example above can be extended like this:

    $processor = map([
        'username' => seq([fMinLength(3), fmaxLength(64)]),
        'age' => seq([fInteger(), fMin(13)])
    ]);

    $valid_post = $processor($_POST);

Since PHP don't allow onr to try and call the result of an arbitrary expression
(like, say, JavaScript does), we provided `call()`, a convenient function, so
it's possible to define AND use processors in a single expression:

    $valid_post = call(map([
        'username' => seq([fMinLength(3), fmaxLength(64)]),
        'age' => seq([fInteger(), fMin(13)])
    ]), $_POST);

##  Ok but what map() and seq() is all about?

Yupp, and there's tpl() and lst() too.

These are _combinators_. They combine existing processors to form new ones.

See, a processor is just a function that takes a single argument, and returns
a value or throws an exception. Pretty simple eh?

Such functions can be used to explode in your face if you have a string that's
too long, or a value that should be an integer, but it's an array instead.

It's still kinda complicated to check for form fields, or to check multiple rules
one after the other. Well, `map()` and `seq()` help you exactly these cases.

* `map()` is a function that returns a processor. It's input is an associative array.
Each field in the array must be a processor itself. The function produced by
`map()` takes an argument that must be an array itself, and runs it's
processors against the corresponding fields. Thus in the above examples the
processor returned by the `map()` combinator will check the 'username' and
'age' fields in any array run through it.

* `seq()` will return a processor that is the _sequence_ of the processors given
to it in an array. 

* `tpl()` can be used to validate _tuples_, arrays of predetermined length, and
with zero-based integer indexes. It's parameter is an array of processors,
similarly to `seq()`, but it runs them against each corresponding array element,
just like `map()` would do.

* `lst()` can be used to process arrays of the same kind of data (lists if you like).
Actually `lst()` works on any kind of Traversable object too.

## Simple processors

Colander comes with a collection of simple processors. Nearly all php is_*
function has its Colander counterpart (in the form if is* (with camelCase), and
fIs* (it's factory)).

Until all of those get documented, please refer to the source code.

## Validation function naming conventions

is*: functions that execute a boolean check and throw an exception if the result
is false.

f*: factory functions: functions that return functions. Example: fIsNull will
return the isNull function.

*_: the function acts on a collection of values, and it has a number of other
functions that act on the collection members. Each of these functions can
throw ValidationExceptions. Functions with names ending with _ stop at the first
error they encounter. The _ always comes before S (if theres an S).

*S the function acts on a collection of values. If the function cannot process
all members of the collection (because an array is too long, or there are
unchecked keys in it), it will throw an IncompleteValidationException (extends
ValidationException).
