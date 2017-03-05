<?php
/**
 * File holding the NavbarHorizontal class
 *
 * This file is part of the MediaWiki skin Chameleon.
 *
 * @copyright 2013 - 2015, Stephan Gambke
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

use Hooks;
use Skins\Chameleon\IdRegistry;

/**
 * The NavbarHorizontal class.
 *
 * A horizontal navbar containing the sidebar items.
 * Does not include standard items (toolbox, search, language links). They need
 * to be added to the page elsewhere
 *
 * The navbar is a list of lists wrapped in a nav element: <nav
 * role="navigation" id="p-navbar" >
 *
 * @author  Stephan Gambke
 * @since   1.0
 * @ingroup Skins
 */
class NavbarHorizontal extends Component {

	private $mHtml = null;
	private $htmlId = null;

	/**
	 * Builds the HTML code for this component
	 *
	 * @return String the HTML code
	 */
	public function getHtml() {

		if ( $this->mHtml === null ) {
			$this->buildHtml();
		}

		return $this->mHtml;
	}

	/**
	 *
	 */
	protected function buildHtml() {

		if ( $this->getDomElement() === null ) {
			$this->mHtml = '';
			return;
		}

		$this->mHtml =
			$this->buildFixedNavBarIfRequested() .
			$this->buildNavBarOpeningTags() .
			$this->buildNavBarComponents() .
			$this->buildNavBarClosingTags();
	}

	/**
	 *
	 */
	protected function buildFixedNavBarIfRequested() {
		// if a fixed navbar is requested
		if ( filter_var( $this->getDomElement()->getAttribute( 'fixed' ), FILTER_VALIDATE_BOOLEAN ) === true ||
			$this->getDomElement()->getAttribute( 'position' ) === 'fixed'
		) {

			// first build the actual navbar and set a class so it will be fixed
			$this->getDomElement()->setAttribute( 'fixed', '0' );
			$this->getDomElement()->setAttribute( 'position', '' );

			$realNav = new self( $this->getSkinTemplate(), $this->getDomElement(), $this->getIndent() );
			$realNav->setClasses( $this->getClassString() . ' navbar-fixed-top' );

			// then add an invisible copy of the nav bar that will act as a spacer
			$this->addClasses( 'navbar-static-top invisible' );

			return $realNav->getHtml();
		} else {
			return '';
		}
	}

	/**
	 * @return string
	 */
	protected function buildNavBarOpeningTags() {
		$openingTags =
			$this->indent() . '<!-- navigation bar -->' .
			$this->indent() . \Html::openElement( 'nav', array(
					'class' => 'mw-navigation p-navbar navbar navbar-toggleable-md navbar-light bg-faded ' . $this->getClassString(),
					'role'  => 'navigation',
					'id'    => $this->getHtmlId()
				)
			);

		return $openingTags;
	}

	/**
	 * @return string
	 */
	private function getHtmlId() {
		if ( $this->htmlId === null ) {
			$this->htmlId = IdRegistry::getRegistry()->getId( 'mw-navigation' );
		}
		return $this->htmlId;
	}

	/**
	 *
	 */
	protected function buildNavBarComponents() {

		$elements = $this->buildNavBarElementsFromDomTree();

		return
			$this->buildHead( $elements[ 'head' ] ) .
			$this->buildTail( $elements[ 'left' ], $elements['right'] );
	}

	/**
	 * @return string[][]
	 */
	protected function buildNavBarElementsFromDomTree() {

		$elements = array(
			'head'  => array(),
			'left'  => array(),
			'right' => array(),
		);

		/** @var \DOMElement[] $children */
		$children = $this->getDomElement()->hasChildNodes() ? $this->getDomElement()->childNodes : array();

		// add components
		foreach ( $children as $node ) {
			$this->buildAndCollectNavBarElementFromDomElement( $node, $elements );
		}
		return $elements;
	}

	/**
	 * @param \DOMElement $node
	 * @param $elements
	 */
	protected function buildAndCollectNavBarElementFromDomElement( $node, &$elements ) {

		if ( is_a( $node, 'DOMElement' ) && $node->tagName === 'component' && $node->hasAttribute( 'type' ) ) {

			$position = $node->getAttribute( 'position' );

			if ( !array_key_exists( $position, $elements ) ) {
				$position = 'left';
			}

			$indentation = ( $position === 'right' ) ? 2 : 1;

			$this->indent( $indentation );
			$html = $this->buildNavBarElementFromDomElement( $node );
			$this->indent( -$indentation );

			$elements[ $position ][ ] = $html;

		} else {
			// TODO: Warning? Error?
		}
	}

	/**
	 * @param \DomElement $node
	 *
	 * @return string
	 */
	protected function buildNavBarElementFromDomElement( $node ) {

		$html = '';

		switch ( $node->getAttribute( 'type' ) ) {
			case 'Logo':
				$html = $this->getLogo( $node );
				break;
			case 'NavMenu':
				$html = $this->getNavMenu( $node );
				break;
			case 'PageTools':
				$html = $this->getPageTools( $node );
				break;
			case 'SearchBar':
				$html = $this->getSearchBar( $node );
				break;
			case 'PersonalTools':
//				$html = $this->getPersonalTools();
				break;
			case 'Menu':
				$html = $this->getMenu( $node );
				break;
			default:
				$html = $this->buildNavBarElementFromComponentClass( $node );
		}
		return $html;
	}

	/**
	 * @param $node
	 *
	 * @return string
	 */
	protected function buildNavBarElementFromComponentClass( $node ) {
		$component = $this->getSkin()->getComponentFactory()->getComponent( $node, $this->getIndent() );
		$html      = '<ul class="nav navbar-nav ' . $node->getAttribute( 'type' ) . '">' . $component->getHtml() . "</ul>\n";
		return $html;
	}

	/**
	 * Creates HTML code for the wiki logo in a navbar
	 *
	 * @param \DOMElement $domElement
	 *
	 * @return String
	 */
	protected function getLogo( \DOMElement $domElement = null ) {

		$logo = new Logo( $this->getSkinTemplate(), $domElement, $this->getIndent() );
		$logo->addClasses( 'navbar-brand my-0 py-0' );

		return $logo->getHtml();
	}

	/**
	 * Creates a list of navigational links usually found in the sidebar
	 *
	 * @param \DOMElement $domElement
	 *
	 * @return string
	 */
	protected function getNavMenu( \DOMElement $domElement = null ) {

		$navMenu = new NavMenu( $this->getSkinTemplate(), $domElement, $this->getIndent() );

		return  $navMenu->getHtml();

	}

	/**
	 * Create a dropdown containing the page tools (page, talk, edit, history,
	 * ...)
	 *
	 * @param \DOMElement $domElement
	 *
	 * @return string
	 */
	protected function getPageTools( \DOMElement $domElement = null ) {

		$ret = '';

		$pageTools = new PageTools( $this->getSkinTemplate(), $domElement, $this->getIndent() + 1 );

		$pageTools->setFlat( true );
		$pageTools->removeClasses( 'text-center list-inline' );
		$pageTools->addClasses( 'dropdown-menu' );

		$editLinkHtml = $this->getEditLinkHtml( $pageTools );

		$pageToolsHtml = $pageTools->getHtml();

		if ( $editLinkHtml || $pageToolsHtml ) {
			$ret =
				$this->indent() . '<!-- page tools -->' .
				$this->indent() . '<div class="navbar-tools d-flex navbar-nav" >';

			if ( $editLinkHtml !== '' ) {
				$ret .= $this->indent( 1 ) . $editLinkHtml;
			}

			if ( $pageToolsHtml !== '' ) {
				$ret .=
					$this->indent( 1 ) . '<div class="navbar-tools-tools nav-item dropdown">' .
					$this->indent( 1 ) . '<a data-toggle="dropdown" class="nav-link" href="#" title="' . $this->getSkinTemplate()->getMsg( 'specialpages-group-pagetools' )->text() . '" ><span>...</span></a>' .
					$pageToolsHtml .
					$this->indent( -1 ) . '</div>';
			}

			$ret .=
				$this->indent( -1 ) . '</div>' . "\n";
		}

		return $ret;
	}

	/**
	 * @param \DOMElement $domElement
	 *
	 * @return string
	 */
	protected function getSearchBar( \DOMElement $domElement = null ) {

		$search = new SearchBar( $this->getSkinTemplate(), $domElement, $this->getIndent() );
		$search->addClasses( 'form-inline' );

		return $search->getHtml();
	}

	/**
	 * Creates a user's personal tools and the newtalk notifier
	 *
	 * @return string
	 */
	protected function getPersonalTools() {

		$user = $this->getSkinTemplate()->getSkin()->getUser();

		if ( $user->isLoggedIn() ) {
			$toolsClass = 'navbar-userloggedin';
			$toolsLinkText = $this->getSkinTemplate()->getMsg( 'chameleon-loggedin' )->params( $user->getName() )->text();
		} else {
			$toolsClass = 'navbar-usernotloggedin';
			$toolsLinkText = $this->getSkinTemplate()->getMsg( 'chameleon-notloggedin' )->text();
		}

		$linkText = '<span class="glyphicon glyphicon-user"></span>';
		\Hooks::run('ChameleonNavbarHorizontalPersonalToolsLinkText', array( &$linkText, $this->getSkin() ) );

		// start personal tools element
		$ret =
			$this->indent() . '<!-- personal tools -->' .
			$this->indent() . '<ul class="navbar-tools navbar-nav" >' .
			$this->indent( 1 ) . '<li class="dropdown navbar-tools-tools">' .
			$this->indent( 1 ) . '<a class="dropdown-toggle ' . $toolsClass . '" href="#" data-toggle="dropdown" title="' . $toolsLinkText . '" >' . $linkText . '</a>' .
			$this->indent() . '<ul class="p-personal-tools dropdown-menu dropdown-menu-right" >';

		$this->indent( 1 );

		// add personal tools (links to user page, user talk, prefs, ...)
		foreach ( $this->getSkinTemplate()->getPersonalTools() as $key => $item ) {
			$ret .= $this->indent() . $this->getSkinTemplate()->makeListItem( $key, $item );
		}

		$ret .=
			$this->indent( -1 ) . '</ul>' .
			$this->indent( -1 ) . '</li>';

		// if the user is logged in, add the newtalk notifier
		if ( $user->isLoggedIn() ) {

			$newMessagesAlert = '';
			$newtalks = $user->getNewMessageLinks();
			$out = $this->getSkinTemplate()->getSkin()->getOutput();

			// Allow extensions to disable the new messages alert;
			// since we do not display the link text, we ignore the actual value returned in $newMessagesAlert
			if ( Hooks::run( 'GetNewMessagesAlert', array( &$newMessagesAlert, $newtalks, $user, $out ) ) ) {

				if ( count( $user->getNewMessageLinks() ) > 0 ) {
					$newtalkClass = 'navbar-newtalk-available';
					$newtalkLinkText = $this->getSkinTemplate()->getMsg( 'chameleon-newmessages' )->text();
				} else {
					$newtalkClass = 'navbar-newtalk-not-available';
					$newtalkLinkText = $this->getSkinTemplate()->getMsg( 'chameleon-nonewmessages' )->text();
				}

				$linkText = '<span class="glyphicon glyphicon-envelope"></span>';
				\Hooks::run('ChameleonNavbarHorizontalNewTalkLinkText', array( &$linkText, $this->getSkin() ) );

				$ret .= $this->indent() . '<li class="navbar-newtalk-notifier">' .
					$this->indent( 1 ) . '<a class="dropdown-toggle ' . $newtalkClass . '" title="' .
					$newtalkLinkText . '" href="' . $user->getTalkPage()->getLinkURL( 'redirect=no' ) . '">' . $linkText . '</a>' .
					$this->indent( -1 ) . '</li>';

			}

		}

		$ret .= $this->indent( -1 ) . '</ul>' . "\n";

		return $ret;
	}

	/**
	 * Creates a list of navigational links from a message key or message text
	 *
	 * @param \DOMElement $domElement
	 *
	 * @return string
	 */
	protected function getMenu( \DOMElement $domElement = null ) {

		$menu = new Menu( $this->getSkinTemplate(), $domElement, $this->getIndent() );

		return '<ul class="nav navbar-nav">' . $menu->getHtml() . "</ul>\n";

	}

	/**
	 * @param string[] $headElements
	 *
	 * @return string
	 */
	protected function buildHead( $headElements ) {

		$head =
			$this->indent( 1 ) . "<button type=\"button\" class=\"navbar-toggler navbar-toggler-right\" data-toggle=\"collapse\" data-target=\"#" . $this->getHtmlId() . "-collapse\">" .
			$this->indent( 1 ) . "<span class=\"navbar-toggler-icon\"></span>" .
			$this->indent( -1 ) . "</button>\n" .

			$this->indent( 1 ) . "<div class=\"navbar-head\">" .
			implode( '', $headElements ) . "\n" .
			$this->indent( -1 ) . "</div>\n";

		return $head;
	}

	/**
	 * @param string[] $leftSideElements
	 * @param string[] $rightSideElements
	 * @return string
	 *
	 */
	protected function buildTail( $leftSideElements = [], $rightSideElements = [] ) {

		return
			$this->indent() . '<div class="collapse navbar-collapse" id="' . $this->getHtmlId() . '-collapse">' .
			implode( '', $leftSideElements ) .
			$this->indent( 1 ) . '<div class="ml-auto"></div>' .
			implode( $rightSideElements ) .
			$this->indent( -1 ) . '<!-- navbar-right-aligned -->' .
			$this->indent() . '</div><!-- /.navbar-collapse -->';
	}

	/**
	 * @return string
	 */
	protected function buildNavBarClosingTags() {
		return
			$this->indent( -1 ) . '</nav>' . "\n";
	}

	/**
	 * @param PageTools $pageTools
	 * @param $editActionId
	 *
	 * @return string
	 */
	protected function getLinkAndRemoveFromPageToolStructure( PageTools $pageTools, $editActionId ) {

		$pageToolsStructure  = $pageTools->getPageToolsStructure();
		$editActionStructure = $pageToolsStructure[ 'views' ][ $editActionId ];


		if ( ! array_key_exists( 'class', $editActionStructure ) ) {
			$editActionStructure[ 'class' ] = '';
		}

		$editActionStructure[ 'class' ] .= ' navbar-tools-tools nav-link';
		$editActionStructure[ 'text' ] = '';

		$options = array (
			'text-wrapper' => array(
				'tag' => 'i',
				'attributes' => array('class' => 'fa fa-pencil',)
			),
		);

		$editLinkHtml = $this->getSkinTemplate()->makeLink(
			$editActionId,
			$editActionStructure,
			$options
		);

		$pageTools->setRedundant( $editActionId );

		return $editLinkHtml;
	}

	/**
	 * @param PageTools $pageTools
	 * @return string
	 */
	protected function getEditLinkHtml( PageTools $pageTools ) {

		$pageToolsStructure = $pageTools->getPageToolsStructure();

		if ( $this->isPageFormsInstalled( $pageToolsStructure ) ) {
			return $this->getLinkAndRemoveFromPageToolStructure( $pageTools, 'formedit' );
		}

		if ( $this->isSemanticFormsInstalled( $pageToolsStructure ) ) {
			return $this->getLinkAndRemoveFromPageToolStructure( $pageTools, 'form_edit' );
		}

		if ( $this->isVisualEditorInstalled( $pageToolsStructure ) ) {
			return $this->getLinkAndRemoveFromPageToolStructure( $pageTools, 've-edit' );
		}

		if ( $this->isPageEditable( $pageToolsStructure )	) {
			return $this->getLinkAndRemoveFromPageToolStructure( $pageTools, 'edit' );
		}

		return '';
	}

	/**
	 * @param $pageToolsStructure
	 * @return bool
	 */
	protected function isPageFormsInstalled( $pageToolsStructure ) {
		return
			array_key_exists( 'views', $pageToolsStructure ) &&
			array_key_exists( 'formedit', $pageToolsStructure[ 'views' ] ) && // SemanticForms 3.5+
			array_key_exists( 'sfgRenameEditTabs', $GLOBALS ) &&
			$GLOBALS[ 'sfgRenameEditTabs' ] === true;
	}

	/**
	 * @param $pageToolsStructure
	 * @return bool
	 */
	protected function isSemanticFormsInstalled( $pageToolsStructure ) {
		return
			array_key_exists( 'views', $pageToolsStructure ) &&
			array_key_exists( 'form_edit', $pageToolsStructure[ 'views' ] ) && // SemanticForms <3.5
			array_key_exists( 'sfgRenameEditTabs', $GLOBALS ) &&
			$GLOBALS[ 'sfgRenameEditTabs' ] === true;
	}

	/**
	 * @param $pageToolsStructure
	 * @return bool
	 */
	protected function isVisualEditorInstalled( $pageToolsStructure ) {
		return
			array_key_exists( 'views', $pageToolsStructure ) &&
			array_key_exists( 've-edit', $pageToolsStructure[ 'views' ] );
	}

	/**
	 * @param $pageToolsStructure
	 * @return bool
	 */
	protected function isPageEditable( $pageToolsStructure ) {
		return
			array_key_exists( 'views', $pageToolsStructure ) &&
			array_key_exists( 'edit', $pageToolsStructure[ 'views' ] );
	}

}
