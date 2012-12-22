Patchwork PHP Parser
====================

Patchwork PHP Parser is aimed at building fast, memory efficient and highly
modular PHP code transformations and analysis. It is written for PHP 5.3 around
the tokenizer extension.

It can be used for example to:

* compute static code analysis,
* verify coding practices for QA,
* backport some language features,
* extend the PHP language,
* build a code preprocessor,
* build an aspect weaver,
* etc.

As an illustrative example, it can backport namespaces and closures and should
easily be able to compile itself to PHP 5.2.

Although it is written for PHP 5.3, it can parse PHP 5.4 code and already has
backports for the short array syntax, the binary number notation and enabling
the short open echo tag regardless of the short_open_tag ini setting.

Licensing
---------

Patchwork PHP Parser is free software; you can redistribute it and/or modify it
under the terms of the (at your option):
- [Apache License v2.0](http://apache.org/licenses/LICENSE-2.0.txt), or
- [GNU General Public License v2.0](http://gnu.org/licenses/gpl-2.0.txt).

This code is extracted from the [Patchwork](http://pa.tchwork.com/) framework,
where it has been proven stable and flexible enough to implement many kind of
code transformations.

It is released here standalone in the hope that it can be used in a different
context successfully!
