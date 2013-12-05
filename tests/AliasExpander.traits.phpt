<?php

/**
 * Test: AliasExpander with namespaces in blocks
 *
 * @author  Miloslav Hůla
 * @phpversion 5.4
 */

namespace Test;

require __DIR__ . '/bootstrap.php';


use Tester\Assert,
	Milo\AliasExpander;

$expander = new AliasExpander;


trait TraitA {
	function foo() {}
}

trait TraitB {
	use TraitA;
}

class ClassA {
	use TraitA, TraitB {
		TraitA::foo insteadof TraitB;
	}

	function foo() {}
}

Assert::same( __NAMESPACE__ . '\TraitA', $expander->expand('TraitA') );
Assert::same( __NAMESPACE__ . '\TraitB', $expander->expand('TraitB') );
