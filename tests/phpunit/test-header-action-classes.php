<?php
/**
 * A header action that shows a label gets a class saying so.
 *
 * `theme.css` widens a labelled action from this class. It used to find the
 * label with `:has()` alone, which Edge and Chrome before 105 do not support:
 * on an old Edge the action stayed an icon-sized square, "Learn" ran across
 * the next action and "Log in" wrapped onto two lines (accessibilitycloud.com,
 * 2026-11-05). The blocks plugin passes `with_label` and prints what this
 * returns.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

use AWT\Theme\DesignSystem\Registry;

/**
 * Carbon's classes for awt/header-action.
 *
 * @covers \AWT\Theme\DesignSystem\Carbon
 */
class Test_Header_Action_Classes extends WP_UnitTestCase {

	/**
	 * Labelled gets the modifier; icon-only and the label span do not.
	 */
	public function test_with_label_adds_the_modifier(): void {
		$carbon = Registry::all()['carbon'];

		$this->assertSame( 'cds--header__action cds--header__action--with-label', $carbon->classes_for( 'header-action', array( 'with_label' => true ) ) );
		$this->assertSame( 'cds--header__action', $carbon->classes_for( 'header-action', array( 'with_label' => false ) ) );
		$this->assertSame( 'cds--header__action', $carbon->classes_for( 'header-action' ) );
		$label_variants = array(
			'element'    => 'label',
			'with_label' => true,
		);
		$this->assertSame( 'cds--header__action-label', $carbon->classes_for( 'header-action', $label_variants ) );
	}
}
