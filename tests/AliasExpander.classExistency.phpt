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


# Class existency check
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
