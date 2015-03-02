<?php

/**
 * RSS link widget.
 * 
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 1.5
 */

function sjf_et_register_rss() {
	register_widget( 'SJF_ET_RSS' );
}
add_action( 'widgets_init', 'sjf_et_register_rss' );

/**
 * Adds Foo_Widget widget.
 */
class SJF_ET_RSS extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		add_shortcode( SJF_Ecwid_Formatting::get_class_name( __CLASS__ ), array( $this, 'shortcode' ) );

		add_filter( 'SJF_Ecwid_Admin_Documentation_get_docs', array( $this, 'get_docs' ), 80 );

		parent::__construct(

			// Base ID.
			SJF_Ecwid_Formatting::get_class_name( __CLASS__ ),
			
			// Name.
			sprintf( __( '%s: RSS Link', 'sjf-et' ), SJF_Ecwid_Helpers::get_plugin_short_title() ),

			// Args.
			array(
				'description' => __( 'A link to the RSS feed for your store.', 'sjf-et' ),
			)
		
		);
	}

	/**
	 * Send the widget as a shortcode.
	 * 
	 * @param  array $atts An array of shortcode args.
	 * @return string      The widget html output.
	 */
	public function shortcode( $atts ) {
		
		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ . '_'.  __FUNCTION__ );

		$args = shortcode_atts( array(
			'title'            => '',
			'label'            => '',
			'before_title'     => "<h3 class='$base_class-title'>",
			'after_title'      => '</h3>',
			'before_widget'    => "<div class='$base_class'>",
			'after_widget'     => '</div>',
		), $atts, __CLASS__ );
	
		$instance['title']= $args['title'];
		$instance['label']= $args['label'];

		$out = $this -> widget( $args, $instance, FALSE );

		$out = apply_filters(  __CLASS__ . '_' . __FUNCTION__, $out );

		return $out;

	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args = array(), $instance = array(), $echo = TRUE  ) {
		
		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		
		$title = '';
		if ( ! empty( $instance['title'] ) ) {
			$before_title = $args['before_title'];
			$after_title  = $args['after_title'];
			$title        = $before_title . apply_filters( 'widget_title', $instance['title'] ) . $after_title;
		}
		
		// Grab the lable.
		$label = '';
		if( isset( $instance['label'] ) ) {
			$label = esc_html( $instance['label'] );
		}

		// Grab the link.
		$out = $this -> get_link( $label );

		$out = $before_widget . $title . $out . $after_widget;

		if( $echo ) {
			echo $out;
		} else {
			return $out;
		}

	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		
		echo SJF_Ecwid_Helpers::get_nag();

		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$label = ! empty( $instance['label'] ) ? $instance['label'] : '';
		
		?>
		<p>
			<label for="<?php echo $this -> get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this -> get_field_id( 'title' ); ?>" name="<?php echo $this -> get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
			<label for="<?php echo $this -> get_field_id( 'label' ); ?>"><?php _e( 'Label:' ); ?></label> 
			<input class="widefat" id="<?php echo $this -> get_field_id( 'label' ); ?>" name="<?php echo $this -> get_field_name( 'label' ); ?>" type="text" value="<?php echo esc_attr( $label ); ?>">
		</p>

		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		
		/**
		 * When the transients class is initiated with a value of FALSE (first arg), it dumps caches.
		 * However the second arg, FALSE, tells it not to dump rewrite rules, since that would break the
		 * widget customizer screen.
		 */
		$trans = new SJF_Ecwid_Transients( FALSE, FALSE );

		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['label'] = ( ! empty( $new_instance['label'] ) ) ? strip_tags( $new_instance['label'] ) : '';
		
		return $instance;
	}

	/**
	 * Get the RSS link.
	 * 
	 * @return string A product RSS feed link.
	 */
	function get_link( $label = '' ) {

		$base_class = SJF_Ecwid_Formatting::get_class_name( __CLASS__ . '_'.  __FUNCTION__ );

		$feed = new SJF_Ecwid_Feed;
		$href = $feed -> get_channel_url();

		// We're gonna need dashicons.
		wp_enqueue_style( 'dashicons' );

		$icon = '<span class="dashicons dashicons-rss"></span>';
		$icon = apply_filters( __CLASS__ . '_'.  __FUNCTION__ . 'icon', $icon );

		$label = "<span class='$base_class-label'>$label</span>";
		$label = apply_filters( __CLASS__ . '_'.  __FUNCTION__ . '_label', $label );

		$out = "<a class='$base_class' target='_blank' href='$href'>$icon$label</a>";

		$out = apply_filters(  __CLASS__ . '_' . __FUNCTION__, $out );

		return $out;

	}

		/**
	 * Grab info about the rss shortcode.
	 * 
	 * @return string Info about the rss shortcode.
	 */
	function get_docs( $in ) {

		$docs = new SJF_Ecwid_Admin_Documentation;

		$namespace = SJF_Ecwid_Helpers::get_namespace();

		$label = esc_html__( 'RSS Shortcode', 'sjf-et' );
		
		$feed = new SJF_Ecwid_Feed;

		$feed_url = $feed -> get_channel_url();

		$feed_link = "<code><a target='_blank' href='$feed_url'>$feed_url</a></code>";

		$content_1 = '<p>' . esc_html__( 'The RSS shortcode can be used like this:', 'sjf-et') . '</p>'; 
		$content_2 = '<p><code>[sjf_et_rss label="click me"]</code></p>';
		$content_3 = '<p>' . esc_html__( 'You may specify a text label which will appear next to an RSS icon.', 'sjf-et' ) . '</p>';
		$content_4 = '<p>' . esc_html__( 'The shortcode points to a special RSS feed for your store products.  You may need to clear your caches (see above) in order for this feed to work.' ) . '</p>';
		$content_5 = '<p>' . esc_html__( 'The feed is valid RSS2 and can be used with services like MailChimp and FeedBurner.  It is not a Google Merchants feed and will not work as one.' ) . '</p>';
		$content_6 = '<p>' . sprintf( esc_html__( 'Your feed can be found at %s.', 'sjf-et' ), $feed_link ) . '</p>';

		$content = $content_1 . $content_2 . $content_3 . $content_4 . $content_5  . $content_6;

		$out = $docs -> get_doc( $label, __FUNCTION__, $content );
		
		return $in . $out;
	}

}