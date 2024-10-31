import { useRef, useEffect } from "@wordpress/element";
import Option from './option.js';
import { nanoid } from 'nanoid';

const setCaretPosition = ( el ) => {
	 // Focus on the div
	 el.focus();

	 // Create a range
	 const range = document.createRange();

	 // Select the content of the div
	 range.selectNodeContents(el);

	 // Collapse the range to the end
	 range.collapse(false);

	 // Clear existing selections
	 const sel = window.getSelection();
	 sel.removeAllRanges();

	 // Add the new range
	 sel.addRange(range);
}

const shiftAnswerFocus = ( wrapper, index ) => {
	// Set the cursor at the end of the text.
	const element = wrapper.querySelectorAll( '[role=textbox]' )[ index ];
	element && setCaretPosition( element );
};

const OptionsWrapper = ( { attributes, setAttributes } ) => {
	// Set a reference to the poll options wrapper.
	const optionsWrapperRef = useRef();
	const { options } = attributes;

	useEffect( () => {
		if ( options.length === 0 ) {
			// Push a new options with nanoID.
			setAttributes( {
				options: [
					{
						option_id: nanoid(),
						type: 'text',
						option: '',
					},
				],
			} );
		}
	}, [] );


	const handleChangeOption = ( index, value ) => {
		// Update the options array.
		setAttributes( {
			options: options.map( ( option, i ) => {
				if ( index === i ) {
					option.option = value;
				}
				return option;
			} ),
		} );

		// Create a new option object once the last option is filled.
		if ( index === options.length - 1 ) {
			setAttributes( {
				options: [
					...options,
					{
						option_id: nanoid(),
						type: 'text',
						option: '',
					},
				],
			} );
		}
	}

	const handleNewOption = ( insertAt ) => {
		// Insert a new option object in the options array.
		if ( insertAt <= options.length ) {
			setAttributes( {
				options: [
					...options.slice( 0, insertAt ),
					{
						option_id: nanoid(),
						type: 'text',
						option: '',
					},
					...options.slice( insertAt, options.length ),
				],
			} );

			shiftAnswerFocus( optionsWrapperRef.current, Math.min( insertAt, options.length ) );
		}
	}

	const handleOnDelete = ( index ) => {
		shiftAnswerFocus( optionsWrapperRef.current, Math.max( index - 1, 0 ) );
		// Delete an option object from the options array.
		if ( options.length > 1 ) {
			setAttributes( {
				options: options.filter( ( option, i ) => {
					return i !== index;
				} ),
			} );
		}
	}

	return (
		<div className='poll-options-wrapper' ref={optionsWrapperRef}>
			{
				options.length && options.map( ( option, index ) => {
					return <Option
					    attributes={ attributes }
						key={ index }
						parentRef={ optionsWrapperRef }
						index={ index }
						option={ option }
						onChange={ handleChangeOption }
						onNewOption={ handleNewOption }
						onDelete={ handleOnDelete }
					/>
				})
			}
		</div>
	);
}

export default OptionsWrapper;