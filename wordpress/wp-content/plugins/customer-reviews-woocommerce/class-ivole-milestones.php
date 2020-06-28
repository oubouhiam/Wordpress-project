<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Ivole_Milestones' ) ) :

	class Ivole_Milestones {

    private $milestone1 = 10;
    private $milestone2 = 25;
    private $milestone3 = 50;
    private $milestone4 = 100;
    private $milestone5 = 250;
    private $milestone6 = 500;
    private $milestone7 = 1000;
    private $milestone_never = 9999;
    private $num_reviews = 0;
    private $current_milestone;

	  public function __construct() {
      $this->current_milestone = intval( get_option( 'ivole_reviews_milestone', $this->milestone1 ) );

      //count reviews
			global $wpdb;
			$reviews_count = $wpdb->get_var( "SELECT COUNT(DISTINCT comment_id) FROM $wpdb->commentmeta WHERE meta_key = 'ivole_order'" );
			if( $reviews_count ) {
				$this->num_reviews = $reviews_count;
			}
	  }

		public function increase_milestone() {
      //error_log( print_r( $this->current_milestone, true ) );

      if( $this->num_reviews >= $this->milestone7 ) {
        update_option( 'ivole_reviews_milestone', $this->milestone_never );
      } elseif ( $this->num_reviews >= $this->milestone6 ) {
        update_option( 'ivole_reviews_milestone', $this->milestone7 );
      } elseif ( $this->num_reviews >= $this->milestone5 ) {
        update_option( 'ivole_reviews_milestone', $this->milestone6 );
      } elseif ( $this->num_reviews >= $this->milestone4 ) {
        update_option( 'ivole_reviews_milestone', $this->milestone5 );
      } elseif ( $this->num_reviews >= $this->milestone3 ) {
        update_option( 'ivole_reviews_milestone', $this->milestone4 );
      } elseif ( $this->num_reviews >= $this->milestone2 ) {
        update_option( 'ivole_reviews_milestone', $this->milestone3 );
      } elseif ( $this->num_reviews >= $this->milestone1 ) {
        update_option( 'ivole_reviews_milestone', $this->milestone2 );
      } else {
        update_option( 'ivole_reviews_milestone', $this->milestone1 );
      }
		}

		public function count_reviews() {
      return $this->num_reviews;
		}

    public function show_notices() {
      //return true;
      if( $this->current_milestone >= $this->milestone_never ) {
        return false;
      } else {
        if( $this->num_reviews >= $this->current_milestone ) {
          return true;
        } else {
          return false;
        }
      }
    }

    public function milestone_never() {
      update_option( 'ivole_reviews_milestone', $this->milestone_never );
    }
	}

endif;

?>
