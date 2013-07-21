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
);

foreach ($cases as $alias => $expanded) {
	Assert::same( $expanded, $expander->expand($alias) );
}



/* 'use' clause in code */
Assert::same( __NAMESPACE__ . '\Fif', $expander->expand('Fif') );
use Fifth as Fif;
Assert::same( 'Fifth', $expander->expand('Fif') );



/* Switch namespace */
namespace Test\Universe;

use Tester\Assert,
	Milo\Utils\AliasExpander;


use Sixth as Six;

Assert::same( __NAMESPACE__ . '\First', $expander->expand('First') );
Assert::same( 'Sixth', $expander->expand('Six') );

Assert::same( __NAMESPACE__ . '\Sec', $expander->expand('Sec') );
use Second as Sec;
Assert::same( 'Second', $expander->expand('Sec') );



/* Wrapping expand() */
function wrapOne($alias, $depth) {
	global $expander;
	return $expander->expand($alias, $depth + 1);
}

function wrapTwo($alias, $depth) {
	return wrapOne($alias, $depth + 1);
}

function aliasFqn($alias) {
	return wrapTwo($alias, 1);
}

Assert::same( 'Second', aliasFqn('Sec') );



/* Class existency check */
use Nonexists as Non;

Assert::same( 'Nonexists', $expander->expand('Non') );

$expander->setExistsCheck(TRUE);

Assert::exception( function() use ($expander) {
	$expander->expand('Non');
}, 'RuntimeException', 'Class Nonexists not found');

$expander->setExistsCheck(E_USER_NOTICE);
Assert::error( function() use ($expander) {
	$expander->expand('Non');
}, E_USER_NOTICE, 'Class Nonexists not found');
