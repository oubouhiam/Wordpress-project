<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Custom_Questions' ) ) :

	class CR_Custom_Questions {
		private $questions = array();
		private $meta_id = 'ivole_c_questions';

	  public function __construct() {
	  }

		public function parse_shop_questions( $order ) {
			if( isset( $order->shop_questions ) && is_array( $order->shop_questions ) ) {
				$this->parse_questions( $order->shop_questions );
			}
		}

		public function parse_product_questions( $item ) {
			if( isset( $item->item_questions ) && is_array( $item->item_questions ) ) {
				$this->parse_questions( $item->item_questions );
			}
		}

		public function parse_questions( $input ) {
			$num_questions = count( $input );
			for( $i = 0; $i < $num_questions; $i++ ) {
				if( $input[$i]->type ) {
					switch( $input[$i]->type ) {
						case 'radio':
							if( isset( $input[$i]->title ) && isset( $input[$i]->value ) ) {
								$question = new CR_Custom_Question();
								$question->type = 'radio';
								$question->title = sanitize_text_field( $input[$i]->title );
								$question->value = sanitize_text_field( $input[$i]->value );
								$this->questions[] = $question;
							}
							break;
						case 'checkbox':
							if( isset( $input[$i]->title ) &&
									isset( $input[$i]->value ) && is_array( $input[$i]->value ) ) {
										$question = new CR_Custom_Question();
										$question->type = 'checkbox';
										$question->title = sanitize_text_field( $input[$i]->title );
										$count_values = count( $input[$i]->value );
										for( $j = 0; $j < $count_values; $j++ ) {
											$question->values[] = sanitize_text_field( $input[$i]->value[$j] );
										}
										$this->questions[] = $question;
									}
							break;
						case 'rating':
							if( isset( $input[$i]->title ) && isset( $input[$i]->value ) ) {
								$question = new CR_Custom_Question();
								$question->type = 'rating';
								$question->title = sanitize_text_field( $input[$i]->title );
								$question->value = intval( $input[$i]->value );
								$this->questions[] = $question;
							}
							break;
						case 'comment':
							if( isset( $input[$i]->title ) && isset( $input[$i]->value ) ) {
								$question = new CR_Custom_Question();
								$question->type = 'comment';
								$question->title = sanitize_text_field( $input[$i]->title );
								$question->value = sanitize_text_field( $input[$i]->value );
								$this->questions[] = $question;
							}
							break;
						default:
							break;
					}
				}
			}
		}

		public function has_questions() {
			if( count( $this->questions ) > 0 ) {
				return true;
			} else {
				return false;
			}
		}

		public function save_questions( $review_id ) {
			if( count( $this->questions ) > 0 ) {
				update_comment_meta( $review_id, $this->meta_id, $this->questions );
			}
		}

		public function read_questions( $review_id ) {
			$meta = get_comment_meta( $review_id, $this->meta_id, true );
			if( $meta && is_array( $meta ) ) {
				$count_meta = count( $meta );
				for( $i = 0; $i < $count_meta; $i++ ) {
					if( $meta[$i] instanceof CR_Custom_Question ) {
						$this->questions[] = $meta[$i];
					}
				}
			}
		}

		public function output_questions( $f = false, $hr = true ) {
			$fr = '';
			if( $f ) {
				$fr = 'f';
			}
			$count_questions = count( $this->questions );
			$output = '';
			for( $i = 0; $i < $count_questions; $i++ ) {
				if( isset( $this->questions[$i]->type ) ) {
					switch( $this->questions[$i]->type ) {
						case 'radio':
							if( isset( $this->questions[$i]->title ) && isset( $this->questions[$i]->value ) ) {
								$output .= '<p class="iv' . $fr . '-custom-question-p"><span class="iv' . $fr . '-custom-question-radio">' . $this->questions[$i]->title .
									'</span> : ' . $this->questions[$i]->value . '</p>';
							}
							break;
						case 'checkbox':
							if( isset( $this->questions[$i]->title ) && isset( $this->questions[$i]->values ) &&
						 			is_array( $this->questions[$i]->values ) ) {
								$count_values = count( $this->questions[$i]->values );
								$output_temp = '';
								for( $j = 0; $j < $count_values; $j++ ) {
									$output_temp .= '<li>' . $this->questions[$i]->values[$j] . '</li>';
								}
								if( $count_values > 0 ) {
									$output .= '<p class="iv' . $fr . '-custom-question-checkbox">' . $this->questions[$i]->title . ' : </p>';
									$output .= '<ul class="iv' . $fr . '-custom-question-ul">' . $output_temp . '</ul>';
								}
							}
							break;
						case 'rating':
							if( isset( $this->questions[$i]->title ) && isset( $this->questions[$i]->value ) ) {
								if( $this->questions[$i]->value > 0 ) {
									$output .= '<div class="iv' . $fr . '-custom-question-rating-cont"><span class="iv' . $fr . '-custom-question-rating">' . $this->questions[$i]->title . ' :</span>';
									$output .= '<span class="iv' . $fr . '-star-rating">';
									for ( $j = 1; $j < 6; $j++ ) {
										$class = ( $j <= $this->questions[$i]->value ) ? 'filled' : 'empty';
										$output .= '<span class="dashicons dashicons-star-' . $class . '"></span>';
									}
									$output .= '</span></div>';
								}
							}
							break;
						case 'comment':
							if( isset( $this->questions[$i]->title ) && isset( $this->questions[$i]->value ) ) {
								$output .= '<p class="iv' . $fr . '-custom-question-p"><span class="iv' . $fr . '-custom-question-radio">' . $this->questions[$i]->title .
									'</span> : ' . $this->questions[$i]->value . '</p>';
							}
							break;
						default:
							break;
					}
				}
			}
			if( strlen( $output ) > 0 ) {
				if( $f ) {
					$output = '<hr class="iv' . $fr . '-custom-question-hr">' . $output . '<hr class="iv' . $fr . '-custom-question-hr">';
				} else {
					if( $hr ) {
						$output = '<hr class="iv' . $fr . '-custom-question-hr">' . $output;
					}
				}
				echo apply_filters( 'cr_custom_questions', $output );
			}
		}

		public function delete_questions( $review_id ) {
			delete_comment_meta( $review_id, $this->meta_id );
		}

	}

endif;

if ( ! class_exists( 'CR_Custom_Question' ) ) :
	class CR_Custom_Question {
		public $type;
		public $title;
		public $value;
		public $values = array();
	}
endif;
?>
