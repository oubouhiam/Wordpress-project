(function() {
	// Messy, but less so than having build files in the repo.
	!function(t,e){"object"==typeof exports&&"object"==typeof module?module.exports=e(require("react")):"function"==typeof define&&define.amd?define(["react"],e):"object"==typeof exports?exports.createReactClass=e(require("react")):t.createReactClass=e(t.React)}(this,function(t){return function(t){function e(o){if(n[o])return n[o].exports;var r=n[o]={i:o,l:!1,exports:{}};return t[o].call(r.exports,r,r.exports,e),r.l=!0,r.exports}var n={};return e.m=t,e.c=n,e.i=function(t){return t},e.d=function(t,n,o){e.o(t,n)||Object.defineProperty(t,n,{configurable:!1,enumerable:!0,get:o})},e.n=function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(n,"a",n),n},e.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},e.p="",e(e.s=2)}([function(t,e,n){"use strict";function o(t){return t}function r(t,e,n){function r(t,e){var n=g.hasOwnProperty(e)?g[e]:null;_.hasOwnProperty(e)&&s("OVERRIDE_BASE"===n,"ReactClassInterface: You are attempting to override `%s` from your class specification. Ensure that your method names do not overlap with React methods.",e),t&&s("DEFINE_MANY"===n||"DEFINE_MANY_MERGED"===n,"ReactClassInterface: You are attempting to define `%s` on your component more than once. This conflict may be due to a mixin.",e)}function u(t,n){if(n){s("function"!=typeof n,"ReactClass: You're attempting to use a component class or function as a mixin. Instead, just use a regular object."),s(!e(n),"ReactClass: You're attempting to use a component as a mixin. Instead, just use a regular object.");var o=t.prototype,i=o.__reactAutoBindPairs;n.hasOwnProperty(c)&&N.mixins(t,n.mixins);for(var a in n)if(n.hasOwnProperty(a)&&a!==c){var u=n[a],p=o.hasOwnProperty(a);if(r(p,a),N.hasOwnProperty(a))N[a](t,u);else{var f=g.hasOwnProperty(a),m="function"==typeof u,h=m&&!f&&!p&&!1!==n.autobind;if(h)i.push(a,u),o[a]=u;else if(p){var y=g[a];s(f&&("DEFINE_MANY_MERGED"===y||"DEFINE_MANY"===y),"ReactClass: Unexpected spec policy %s for key %s when mixing in component specs.",y,a),"DEFINE_MANY_MERGED"===y?o[a]=l(o[a],u):"DEFINE_MANY"===y&&(o[a]=d(o[a],u))}else o[a]=u}}}else;}function p(t,e){if(e)for(var n in e){var o=e[n];if(e.hasOwnProperty(n)){var r=n in N;s(!r,'ReactClass: You are attempting to define a reserved property, `%s`, that shouldn\'t be on the "statics" key. Define it as an instance property instead; it will still be accessible on the constructor.',n);var i=n in t;s(!i,"ReactClass: You are attempting to define `%s` on your component more than once. This conflict may be due to a mixin.",n),t[n]=o}}}function f(t,e){s(t&&e&&"object"==typeof t&&"object"==typeof e,"mergeIntoWithNoDuplicateKeys(): Cannot merge non-objects.");for(var n in e)e.hasOwnProperty(n)&&(s(void 0===t[n],"mergeIntoWithNoDuplicateKeys(): Tried to merge two objects with the same key: `%s`. This conflict may be due to a mixin; in particular, this may be caused by two getInitialState() or getDefaultProps() methods returning objects with clashing keys.",n),t[n]=e[n]);return t}function l(t,e){return function(){var n=t.apply(this,arguments),o=e.apply(this,arguments);if(null==n)return o;if(null==o)return n;var r={};return f(r,n),f(r,o),r}}function d(t,e){return function(){t.apply(this,arguments),e.apply(this,arguments)}}function m(t,e){var n=e.bind(t);return n}function h(t){for(var e=t.__reactAutoBindPairs,n=0;n<e.length;n+=2){var o=e[n],r=e[n+1];t[o]=m(t,r)}}function y(t){var e=o(function(t,o,r){this.__reactAutoBindPairs.length&&h(this),this.props=t,this.context=o,this.refs=a,this.updater=r||n,this.state=null;var i=this.getInitialState?this.getInitialState():null;s("object"==typeof i&&!Array.isArray(i),"%s.getInitialState(): must return an object or null",e.displayName||"ReactCompositeComponent"),this.state=i});e.prototype=new D,e.prototype.constructor=e,e.prototype.__reactAutoBindPairs=[],E.forEach(u.bind(null,e)),u(e,b),u(e,t),u(e,v),e.getDefaultProps&&(e.defaultProps=e.getDefaultProps()),s(e.prototype.render,"createClass(...): Class specification must implement a `render` method.");for(var r in g)e.prototype[r]||(e.prototype[r]=null);return e}var E=[],g={mixins:"DEFINE_MANY",statics:"DEFINE_MANY",propTypes:"DEFINE_MANY",contextTypes:"DEFINE_MANY",childContextTypes:"DEFINE_MANY",getDefaultProps:"DEFINE_MANY_MERGED",getInitialState:"DEFINE_MANY_MERGED",getChildContext:"DEFINE_MANY_MERGED",render:"DEFINE_ONCE",componentWillMount:"DEFINE_MANY",componentDidMount:"DEFINE_MANY",componentWillReceiveProps:"DEFINE_MANY",shouldComponentUpdate:"DEFINE_ONCE",componentWillUpdate:"DEFINE_MANY",componentDidUpdate:"DEFINE_MANY",componentWillUnmount:"DEFINE_MANY",updateComponent:"OVERRIDE_BASE"},N={displayName:function(t,e){t.displayName=e},mixins:function(t,e){if(e)for(var n=0;n<e.length;n++)u(t,e[n])},childContextTypes:function(t,e){t.childContextTypes=i({},t.childContextTypes,e)},contextTypes:function(t,e){t.contextTypes=i({},t.contextTypes,e)},getDefaultProps:function(t,e){t.getDefaultProps?t.getDefaultProps=l(t.getDefaultProps,e):t.getDefaultProps=e},propTypes:function(t,e){t.propTypes=i({},t.propTypes,e)},statics:function(t,e){p(t,e)},autobind:function(){}},b={componentDidMount:function(){this.__isMounted=!0}},v={componentWillUnmount:function(){this.__isMounted=!1}},_={replaceState:function(t,e){this.updater.enqueueReplaceState(this,t,e)},isMounted:function(){return!!this.__isMounted}},D=function(){};return i(D.prototype,t.prototype,_),y}var i=n(5),a=n(3),s=n(4),c="mixins";t.exports=r},function(e,n){e.exports=t},function(t,e,n){"use strict";var o=n(1),r=n(0);if(void 0===o)throw Error("create-react-class could not find the React object. If you are using script tags, make sure that React is being loaded before create-react-class.");var i=(new o.Component).updater;t.exports=r(o.Component,o.isValidElement,i)},function(t,e,n){"use strict";var o={};t.exports=o},function(t,e,n){"use strict";function o(t,e,n,o,i,a,s,c){if(r(e),!t){var u;if(void 0===e)u=new Error("Minified exception occurred; use the non-minified dev environment for the full error message and additional helpful warnings.");else{var p=[n,o,i,a,s,c],f=0;u=new Error(e.replace(/%s/g,function(){return p[f++]})),u.name="Invariant Violation"}throw u.framesToPop=1,u}}var r=function(t){};t.exports=o},function(t,e,n){"use strict";function o(t){if(null===t||void 0===t)throw new TypeError("Object.assign cannot be called with null or undefined");return Object(t)}var r=Object.getOwnPropertySymbols,i=Object.prototype.hasOwnProperty,a=Object.prototype.propertyIsEnumerable;t.exports=function(){try{if(!Object.assign)return!1;var t=new String("abc");if(t[5]="de","5"===Object.getOwnPropertyNames(t)[0])return!1;for(var e={},n=0;n<10;n++)e["_"+String.fromCharCode(n)]=n;if("0123456789"!==Object.getOwnPropertyNames(e).map(function(t){return e[t]}).join(""))return!1;var o={};return"abcdefghijklmnopqrst".split("").forEach(function(t){o[t]=t}),"abcdefghijklmnopqrst"===Object.keys(Object.assign({},o)).join("")}catch(t){return!1}}()?Object.assign:function(t,e){for(var n,s,c=o(t),u=1;u<arguments.length;u++){n=Object(arguments[u]);for(var p in n)i.call(n,p)&&(c[p]=n[p]);if(r){s=r(n);for(var f=0;f<s.length;f++)a.call(n,s[f])&&(c[s[f]]=n[s[f]])}}return c}}])});

	var registerBlockType = wp.blocks.registerBlockType,
		__ = wp.i18n.__,
		el = wp.element.createElement,
		Fragment = wp.element.Fragment,
		withSelect = wp.data.withSelect,

		ServerSideRender = wp.serverSideRender,
		SelectControl = wp.components.SelectControl,
		ToggleControl = wp.components.ToggleControl,
		RangeControl = wp.components.RangeControl,
		Disabled = wp.components.Disabled,
		PanelBody = wp.components.PanelBody,
		BaseControl = wp.components.BaseControl,
		ColorPicker = wp.components.ColorPicker,

		InspectorControls = wp.blockEditor.InspectorControls;

	registerBlockType('ivole/cusrev-trustbadge', {
		title: __( 'Trust Badge', 'customer-reviews-woocommerce'),
		icon: 'star-filled',
		description: __( 'Block showing a trust badge generated by CR plugin.', 'customer-reviews-woocommerce' ),
		category: 'widgets',

		edit: withSelect( function( select ) {
			return {
				settings: select( 'core/editor' ).getEditorSettings().cusrev
			};
		} )( function( props ) {
			if ( ! props.settings.reviews_verified ) {
				return el(
					'div',
					{},
					el(
						'em',
						{},
						__( 'You need to enable Trust Badges in Reviews -> Settings -> Trust Badges', 'customer-reviews-woocommerce' )
					)
				);
			}

			return el(
				Fragment,
				{},
				[
					el(
						InspectorControls,
						{},
						[
							el(
								PanelBody,
								{
									title: __( 'Trust Badge Settings', 'customer-reviews-woocommerce' )
								},
								[
									el(
										SelectControl,
										{
											label: __( 'Badge Size', 'customer-reviews-woocommerce' ),
											value: props.attributes.badge_size,
											options: [
												{
													label: __( 'Small', 'customer-reviews-woocommerce' ),
													value: 'small'
												},
												{
													label: __( 'Wide', 'customer-reviews-woocommerce' ),
													value: 'wide'
												}
											],
											onChange: function( size ) {
												props.setAttributes( {
													badge_size: size
												} );
											}
										}
									),
									el(
										SelectControl,
										{
											label: __( 'Badge Style', 'customer-reviews-woocommerce' ),
											value: props.attributes.badge_style,
											options: [
												{
													label: __( 'Light', 'customer-reviews-woocommerce' ),
													value: 'light'
												},
												{
													label: __( 'Dark', 'customer-reviews-woocommerce' ),
													value: 'dark'
												}
											],
											onChange: function( style ) {
												props.setAttributes( {
													badge_style: style
												} );
											}
										}
									),
									el(
										ToggleControl,
										{
											label: __( 'Show Store Rating', 'customer-reviews-woocommerce' ),
											checked: props.attributes.store_rating,
											help: props.attributes.store_rating ? __( 'Include store rating', 'customer-reviews-woocommerce' ): __( 'Exclude store rating', 'customer-reviews-woocommerce' ),
											onChange: function() {
												props.setAttributes( {
													store_rating: ! props.attributes.store_rating
												} );
											}
										}
									),
									el(
										ToggleControl,
										{
											label: __( 'Show Border', 'customer-reviews-woocommerce' ),
											checked: props.attributes.badge_border,
											help: props.attributes.badge_border ? __( 'Display badge border', 'customer-reviews-woocommerce' ): __( 'No badge border', 'customer-reviews-woocommerce' ),
											onChange: function() {
												props.setAttributes( {
													badge_border: ! props.attributes.badge_border
												} );
											}
										}
									),
									el(
										BaseControl,
										{
											id: 'cr_trustbadge_colorpicker',
											className: 'cr-trustbadge-colorpicker',
											label: __( 'Background color', 'customer-reviews-woocommerce' ),
											help: __( 'Set a custom background color for the badge', 'customer-reviews-woocommerce' ),
											children: el(
												ColorPicker,
												{
													id: 'cr_trustbadge_colorpicker',
													color: props.attributes.badge_color,
													disableAlpha: true,
													onChangeComplete: function( color ) {
														props.setAttributes( {
															badge_color: color.hex
														} );
													}
												}
											)
										}
									)
								]
							)
						]
					),
					el(
						Disabled,
						{},
						[
							el(
								ServerSideRender,
								{
									block: 'ivole/cusrev-trustbadge',
									attributes:  props.attributes
								}
							)
						]
					)
				]
			);
		} ),

		save: function() {
			return null;
		}
	});

	var _n = wp.i18n._n,
		sprintf = wp.i18n.sprintf,
		addQueryArgs = wp.url.addQueryArgs,
		apiFetch = wp.apiFetch,
		withInstanceId = wp.compose.withInstanceId,
		BaseControl = wp.components.BaseControl,
		ColorPicker = wp.components.ColorPicker,
		find = lodash.find,
		assign = lodash.assign,
		SearchListControl = wc.components.SearchListControl,
		SearchListItem = wc.components.SearchListItem;

	var ProductCategoryControl = createReactClass({
		static: {
			defaultProps: {
				operator: 'any'
			}
		},

		getInitialState: function() {
			return {
				list: [],
				loading: true,
			};
		},

		componentDidMount: function() {
			fetch(ajaxurl + '?action=ivole_fetch_product_categories')
			.then( function( response ) {
				return response.json();
			})
			.then( (function( list ) {;
				this.setState( { list: list, loading: false } );
			}).bind(this) )
			.catch( (function() {
				this.setState( { list: [], loading: false } );
			}).bind(this) );
		},

		renderItem: function( args ) {
			var item = args.item,
				search = args.search,
				depth = args.depth;

			var classes = [
				'woocommerce-product-categories__item',
			];

			if ( search.length ) {
				classes.push( 'is-searching' );
			}

			if ( depth === 0 && item.parent !== 0 ) {
				classes.push( 'is-skip-level' );
			}

			var accessibleName = ! item.breadcrumbs.length ?
				item.name :
				item.breadcrumbs.join( ', ' ) + ', ' + item.name;

			var searchListItemProps = assign( args, {
				className: classes.join( ' ' ),
				showCount: true,
				'aria-label': sprintf(
					_n(
						'%s, has %d product',
						'%s, has %d products',
						item.count,
						'customer-reviews-woocommerce'
					),
					accessibleName,
					item.count
				)
			} );

			return el( SearchListItem, searchListItemProps );
		},

		render: function() {
			var list = this.state.list,
				loading = this.state.loading,
				onChange = this.props.onChange,
				onOperatorChange = this.props.onOperatorChange,
				operator = this.props.operator,
				selected = this.props.selected;

			var messages = {
				clear: __( 'Clear all product categories', 'customer-reviews-woocommerce' ),
				list: __( 'Product Categories', 'customer-reviews-woocommerce' ),
				noItems: __(
					"Your store doesn't have any product categories.",
					'customer-reviews-woocommerce'
				),
				search: __(
					'Search for product categories',
					'customer-reviews-woocommerce'
				),
				selected: function( n ) {
					return sprintf(
						_n(
							'%d category selected',
							'%d categories selected',
							n,
							'customer-reviews-woocommerce'
						),
						n
					)
				},
				updated: __(
					'Category search results updated.',
					'customer-reviews-woocommerce'
				),
			};

			var comps = [
				el( SearchListControl, {
					className: "woocommerce-product-categories",
					list: list,
					isLoading: loading,
					selected: selected.map( function( id ) {
						return find( list, { id: id } );
					} ).filter( Boolean ),
					onChange: onChange,
					renderItem: this.renderItem,
					messages: messages,
					isHierarchical: true
				} )
			];

			if ( !! onOperatorChange ) {
				comps.push(
					el( 'div', {
							className: selected.length < 2 ? 'screen-reader-text' : ''
				 		},
						[
							el( SelectControl, {
								className: "woocommerce-product-categories__operator",
								label: __( 'Display products matching', 'customer-reviews-woocommerce' ),
								help: __( 'Pick at least two categories to use this setting.', 'customer-reviews-woocommerce' ),
								value: operator,
								onChange: onOperatorChange,
								options: [
									{
										label: __( 'Any selected categories', 'customer-reviews-woocommerce' ),
										value: 'any',
									},
									{
										label: __( 'All selected categories', 'customer-reviews-woocommerce' ),
										value: 'all',
									},
								]
							} )
						]
					)
				);
			}

			return el(
				Fragment,
				{},
				comps
			);
		}
	});

	var ProductsControl = createReactClass( {
		getInitialState: function() {
			return {
				list: [],
				loading: true,
			};
		},

		componentDidMount: function() {
			fetch(ajaxurl + '?action=ivole_fetch_products')
			.then( function( response ) {
				return response.json();
			})
			.then( (function( list ) {
				this.setState( { list: list, loading: false } );
			}).bind(this) )
			.catch( (function() {
				this.setState( { list: [], loading: false } );
			}).bind(this) );
		},

		render: function() {
			var list = this.state.list,
				loading = this.state.loading,
				onChange = this.props.onChange,
				selected = this.props.selected;

			var messages = {
				clear: __( 'Clear all products', 'woo-gutenberg-products-block' ),
				list: __( 'Products', 'woo-gutenberg-products-block' ),
				noItems: __(
					"Your store doesn't have any products.",
					'woo-gutenberg-products-block'
				),
				search: __(
					'Search for products to display',
					'woo-gutenberg-products-block'
				),
				selected: function( n ) {
					return sprintf(
						_n(
							'%d product selected',
							'%d products selected',
							n,
							'woo-gutenberg-products-block'
						),
						n
					)
				},
				updated: __(
					'Product search results updated.',
					'woo-gutenberg-products-block'
				),
			};

			return el( Fragment,
				{},
				[
					el( SearchListControl,
						{
							className: "woocommerce-products",
							list: list,
							isLoading: loading,
							selected: selected.map( function( id ) {
								return find( list, { id: id } );
							} ).filter( Boolean ),
							onChange: onChange,
							messages: messages
						}
					)
				]
			);
		}
	} );

	var pickerWithLabel = function( props ) {
		return el(
			BaseControl,
			{
				id: props.instanceId,
				label: props.label || ''
			},
			[
				el( ColorPicker, props )
			]
		);
	}

	registerBlockType('ivole/cusrev-reviews-grid', {
		title: __( 'Reviews Grid', 'customer-reviews-woocommerce'),
		icon: 'screenoptions',
		description: __( 'Block showing a grid with WooCommerce product reviews.', 'customer-reviews-woocommerce' ),
		category: 'widgets',

		edit: withSelect( function( select ) {
			return {
				settings: select( 'core/editor' ).getEditorSettings().cusrev
			};
		} )( function( props ) {
			if ( ! props.settings.reviews_shortcodes ) {
				return el(
					'div',
					{},
					el(
						'em',
						{},
						__( 'You need to enable "Reviews Shortcodes" checkbox in Reviews > Settings > Review Extensions', 'customer-reviews-woocommerce' )
					)
				);
			}

			return el(
				Fragment,
				{},
				[
					el(
						InspectorControls,
						{},
						[
							el(
								PanelBody,
								{
									title: __( 'Review Grid Settings', 'customer-reviews-woocommerce' )
								},
								[
									el(
										RangeControl,
										{
											label: __( 'Number of Reviews', 'customer-reviews-woocommerce' ),
											value: props.attributes.count,
											min: 1,
											max: 6,
											onChange: function( count ) {
												props.setAttributes( {
													count: count
												} );
											}
										}
									),
									el(
										RangeControl,
										{
											label: __( 'Number of Shop Reviews', 'customer-reviews-woocommerce' ),
											value: props.attributes.count_shop_reviews,
											min: 0,
											max: 3,
											onChange: function( count_shop_reviews ) {
												props.setAttributes( {
													count_shop_reviews: count_shop_reviews
												} );
											}
										}
									),
									el(
										ToggleControl,
										{
											label: __( 'Show Products', 'customer-reviews-woocommerce' ),
											checked: props.attributes.show_products,
											onChange: function() {
												props.setAttributes( {
													show_products: ! props.attributes.show_products
												} );
											}
										}
									),
									el(
										ToggleControl,
										{
											label: __( 'Product Links', 'customer-reviews-woocommerce' ),
											checked: props.attributes.product_links,
											onChange: function() {
												props.setAttributes( {
													product_links: ! props.attributes.product_links
												} );
											}
										}
									),
									el(
										ToggleControl,
										{
											label: __( 'Shop Reviews', 'customer-reviews-woocommerce' ),
											checked: props.attributes.shop_reviews,
											onChange: function() {
												props.setAttributes( {
													shop_reviews: ! props.attributes.shop_reviews
												} );
											}
										}
									),
									el(
										ToggleControl,
										{
											label: __( 'Inactive Products', 'customer-reviews-woocommerce' ),
											checked: props.attributes.inactive_products,
											onChange: function() {
												props.setAttributes( {
													inactive_products: ! props.attributes.inactive_products
												} );
											}
										}
									),
									el(
										SelectControl,
										{
											label: __( 'Sort By', 'customer-reviews-woocommerce' ),
											value: props.attributes.sort_by,
											options: [
												{
													label: __( 'Date', 'customer-reviews-woocommerce' ),
													value: 'date'
												},
												{
													label: __( 'Rating', 'customer-reviews-woocommerce' ),
													value: 'rating'
												}
											],
											onChange: function( sort_by ) {
												props.setAttributes( {
													sort_by: sort_by
												} );
											}
										}
									),
									el(
										SelectControl,
										{
											label: __( 'Sort Order', 'customer-reviews-woocommerce' ),
											value: props.attributes.sort,
											options: [
												{
													label: __( 'Ascending', 'customer-reviews-woocommerce' ),
													value: 'ASC'
												},
												{
													label: __( 'Descending', 'customer-reviews-woocommerce' ),
													value: 'DESC'
												},
												{
													label: __( 'Random', 'customer-reviews-woocommerce' ),
													value: 'RAND'
												}
											],
											onChange: function( sort_order ) {
												props.setAttributes( {
													sort: sort_order
												} );
											}
										}
									),
								]
							),
							el(
								PanelBody,
								{
									title: __( 'Product Categories', 'customer-reviews-woocommerce' ),
									initialOpen: false
								},
								[
									el(
										'div',
										{},
										__( 'Select which product categories to show reviews for.', 'customer-reviews-woocommerce' )
									),
									el(
										ProductCategoryControl,
										{
											selected: props.attributes.categories,
											onChange: function( value ) {
												value = value || [];
												var ids = value.map( function( category ) {
													return category.id;
												} );

												props.setAttributes( {
													categories: ids
												} );
											}
										}
									)
								]
							),
							el(
								PanelBody,
								{
									title: __( 'Products', 'customer-reviews-woocommerce' ),
									initialOpen: false
								},
								[
									el(
										'div',
										{},
										__( 'Select which products to show reviews for.', 'customer-reviews-woocommerce' )
									),
									el(
										ProductsControl,
										{
											selected: props.attributes.products,
											onChange: function( value ) {
												value = value || [];
												var ids = value.map( function( product ) {
													return product.id;
												} );

												props.setAttributes( {
													products: ids
												} );
											}
										}
									)
								]
							),
							el(
								PanelBody,
								{
									title: __( 'Colors', 'customer-reviews-woocommerce' ),
									initialOpen: false
								},
								[
									el(
										withInstanceId( pickerWithLabel ),
										{
											color: props.attributes.color_ex_brdr,
											label: __( 'External Border', 'customer-reviews-woocommerce' ),
											disableAlpha: true,
											onChangeComplete: function( color ) {
												props.setAttributes( {
													color_ex_brdr: color.hex
												} );
											}
										}
									),
									el(
										withInstanceId( pickerWithLabel ),
										{
											color: props.attributes.color_brdr,
											label: __( 'Review Card Border', 'customer-reviews-woocommerce' ),
											disableAlpha: true,
											onChangeComplete: function( color ) {
												props.setAttributes( {
													color_brdr: color.hex
												} );
											}
										}
									),
									el(
										withInstanceId( pickerWithLabel ),
										{
											color: props.attributes.color_ex_bcrd,
											label: __( 'Background', 'customer-reviews-woocommerce' ),
											disableAlpha: true,
											onChangeComplete: function( color ) {
												props.setAttributes( {
													color_ex_bcrd: color.hex
												} );
											}
										}
									),
									el(
										withInstanceId( pickerWithLabel ),
										{
											color: props.attributes.color_bcrd,
											label: __( 'Review Card Background', 'customer-reviews-woocommerce' ),
											disableAlpha: true,
											onChangeComplete: function( color ) {
												props.setAttributes( {
													color_bcrd: color.hex
												} );
											}
										}
									),
									el(
										withInstanceId( pickerWithLabel ),
										{
											color: props.attributes.color_pr_bcrd,
											label: __( 'Product Area Background', 'customer-reviews-woocommerce' ),
											disableAlpha: true,
											onChangeComplete: function( color ) {
												props.setAttributes( {
													color_pr_bcrd: color.hex
												} );
											}
										}
									)
								]
							)
						]
					),
					el(
						Disabled,
						{},
						[
							el(
								ServerSideRender,
								{
									block: 'ivole/cusrev-reviews-grid',
									attributes:  props.attributes
								}
							)
						]
					)
				]
			);
		} ),

		save: function() {
			return null;
		}
	});
})();
