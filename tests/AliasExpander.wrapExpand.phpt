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


use Second as Sec;

# Wrapping expand()
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
