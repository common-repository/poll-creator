/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress Dependencies.
 */
import { useBlockProps, RichText } from '@wordpress/block-editor';

/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#save
 *
 * @return {WPElement} Element to render.
 */
export default function Save( { attributes } ) {
	// Filter each attribute.options and remove empty option.option values.
	const pollOptions = attributes.options.filter( ( option ) => option.option !== '' );

	return (
        <div {...useBlockProps.save()}>
            <div className='pollify-poll-form'>
				<RichText.Content tagName='h4' value={ attributes.title } />

                <RichText.Content tagName='p' value={ attributes.description } />

				<form action="post" class="poll-form">
					<div className="poll-options-wrapper">
						{ pollOptions.map( ( option, index ) => (
							<div key={ index } class="option">
								<div className="option-selector">
									{ attributes.optionType === 'radio' && (
										<input
											type="radio"
											id={ `option-${ index }` }
											name="poll-option"
											value={ option.option }
										/>
									) }
									{ attributes.optionType === 'checkbox' && (
										<input
											type="checkbox"
											id={ `option-${ index }` }
											name="poll-option"
											value={ option.option }
										/>

									) }
								</div>
								<label className='option-label' htmlFor={ `option-${ index }` }>
									{ option.option }
								</label>
							</div>
						) ) }
					</div>
					<div className={ classnames( 'wp-block-button poll-block-button', {
						[ `align-${ attributes.submitButtonAlign }` ]: attributes.submitButtonAlign,
						} ) }>
						<div className={ classnames( 'submit-button-wrapper', {
						[ `has-custom-width wp-block-button-width-${ attributes.submitButtonWidth }` ]: attributes.submitButtonWidth,
						} ) }>
							<RichText.Content className="wp-block-button__link submit-button" value={ attributes.submitButtonLabel } />
						</div>
					</div>
				</form>
			</div>
        </div>
    );
}
