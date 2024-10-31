import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

const Option = (
	{
		index,
		option,
		onChange,
		onNewOption,
		onDelete,
		attributes,
	}
) => {
	const { optionType } = attributes;

	const handleChange = ( value ) => {
		onChange( index, value );
	}

	const handleKeyDown = ( event ) => {
		if ( event.key === 'Enter' ) {
			event.preventDefault();
			onNewOption( index + 1 );
		}
	}

	const handleDelete = () => onDelete( index );

	return (
		<div className='option'>
			<div className='option-selector'>
				{ optionType === 'multi-check' && <input type='checkbox' name='poll-option[]' className='checkbox'/> }
				{ optionType === 'radio' && <input type='radio' name='poll-option' className='radio'/> }
			</div>
			<RichText
				tagName='label'
				className='option-label'
				placeholder={ __( 'Enter option', 'poll-creator' ) }
				multiline={ false }
				preserveWhiteSpace={ false }
				onChange={ handleChange }
				onKeyDown={ handleKeyDown}
				onRemove={ handleDelete }
				onReplace={ undefined }
				value={option.option}
				allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
				withoutInteractiveFormatting
				disableLineBreaks={ true }
			/>
		</div>
	);
};

export default Option;