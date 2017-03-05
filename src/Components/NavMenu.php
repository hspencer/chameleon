<?php
/**
 * File holding the NavMenu class
 *
 * This file is part of the MediaWiki skin Chameleon.
 *
 * @copyright 2013 - 2016, Stephan Gambke
 * @license   GNU General Public License, version 3 (or any later version)
 *
 * The Chameleon skin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * The Chameleon skin is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @file
 * @ingroup   Skins
 */

namespace Skins\Chameleon\Components;

use Linker;
use Skins\Chameleon\IdRegistry;

/**
 * The NavMenu class.
 *
 * @author  Stephan Gambke
 * @since   1.0
 * @ingroup Skins
 */
class NavMenu extends Component {

	/**
	 * Builds the HTML code for this component
	 *
	 * @return string the HTML code
	 */
	public function getHtml() {

		$ret = '';

		$sidebar = $this->getSkinTemplate()->getSidebar( array(
				'search' => false, 'toolbox' => $this->showTools(), 'languages' => $this->showLanguages()
			)
		);

		$msg = \Message::newFromKey( 'skin-chameleon-navmenu-flatten' );

		if ( $msg->exists() ) {
			$flatten = array_map( 'trim', explode( ';', $msg->plain() ) );
		} elseif ( $this->getDomElement() !== null ) {
			$flatten = array_map( 'trim', explode( ';', $this->getDomElement()->getAttribute( 'flatten' ) ) );
		} else {
			$flatten = array();
		}

		// create html for each sidebar box
		foreach ( $sidebar as $menuName => $menuDescription ) {
			$ret .= $this->getHtmlForSubMenu( $menuName, $menuDescription, array_search( $menuName, $flatten ) !== false );
		}

		return '<ul class="navbar-nav">' . $ret . "</ul>\n";
	}

	/**
	 * @return bool
	 */
	private function showLanguages() {
		return $this->getDomElement() !== null && filter_var( $this->getDomElement()->getAttribute( 'showLanguages' ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * @return bool
	 */
	private function showTools() {
		return $this->getDomElement() !== null && filter_var( $this->getDomElement()->getAttribute( 'showTools' ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Create a single dropdown
	 *
	 * @param string $menuName
	 * @param mixed[] $menuDescription
	 * @param bool $flatten
	 *
	 * @return string
	 */
	protected function getHtmlForSubMenu( $menuName, $menuDescription, $flatten = false ) {

		// open list item containing the dropdown
		$ret = $this->indent() . '<!-- ' . $menuName . ' -->';

		if ( $flatten ) {

			$ret .= $this->getHtmlForFlatMenu( $menuDescription );

		} elseif ( !$this->hasSubmenuItems( $menuDescription ) ) {

			$ret .= $this->getHtmlForDropdownMenuStub( $menuDescription );

		} else {

			$ret .= $this->getHtmlForDropdownMenu( $menuDescription );

		}

		return $ret;
	}

	/**
	 * @param mixed[] $menuDescription
	 * @param int $indent
	 *
	 * @param callable $buildSingleMenuItem
	 * @return string
	 */
	protected function buildMenuItems( $menuDescription, $indent = 0, callable $buildSingleMenuItem = null ) {

		// build the list of submenu items
		if ( $this->hasSubmenuItems( $menuDescription ) ) {

			$menuitems = '';
			$this->indent( $indent );

			foreach ( $menuDescription[ 'content' ] as $key => $item ) {
				$item[ 'class' ] = '';
				$menuitems .= $buildSingleMenuItem( $item, $key );
			}

			$this->indent( -$indent );
			return $menuitems;

		} else {
			return $this->indent() . '<!-- empty -->';
		}
	}

	/**
	 * @param mixed[] $menuDescription
	 * @param int $indent
	 *
	 * @return string
	 */
	protected function getHtmlForFlatMenu( $menuDescription, $indent = 0 ) {

		return $this->buildMenuItems( $menuDescription, $indent, function ( $item, $key ) {

			$item[ 'class' ] .= ' nav-item';
			$item[ 'link-class' ] = 'nav-link';
			return $this->indent() . $this->getSkinTemplate()->makeListItem( $key, $item );

		} );
	}

	/**
	 * @param $menuDescription
	 * @return string
	 */
	protected function getHtmlForDropdownMenu( $menuDescription ) {
		return $this->buildDropdownOpeningTags( $menuDescription )

			. $this->buildMenuItems( $menuDescription, 2, function ( $item, $key ) {

				$item[ 'class' ] .= ' dropdown-item';
				return $this->indent() . $this->getSkinTemplate()->makeLink( $key, $item );

			} )

			. $this->buildDropdownClosingTags();
	}

	/**
	 * @param mixed[] $menuDescription
	 *
	 * @return bool
	 */
	protected function hasSubmenuItems( $menuDescription ) {
		return is_array( $menuDescription[ 'content' ] ) && count( $menuDescription[ 'content' ] ) > 0;
	}

	/**
	 * @param mixed[] $menuDescription
	 *
	 * @return string
	 */
	protected function getHtmlForDropdownMenuStub( $menuDescription ) {
		return
			$this->indent() . \Html::rawElement( 'li',
				array(
					'class' => 'nav-item',
					'title' => Linker::titleAttrib( $menuDescription[ 'id' ] )
				),
				'<a href="#" class="nav-link">' . htmlspecialchars( $menuDescription[ 'header' ] ) . '</a>'
			);
	}

	/**
	 * @param mixed[] $menuDescription
	 *
	 * @return string
	 */
	protected function buildDropdownOpeningTags( $menuDescription ) {
		// open list item containing the dropdown
		$ret = $this->indent() . \Html::openElement( 'li',
				array(
					'class' => 'nav-item dropdown',
					'title' => Linker::titleAttrib( $menuDescription[ 'id' ] )
				)
			);

		// add the dropdown toggle
		$ret .= $this->indent( 1 ) . '<a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">' .
			htmlspecialchars( $menuDescription[ 'header' ] ) . '</a>';

		// open list of dropdown menu items
		$ret .= $this->indent() .
			$this->indent() . \Html::openElement( 'div',
				array(
					'class' => 'dropdown-menu ' . $menuDescription[ 'id' ],
					'id'    => IdRegistry::getRegistry()->getId( $menuDescription[ 'id' ] ),
				)
			);
		return $ret;
	}

	/**
	 * @return string
	 */
	protected function buildDropdownClosingTags() {
		return
			$this->indent() . '</div>' .
			$this->indent( -1 ) . '</li>';
	}

}
