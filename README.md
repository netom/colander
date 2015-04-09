Colander
==

Colander is an input filter and validator library for PHP
with a functional twist.

Validation function naming conventions
--

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
