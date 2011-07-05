Patchwork PHP Parser
====================

Patchwork PHP Parser is a wrapper around PHP's tokenizer extension.
It is aimed at building high-performance tools for transforming and analysing PHP code.
It is written in PHP, runs in PHP 5.1.4+ and knows everything about PHP 5.3 and namespaces.
Here, high-performance means:

* fast,
* memory efficient,
* and easy to extend!

It can be used for example to:

* compute static code analysis,
* verify coding practices for QA,
* backport some language features,
* extend the PHP language,
* build a code preprocessor,
* build an aspect weaver,
* etc.

This code is extracted from the [Patchwork](http://pa.tchwork.com/) framework,
where it has been proven stable and flexible enough to implement many kind of code transformations.

It is released here standalone in the hope that it can be used in a different context successfully!
