( function( $ ) {

	/*
	 * String.format was deprecated in GF 2.7.1 and will be removed in GF 2.8 in favor of String.prototype.gformFormat.
	 *
	 * As we support older versions of GF, we need to add String.prototype.gformFormat if it doesn't exist.
	 */
	if (!String.prototype.gformFormat) {
		String.prototype.gformFormat = function () {
			var args = arguments;
			return this.replace(/{(\d+)}/g, function (match, number) {
				return typeof args[number] !== 'undefined' ? args[number] : match;
			});
		};
	}

	var gpGetDateFieldFormat = function ($field) {
		if ( $field.prop('type') === 'date' ) {
			return {
				sep: '-',
				format: 'ymd'
			}
		}

		var formatClass = getDateFormatByClass( $field.parents( '.gfield' ).attr( 'class' ) );

		if ( formatClass ) {
			var formatBits = formatClass.split( '_' );
			var sepTypes = {dot: '.', slash: '/', dash: '-'};
			var separator = formatBits[1] ? sepTypes[formatBits[1]] : 'slash';

			return {
				sep: sepTypes[separator] ? sepTypes[separator] : separator,
				format: formatBits[0]
			}
		}

		// Default
		return {
			sep: '/',
			format: 'mdy',
		};
	}

	var gpGetDateFieldDate = function ($field, fieldFormat) {

		if (!fieldFormat) {
			fieldFormat = gpGetDateFieldFormat($field);
		}

		var sep = fieldFormat.sep;
		var format = fieldFormat.format;

		if ( $field.prop('type') === 'date' ) {
			var valueSplit = $field.val().split(sep);

			return {
				month: valueSplit[format.indexOf('m')] - 1,
				day: valueSplit[format.indexOf('d')],
				year: valueSplit[format.indexOf('y')],
			}
		}

		var origValue, month, day, year;

		/* Dropdown/Fields */
		if ($field.find( '.gfield_date_dropdown_month' ).length || $field.find( '.gfield_date_month' ).length) {

			var $inputs = $field.find( '.ginput_container_date input, .ginput_container_date select' );

			month = $inputs.eq( format.indexOf( 'm' ) ).val() - 1;
			day   = $inputs.eq( format.indexOf( 'd' ) ).val();
			year  = $inputs.eq( format.indexOf( 'y' ) ).val();

			/* Datepicker */
		} else if ( $field.is( '.datepicker, .has-inline-datepicker, .gpro-disabled-datepicker' ) ) {

			origValue = $field.val();
			dateBits  = origValue.split( sep );
			month     = dateBits[ format.indexOf( 'm' ) ] - 1;
			day       = dateBits[ format.indexOf( 'd' ) ];
			year      = dateBits[ format.indexOf( 'y' ) ];

		}

		return {
			month: month,
			day: day,
			year: year
		}

	};

	var gpGetDateFieldDateObject = function ($field) {

		var datebits = gpGetDateFieldDate( $field );

		var day   = datebits.day;
		var month = datebits.month;
		var year  = datebits.year;

		if ( ! day || ( ! month && month !== '0' && month !== 0 ) || ! year ) {
			return false;
		}

		date = new Date( year, month, day, 0, 0, 0, 0 );

		return date;

	};

	var gpGetDateFieldTimestamp = function ($field) {

		var date = gpGetDateFieldDateObject( $field );

		if ( ! date) {
			return false;
		}

		var tzOffset = date.getTimezoneOffset() * 60; // convert to seconds

		if (isNaN( date.getTime() )) {
			return false;
		}

		return ( date.getTime() / 1000 ) - tzOffset;

	};

	var isNegativeUnixTimestamp = function ($value) {

		if (typeof $value !== 'string') {
			return false;
		}
	
		// Try converting the string to an integer
		var timestamp = parseInt( $value, 10 );
	
		// Check if the conversion was successful and if the timestamp is negative
		if (isNaN( timestamp )) {
			return false;
		}
	
		return timestamp < 0;
	};	

	gform.addFilter( 'gform_is_value_match', function( isMatch, formId, rule ) {

		var fieldValue   = false,
			ruleValue    = rule.value,
			$sourceInput = jQuery( '#input_' + formId + '_' + rule.fieldId ),
			isTimeField  = $sourceInput.parents( '.gfield' ).hasClass( 'gcldf-field-time' );

		if ( rule.fieldId == '_gpcld_current_time' || isTimeField ) {

			var currentDate = new Date(),
				$timeInputs = $sourceInput.parents( '.gfield' ).find( 'input, select' ),
				timeString  = '{0}:{1}{2}'.gformFormat( $timeInputs.eq( 0 ).val(), $timeInputs.eq( 1 ).val(), $timeInputs.eq( 2 ).val() );

			var datetime    = isTimeField ? getDateFromTimeString( timeString ) : currentDate,
				timestamp   = datetime.getTime() / 1000,
				compareDate = getDateFromTimeString( rule.value );

			/**
			 * The `gpcldBaseDate` property is only set via a custom snippet that attempts to intelligently modify time-based
			 * rules such that the base data used to calculate the current time is adjusted based on the conditional logic
			 * rules that precede them.
			 */
			if ( rule.gpcldBaseDate ) {
				if ( $.isNumeric( rule.gpcldBaseDate ) ) {
					compareDate.setDate( compareDate.getDate() + rule.gpcldBaseDate );
				} else {
					var baseDate = new Date( rule.gpcldBaseDate );
					compareDate.setFullYear( baseDate.getFullYear() );
					compareDate.setMonth( baseDate.getMonth() );
					compareDate.setDate( baseDate.getDate() );
				}
			}

			var utcTzOffset    = datetime.getTimezoneOffset() * 60, // convert to seconds
				utcTzTimestamp = ( datetime.getTime() / 1000 ) + utcTzOffset; // .getTimezoneOffset() returns a POSITIVE number if behind UTC and negative if ahead.

			/**
			 * Users can use the `gpcld_use_visitor_timezone` PHP filter to force the use of the visitor's timezone
			 * for a given form or all forms.
			 */
			if (
				typeof window['GPCLD_USE_VISITOR_TIMEZONE_' + formId] === 'undefined'
				|| ! window['GPCLD_USE_VISITOR_TIMEZONE_' + formId]
			) {
				timestamp = utcTzTimestamp + (window.GPConditionalLogicDates.serverTzOffsetHours * 3600);
			}

			ruleValue = ( compareDate.getTime() / 1000 );

		} else {

			if ( ! $sourceInput.parents( '.gfield' ).hasClass( 'gcldf-field' ) ) {
				return isMatch;
			}

			timestamp = gpGetDateFieldTimestamp( $sourceInput );

		}

		fieldValue = timestamp;

		/*
		 * Allows use of asterisks (wildcards) when specifying dates in rule values. Will be replaced with
		 * the corresponding value from the compared date value.
		 *
		 * Selected Date: 9/20/2016
		 * Wildcard Rule: 9/15/*
		 * Replaced Rule: 9/15/2016
		 */
		if ( String( ruleValue ).indexOf( '*' ) != -1 ) {

			var ruleBits = ruleValue.split( '/' );
			var date     = gpGetDateFieldDate( $sourceInput );
			var dateBits = [ date.month + 1, date.day, date.year ];

			for ( var i = 0; i < ruleBits.length; i++ ) {
				if ( ruleBits[i] == '*' ) {
					ruleBits[i] = dateBits[i];
				}
			}

			// ruleBits = [ month, day, year ]
			var date     = new Date( ruleBits[2], ruleBits[0] - 1, ruleBits[1], 0, 0, 0, 0 ),
				tzOffset = date.getTimezoneOffset() * 60; // convert to seconds

			ruleValue = ( date.getTime() / 1000 ) - tzOffset;

		}

		if ( fieldValue ) {

			var tag = rule.value.match( /{(.+?)}/ );

			if ( tag ) {

				var tag  = tag[1].toLowerCase(),
					days = [ 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday' ],
					date = gpGetDateFieldDateObject( $sourceInput );

				switch ( tag ) {
					case 'monday':
					case 'tuesday':
					case 'wednesday':
					case 'thursday':
					case 'friday':
					case 'saturday':
					case 'sunday':
						fieldValue = ! date || isNaN( date.getDay() ) ? '' : date.getDay();
						ruleValue  = days.indexOf( tag );
						break;
				}

			}

		}

		// Modify fieldValue or ruleValue so that rule always returns false until a date is selected.
		/** This action is documented in includes/class-gw-conditional-logic-date-fields.php */
		if ( fieldValue === false && gform.applyFilters( 'gpcld_require_date_selection', true, formId, rule ) ) {
			if ( rule.operator == 'isnot' ) {
				fieldValue = ruleValue;
			} else if ( rule.operator != 'is' ) {
				ruleValue = '';
			} else {
				fieldValue = '';
			}
		}

		// Convert the rule value to a timestamp if it contains characters such as / or -, exclude negative unix timestamps (dates before 1 January 1970).
		if ( ruleValue && ruleValue.toString().match( /[-/]/ ) && ! isNegativeUnixTimestamp(ruleValue) ) {
			var fieldFormat = gpGetDateFieldFormat( $sourceInput );
			var ruleDate = getDateFromDateString( ruleValue, fieldFormat );

			if (ruleDate) {
				ruleValue = (ruleDate.getTime() / 1000).toString();
			}
		}

		// must be strings for GF
		isMatch = gf_matches_operation( String( fieldValue ), String( ruleValue ), rule.operator );

		return isMatch;
	} );

	function getDateFromDateString( dateString, fieldFormat ) {
		if (!fieldFormat) {
			fieldFormat = {
				sep: '/',
				format: 'mdy'
			}
		}

		var sep = fieldFormat.sep;
		var format = fieldFormat.format;

		var dateBits = dateString.split( sep );

		var month = dateBits[format.indexOf('m')] - 1;
		var day = dateBits[format.indexOf('d')];
		var year = dateBits[format.indexOf('y')];

		return new Date(year, month, day, 0, 0, 0, 0);
	}

	function getDateFromTimeString( timeString ) {

		var bits        = timeString.split( /([0-9]{1,2}):([0-9]{2})(am|pm)?/i ), // 09:00pm => [ '', '09', '00', 'pm', '' ]
			hour        = parseInt( bits[1] ),
			min         = parseInt( bits[2] ),
			ampm        = String( bits[3] ).toLowerCase(),
			currentDate = new Date();

		if ( ampm == 'pm' && hour < 12 ) {
			hour += 12;
		} else if ( ampm == 'am' && hour == 12 ) {
			hour = 0;
		}

		return new Date( currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate(), hour, min, 0, 0 );
	}

	function getDateFormatByClass( cssClass ) {
		var classes = cssClass.split( ' ' );
		for ( var i = 0; i < classes.length; i++ ) {
			if ( classes[i].indexOf( 'gcldf-date-format-' ) != -1 ) {
				var bits = classes[i].split( '-' );
				return bits[ bits.length - 1 ];
			}
		}
		return false;
	}

} )( jQuery );
