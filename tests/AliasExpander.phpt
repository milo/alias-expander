<?php

/**
 * Test: AliasExpander basics
 *
 * @author  Miloslav Hůla
 */

namespace Test\Space;

use Tester\Assert,
	Milo\AliasExpander;

require __DIR__ . '/bootstrap.php';



$expander = new AliasExpander;



use First, Second as Sec;
use Third as Thi;
use \Fourth as Fou;

$cases = array(
	'\Absolute' => 'Absolute',
	'\Absolute\Foo' => 'Absolute\Foo',

	'First' => 'First',
	'First\Foo' => 'First\Foo',
	'Foo\First' => __NAMESPACE__ . '\Foo\First',
	'Foo\First\Bar' => __NAMESPACE__ . '\Foo\First\Bar',

	'Sec' => 'Second',
	'Sec\Foo' => 'Second\Foo',
	'Foo\Sec' => __NAMESPACE__ . '\Foo\Sec',
	'Foo\Sec\Bar' => __NAMESPACE__ . '\Foo\Sec\Bar',
	'Second' => __NAMESPACE__ . '\Second',
	'Second\Foo' => __NAMESPACE__ . '\Second\Foo',

	'Thi' => 'Third',

	'Fou' => 'Fourth',
);

foreach ($cases as $alias => $expanded) {
	Assert::same( $expanded, $expander->expand($alias) );
}



# 'use' clause in code
Assert::same( __NAMESPACE__ . '\Fif', $expander->expand('Fif') );
use Fifth as Fif;
Assert::same( 'Fifth', $expander->expand('Fif') );



# Switch namespace
namespace Test\Universe;

use Tester\Assert;


use Sixth as Six;

Assert::same( __NAMESPACE__ . '\First', $expander->expand('First') );
Assert::same( 'Sixth', $expander->expand('Six') );

Assert::same( __NAMESPACE__ . '\Sec', $expander->expand('Sec') );
use Second as Sec;
Assert::same( 'Second', $expander->expand('Sec') );



# Reset namespace
namespace Test\Universe;

use Tester\Assert;

Assert::same( __NAMESPACE__ . '\Six', $expander->expand('Six') );
use Sixth as Six;
Assert::same( 'Sixth', $expander->expand('Six') );
