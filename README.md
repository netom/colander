Colander
==

Colander is an input filter and validator library for PHP
with a functional twist.

Function naming conventions
---------------------------

is*: functions that execute a boolean check and throw an exception if the result
is false.

f*: factory functions: functions that return functions. Example: fIsNull will
return the isNull function.


