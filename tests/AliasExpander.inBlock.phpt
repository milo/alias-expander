<?php

/**
 * Test: AliasExpander with namespaces in blocks
 *
 * @author  Miloslav Hůla
 */

namespace Test {
	require __DIR__ . '/bootstrap.php';

	use Tester\Assert,
		Milo\Alias;



	use First;

	$cases = array(
		'\Absolute' => 'Absolute',
		'First' => 'First',
		'Foo' => __NAMESPACE__ . '\Foo',
	);

	foreach ($cases as $alias => $expanded) {
		Assert::same( $expanded, Alias::expand($alias) );
	}
}

namespace Test\Space {
	use Tester\Assert,
		Milo\Alias;



	use First;

	$cases = array(
		'\Absolute' => 'Absolute',
		'First' => 'First',
		'Foo' => __NAMESPACE__ . '\Foo',
	);

	foreach ($cases as $alias => $expanded) {
		Assert::same( $expanded, Alias::expand($alias) );
	}
}
