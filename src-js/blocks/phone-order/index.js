/**
 * Phone Order Block - Editor
 *
 * WordPress 6.9+ Gutenberg block with modern React patterns
 *
 * @package OpenWPClub\PhoneOrder
 */

import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps, BlockControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, TextControl, SelectControl, ToolbarGroup, ToolbarButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import ServerSideRender from '@wordpress/server-side-render';

// Simple phone icon as SVG
const phoneIcon = (
	<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
		<path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" />
	</svg>
);

import './editor.scss';
import './style.scss';
import metadata from './block.json';

/**
 * Edit component
 */
function Edit({ attributes, setAttributes }) {
	const { productId, showTitle, showDescription, customTitle, customButtonText } = attributes;

	// Get WooCommerce products
	const products = useSelect((select) => {
		return select('core').getEntityRecords('postType', 'product', {
			per_page: 100,
			status: 'publish',
		});
	}, []);

	const blockProps = useBlockProps({
		className: 'wp-block-openwpclub-phone-order',
	});

	const productOptions = products
		? [
				{ label: __('Select a product...', 'woocommerce-phone-order'), value: 0 },
				...products.map((product) => ({
					label: product.title.rendered,
					value: product.id,
				})),
		  ]
		: [{ label: __('Loading...', 'woocommerce-phone-order'), value: 0 }];

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Phone Order Settings', 'woocommerce-phone-order')} initialOpen={true}>
					<SelectControl
						label={__('Product', 'woocommerce-phone-order')}
						value={productId}
						options={productOptions}
						onChange={(value) => setAttributes({ productId: parseInt(value) })}
						help={__('Select which product this form should order', 'woocommerce-phone-order')}
					/>

					<ToggleControl
						label={__('Show Title', 'woocommerce-phone-order')}
						checked={showTitle}
						onChange={(value) => setAttributes({ showTitle: value })}
					/>

					{showTitle && (
						<TextControl
							label={__('Custom Title', 'woocommerce-phone-order')}
							value={customTitle}
							onChange={(value) => setAttributes({ customTitle: value })}
							placeholder={__('Order by Phone', 'woocommerce-phone-order')}
							help={__('Leave empty to use default', 'woocommerce-phone-order')}
						/>
					)}

					<ToggleControl
						label={__('Show Description', 'woocommerce-phone-order')}
						checked={showDescription}
						onChange={(value) => setAttributes({ showDescription: value })}
					/>

					<TextControl
						label={__('Custom Button Text', 'woocommerce-phone-order')}
						value={customButtonText}
						onChange={(value) => setAttributes({ customButtonText: value })}
						placeholder={__('Order Now', 'woocommerce-phone-order')}
						help={__('Leave empty to use default', 'woocommerce-phone-order')}
					/>
				</PanelBody>
			</InspectorControls>

			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						icon={phoneIcon}
						label={__('Phone Order Settings', 'woocommerce-phone-order')}
						onClick={() => {}}
					/>
				</ToolbarGroup>
			</BlockControls>

			<div {...blockProps}>
				{productId === 0 ? (
					<div className="phone-order-placeholder">
						{phoneIcon}
						<p>{__('Select a product in the block settings to display the phone order form.', 'woocommerce-phone-order')}</p>
					</div>
				) : (
					<ServerSideRender block="openwpclub/phone-order" attributes={attributes} />
				)}
			</div>
		</>
	);
}

/**
 * Register block
 */
registerBlockType(metadata.name, {
	edit: Edit,
	icon: phoneIcon,
});
