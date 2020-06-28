jQuery(document).ready(function($){



	(function(){
		//initialize datepicker
		if( xoo_aff_localize.datepicker_data ){
			var data = JSON.parse( xoo_aff_localize.datepicker_data );
			if( $.isEmptyObject( data ) ) return;
			$.each( data, function( index, field ){
				$( 'input[name='+field.id+']' ).datepicker(field.args);
			} )
		}

	}());


	var all_states = JSON.parse(xoo_aff_localize.states);

	//-------------------- XXXXXXXXXXXXXXX -------------------- //

	var SelectCountry = function( $select ){
		
		var self = this;
		self.$selectCountry 	= $select; 
		self.id 				= self.$selectCountry.attr('id');
		self.$form 				= self.$selectCountry.closest( 'form' );
		self.$selectState 		= self.$form.find('.xoo-aff-states[data-country_field="'+self.id+'"]');
		self.$selectPhoneCode 	= self.$form.find('.xoo-aff-phone_code[data-country_field="'+self.id+'"]');

		//Methods
		self.states = self.$selectState.length ? self.states() : false;
		//Events
		self.$selectCountry.on( 'change', { country: self }, this.onChange );
		self.$selectCountry.trigger('change'); //on load

	}

	SelectCountry.prototype.onChange = function( event ){
		var self = event.data.country;
		if( self.states ){
			self.states.updateStateField( event );
		}

		if( self.$selectPhoneCode.length ){
			self.updatePhoneCodeField();
		}
	}

	//PhoneCode field handler
	SelectCountry.prototype.updatePhoneCodeField = function(){
		var country 	= this,
			$codeOption = country.$selectPhoneCode.find( 'option[data-country_code="'+country.$selectCountry.val()+'"]' );
		
		if( $codeOption ){
			$codeOption.prop('selected','selected');
		}
	}

	//State field handler
	SelectCountry.prototype.states = function(){

		var country 		= this,
			$selectState 	= country.$selectState,
			defaultValue 	= country.$selectState.data( 'default_state' );

		var Handler =  {

			init: function(){
				Handler.$statePlaceholder 	= $selectState.find('option[value="placeholder"]');
				Handler.$selectStateCont 	= $selectState.closest('.xoo-aff-input-group')
				Handler.$inputState 		= Handler.createStateInput();
			},

			getStates: function(){
				var states = all_states[ country.$selectCountry.val() ];
				return states === undefined || states.length === 0  ? null : states;
			},

			createStateInput: function(){
				return $( '<input type="text" />' )
					.prop( 'id', $selectState.attr('id') )
					.prop( 'name', $selectState.attr('name') )
					.prop('placeholder', Handler.$statePlaceholder.html() )
					.addClass( $selectState.attr('class') );
			},

			updateStateField: function( event ){

				var country = event.data.country;

				//Remove all current states
				$selectState.find('option').not(Handler.$statePlaceholder).remove();

				if( country.$selectCountry.val() ){
					var active_states = Handler.getStates();

					if( !active_states ){
						$selectState.remove();
						Handler.$selectStateCont.append( Handler.$inputState );
					}
					else{
						Handler.$inputState.remove();
						Handler.$selectStateCont.append( $selectState );
						$.each( active_states, function( state_key, label ){
							$selectState.append( '<option value="'+state_key+'">'+label+'</option>' );
						} )

						Handler.$selectStateCont.find( 'option[value='+defaultValue+']' ).prop( 'selected', 'selected' );
					}
				}

			}

		}

		Handler.init();

		return Handler;
	}

	$.each( $('select.xoo-aff-country'), function( index, el ){
		new SelectCountry( $(el));
	} );


	
})