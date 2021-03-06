Eris Runner
===========

Eris Runner is a test runner for [Eris](https://github.com/giorgiosironi/eris)

With this runner you don't need an extra testing framework to run your Property Based Testing.

All you need is a directory or a file with properties written like this:

```php

<?php

property ("strings have the same length in reverse", function() {
    forAll (Gen::string()) (function ($s) {
        Assert::same(strlen($s), strlen(strrev($s)));
    });
});

property ("positive integers squared are always bigger than themselves", function() {
    forAll (Gen::integer())
        // ok zero * zero is still zero
        ->when (function($x) { return $x > 0; })
        ->then (function ($x) {

        // Should fail! Did you think about 1? ;-)
        Assert::greaterThan($x * $x, $x);
    });
});

```

Then just call the runner to run all tests in the `tests` folder:

```bash
$ bin/eris tests
```

Or a single php file:

```bash
$ bin/eris my_test.php
```

Note that Eris Runner lets you use [Webmozart's Assert library](https://github.com/webmozart/assert) as assertions. But you can use whatever you like. Just make sure you throw some exception.