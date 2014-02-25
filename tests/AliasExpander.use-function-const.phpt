<?php

/**
 * Test: AliasExpander with 'use function' and 'use constant'
 *
 * @author  Miloslav Hůla
 * @phpVersion 5.6
 */

namespace Test;

require __DIR__ . '/bootstrap.php';


use Tester\Assert,
	Milo\AliasExpander;

$expander = new AliasExpander;


Assert::same( 'Test\foo_bar', $expander->expand('foo_bar') );
Assert::same( 'Test\foo_C', $expander->expand('foo_C') );

use function Foo\bar as foo_bar;
use const Foo\C as FOO_C;

Assert::same( 'Test\foo_bar', $expander->expand('foo_bar') );
Assert::same( 'Test\foo_C', $expander->expand('foo_C') );

use Foo\bar as foo_bar;
use Foo\C as FOO_C;

Assert::same( 'Foo\bar', $expander->expand('foo_bar') );
Assert::same( 'Foo\C', $expander->expand('foo_C') );
