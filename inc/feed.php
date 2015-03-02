<?php

/**
 * Our RSS feed class.
 *
 * Parse ecwid products into an RSS feed.
 *
 * @package WordPress
 * @subpackage sjf-et
 * @since SJF ET 1.5
 */

function sjf_et_feed_init() {
	new SJF_Ecwid_Feed();
}
add_action( 'plugins_loaded', 'sjf_et_feed_init' );

class SJF_Ecwid_Feed {

	/**
	 * The slug for our feed.
	 * 
	 * @var string
	 */
	public $feed_url = 'products.xml';

	/**
	 * The number of posts for our feed.
	 * @var integer
	 */
	public $num_posts = 10;

	/**
	 * The sort method for our feed.
	 * @var string
	 */
	public $sort_by = 'ADDED_TIME_DESC';

	/**
	 * Add actions for our class.
	 */
	public function __construct() {

		// Set the content type for RSS.
		add_action( 'wp_headers', array( $this, 'headers' ), 999 );

		// We need to wait until init before we add a feed.
		add_action( 'init', array( $this, 'init' ) );

	}

	/**
	 * Register our feed with WordPress.
	 */
	public function init() {
		add_feed( $this -> feed_url, array( $this, 'the_xml' ) );
	}

	/**
	 * Determine if we are viewing the products feed.
	 * 
	 * @return boolean If we are viewing the product feed, TRUE, else FALSE.
	 */
	public function is_feed() {

		// Just to be safe, make really sure we don't mess up the rest of the site with the wrong content type.
		if(
			is_404()            ||
			is_admin()          ||
			is_archive()        ||
			is_attachment()     ||
			is_author()         ||
			is_comments_popup() ||
			is_date()           ||
			is_front_page()     ||
			is_home()           ||
			is_page()           ||
			is_search()         ||
			is_single()         ||
			is_singular()       
		) {
			return FALSE;
		}

		// Grab the feed url.
		$feed_url = $this -> feed_url;

		// Grab the last portion of the current url.
		$url = untrailingslashit( remove_query_arg( '' ) );
		$url_arr = explode( '/', $url );
		$last_part_of_url = array_pop( $url_arr );

		// Watch our for fragments.
		$frags = explode( '#', $last_part_of_url );

		$last_part_of_url = $frags[0];

		// Compare the current url in either pretty or non-pretty permalink format.
		if( $last_part_of_url == $feed_url ) { return TRUE; }
		if( $last_part_of_url == "?feed=$feed_url" ) { return TRUE; }

		return FALSE;

	}

	/**
	 * If we are viewing the ecwid feed, set the headers for RSS content type.
	 */
	public function headers() {
	
		// Are we viewing the feed?
		if( ! $this -> is_feed() ) {
			return FALSE;
		}

		header( 'Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=' . get_option( 'blog_charset' ), TRUE );

	}

	/**
	 * Get the products for our feed.
	 * 
	 * @return array An array of products.
	 */
	function get_products() {

		// Args for our product query.
		$args = array(
			'limit'  => $this -> num_posts,
			'sortBy' => $this -> sort_by,
		);
		
		// Grab the products.
		$collection = new SJF_Ecwid_Collection( 'products', $args );
		$result = $collection -> get_collection();
		
		// Make sure the result isn't weird.
		if( ! is_array( $result ) ) { return FALSE; }
		if( ! isset( $result['items'] ) ) { return FALSE; }
		
		$items = $result['items'];
		return $items;

	}

	/**
	 * The main template tag for drawing our feed.
	 */
	public function the_xml() {
		
		// Grab the products.
		$items = $this -> get_products();

		$out = '';

		// For each product...
		foreach( $items as $item ) {

			$author      = $this -> get_item_author( $item );
			$categories  = $this -> get_item_cats( $item );
			$content     = $this -> get_item_content( $item );
			$description = $this -> get_item_description( $item );
			$enclosure   = $this -> get_item_enclosure( $item );
			$guid        = $this -> get_item_guid( $item );
			$link        = $this -> get_item_link( $item );
			$pub_date    = $this -> get_item_pub_date( $item );
			$source      = $this -> get_item_source( $item );
			$title       = $this -> get_item_title( $item );
			
			$out .= "
				<item>
					$author
					$categories
					$content
					$description
					$enclosure
					$guid
					$link
					$pub_date
					$source
					$title
				</item>
			";

		}

		// Wrap the products in the channel.
		$out = $this -> get_wrap( trim( $out ) );

		echo $out;

	} 

	/**
	 * Get the date format for our feed.
	 * 
	 * @return string The date format for our feed.
	 */
	function get_date_format() {
		$date_format = 'D, d M Y H:i:s O';
		return $date_format;
	}

	/**
	 * Get the <author> tag for a product.
	 * 
	 * @param  array $item A product.
	 * @return string The <author> tag for a product.
	 */
	function get_item_author( $item ) {
		
		// Grab the the nickname for the store owner.
		$name  = htmlspecialchars( SJF_Ecwid_Helpers::get_store_account_nickname() );
	
		// Grab the store email address.
		$email = sanitize_email( SJF_Ecwid_Helpers::get_store_account_email() );

		$out = "<author>$email ($name)</author>";

		return $out;

	}

	/**
	 * Get the <category> tags for an item.
	 * 
	 * @param  array $item A product.
	 * @return string The <category> tags for an item.
	 */
	function get_item_cats( $item ) {
		
		$out = '';

		// Sanitize all the cat ID's for this item.
		$category_ids = array_map( 'absint',  $item['categoryIds'] );
		
		// For each at ID...
		foreach( $category_ids as $category_id ) {

			/**
			 * Making a remote call for each category.
			 * 
			 * @todo Need to find a way to grab multiple categories by ID.
			 */
			$collection = new SJF_Ecwid_Collection( "categories/$category_id" );
			$cat = $collection -> get_collection();
			
			// If there is something weird about this category, bail.
			if( ! isset( $cat['name'] ) ) { continue; }
			if( ! isset( $cat['url'] ) ) { continue; }

			$name = htmlspecialchars( $cat['name'] );
			$url = esc_url( $cat['url'] );

			$out .= "<category domain='$url'><![CDATA[ $name ]]></category>";

		}

		return $out;

	}

	/**
	 * Get the <content:encoded> for an item.
	 * 
	 * @param  array $item A product.
	 * @return string The <content:encoded> for an item.
	 */
	function get_item_content( $item ) {

		// If there is a thumbnail, prepend it.
		$thumbnail = $this -> get_item_thumbnail_image( $item );
		if( ! empty( $thumbnail ) ) {
			$thumbnail = "<p>$thumbnail</p>";
		}

		// Grab the item description.
		$desc = wp_kses_post( $item['description'] );
		
		$out = "<content:encoded><![CDATA[ $thumbnail$desc ]]></content:encoded>";
		
		return $out;
	}

	/**
	 * Grab the <description> for an item.
	 * 
	 * @param  array $item A product.
	 * @return string The <description> for an item.
	 */
	function get_item_description( $item ) {
		
		// Grab the first 50 words for an item, sans html.
		$out = SJF_Ecwid_Formatting::get_words( strip_tags( $item['description'] ), 50 );
		
		$out = "<description><![CDATA[ $out ]]></description>";

		return $out;
	}

	/**
	 * Grab the product image as an <enclosure>.
	 * 
	 * @param  array $item A product.
	 * @return string The product image as an <enclosure>.
	 */
	function get_item_enclosure( $item ) {

		// If there is no image, bail.
		if( ! isset( $item['imageUrl'] ) ) { return FALSE; }

		// If there is an image, grab it in order to get headers.
		$url  = esc_url( $item['imageUrl'] );
		$file = wp_remote_get( $url );
		if( is_wp_error( $file ) ) { return FALSE; }
		$headers = $file['headers'];
		
		// Grab the size and type of the file.
		$length = absint( $headers['content-length'] );
		$type   = esc_attr( $headers['content-type'] );

		$out = "<enclosure url='$url' length='$length' type='$type' />";

		return $out;

	}

	/**
	 * Get the <guid> tag for an item.
	 * 
	 * @param  array $item A product.
	 * @return string The <guid> tag for an item.
	 */
	function get_item_guid( $item ) {
		return '<guid isPermaLink="true">' . esc_url( $item['url'] ) . '</guid>';
	}

	/**
	 * Get the <link> tag for an item.
	 * 
	 * @param  array $item A product.
	 * @return string The product <link>.
	 */
	function get_item_link( $item ) {
		return '<link>' . esc_url( $item['url'] ) . '</link>';
	}

	/**
	 * Get the <pubDate> of the item.
	 * 
	 * @param  Array $item A product.
	 * @return string The <pubDate> for the item.
	 */
	function get_item_pub_date( $item ) {
		
		// The date on which the product was created in Ecwid.
		$created = $item['created'];

		// The timestamp on which the product was created.
		$timestamp = strtotime( $created );

		// The date format for our feed.
		$date_format = $this -> get_date_format();

		$out = '<pubDate>' . date( $date_format, $timestamp ) . '</pubDate>';

		return $out;
	}

	/**
	 * Get the <source> tag for an item.
	 * 
	 * @param  array $item A product.
	 * @return string The <source> for a product.
	 */
	function get_item_source( $item ) {

		// The the url to this channel.
		$url = $this -> get_channel_url();
		
		// Get the store name.
		$name = htmlspecialchars( SJF_Ecwid_Helpers::get_store_name() );

		$out = "<source url='$url'>$name</source>";

		return $out;

	}

	/**
	 * Get the <title> for an item.
	 * 
	 * @param  array $item A product.
	 * @return string The <title> for the product.
	 */
	function get_item_title( $item ) {
		return '<title>' . htmlspecialchars( $item['name'] ) . '</title>';
	}

	/**
	 * Get the thumbnail <img> for a product.
	 * 
	 * @param  array $item A product.
	 * @return string The product thumbnail image in an <img> tag.
	 */
	function get_item_thumbnail_image( $item ) {
		
		// If there is no image bail.
		if( ! isset( $item['imageUrl'] ) ) { return FALSE; }

		$src  = esc_url( $item['imageUrl'] );

		$alt = esc_attr( $item['name'] );

		$out = "<img src='$src' alt='$alt'>";

		return $out;

	}

	/**
	 * Wrap the RSS items in a <channel> and <rss>.
	 * 
	 * @param  string $rss The RSS items.
	 * @return string The complete RSS document.
	 */
	function get_wrap( $rss ) {
	
		$atom        = $this -> get_channel_atom();
		$copyright   = $this -> get_channel_copyright();
		$description = $this -> get_channel_description();
		$docs        = $this -> get_channel_docs();
		$editor      = $this -> get_channel_editor();
		$generator   = $this -> get_channel_generator();
		$image       = $this -> get_channel_image();
		$link        = $this -> get_channel_link();
		$locale      = $this -> get_channel_locale();
		$pub_date    = $this -> get_channel_pub_date();
		$title       = $this -> get_channel_title();
		$web_master  = $this -> get_channel_web_master();
		
		$out  = '<?xml version="1.0" encoding="UTF-8"?>';
		$out .= "
			<rss version='2.0'
				xmlns:content='http://purl.org/rss/1.0/modules/content/'
				xmlns:wfw='http://wellformedweb.org/CommentAPI/'
				xmlns:dc='http://purl.org/dc/elements/1.1/'
				xmlns:atom='http://www.w3.org/2005/Atom'
				xmlns:sy='http://purl.org/rss/1.0/modules/syndication/'
				xmlns:slash='http://purl.org/rss/1.0/modules/slash/'
			>
			<channel>
				$atom
				$copyright
				$description
				$docs
				$editor
				$generator
				$image
				$link
				$locale
				$pub_date
				$title 
				$web_master
				$rss
			</channel>
		</rss>
		";

		return $out;

	}

	/**
	 * Get the url for this channel.
	 * 
	 * @return string the URL for this channel.
	 */
	function get_channel_url() {

		// Ask WordPress for the link to our feed.
		$out = untrailingslashit( esc_url( get_feed_link( $this -> feed_url ) ) );
	
		return $out;

	}

	/**
	 * Use the store logo to build an <image> tag.
	 * 
	 * @return string The store logo in an <image> tag.
	 */
	function get_channel_image() {

		// If there is no logo, bail.
		$src = esc_url( SJF_Ecwid_Helpers::get_store_logo_src() );
		if( empty( $src ) ) { return FALSE; }

		// Wrap the image src.
		$url = "<url>$src</url>";
				
		// Grab the channel elements to describe the image.
		$title       = $this -> get_channel_title();
		$link        = $this -> get_channel_link();
		$description = $this -> get_channel_description();
		
		$out = "
			<image>
				$url
				$title
				$link
				$description	
			</image>
		";

		return $out;

	}

	/**
	 * Get the <docs> tag for our channel.
	 * 
	 * @return string The <docs> tag.
	 */
	function get_channel_docs() {
		return '<docs>http://blogs.law.harvard.edu/tech/rss</docs>';
	}

	/**
	 * The <atom> tag for our channel.
	 * 
	 * @return string The <atom> tag for our channel.
	 */
	function get_channel_atom() {

		$self = $this -> get_channel_url();
		$out = "<atom:link href='$self' rel='self' type='application/rss+xml' />";
		return $out;
	}

	/**
	 * Get the title for our channel.
	 * 
	 * @return string The title for our channel.
	 */
	function get_channel_title() {
		$out = '<title>' . htmlspecialchars( SJF_Ecwid_Helpers::get_store_name() ) .'</title>';
		return $out;
	}

	/**
	 * Get the link for our channel.
	 * 
	 * @return string The link for our channel.
	 */
	function get_channel_link() {		
		return '<link>' . htmlspecialchars( SJF_Ecwid_Helpers::get_store_url() ). '</link>';
	}

	/**
	 * Get the copyright for our channel.
	 * 
	 * @return string The copyright for our channel.
	 */
	function get_channel_copyright() {
		$year = date( 'Y' );
		$org  = htmlspecialchars( SJF_Ecwid_Helpers::get_store_name() );
		$out = '<copyright>' . sprintf( esc_html__( 'Copyright %d, %s' ), $year, $org ) . '</copyright>';

		return $out;
	}

	/**
	 * Get the description for our channel.
	 * 
	 * @return string The description for our channel.
	 */
	function get_channel_description() {

		$blog_name = get_bloginfo( 'name' );
		$products = esc_html__( 'Products', 'sjf-et' );

		$out = htmlspecialchars( "$blog_name | $products" );

		$out = "<description>$out</description>";

		return $out;
	}

	/**
	 * Get the locale for our channel.
	 * 
	 * @return string The locale for our channel.
	 */
	function get_channel_locale() {
		
		$locale = SJF_Ecwid_Helpers::get_store_locale();
		
		$locale = substr( $locale, 0, 2 );
		$out    = '<language>' . htmlspecialchars( $locale ). '</language>';
	
		return $out;

	}

	/**
	 * Get the managingEditor for our channel.
	 * 
	 * @return string The managingEditor for our channel.
	 */
	function get_channel_editor() {

		$name  = htmlspecialchars( SJF_Ecwid_Helpers::get_store_account_nickname() );
		$email = sanitize_email( SJF_Ecwid_Helpers::get_store_account_email() );
	
		return "<managingEditor>$email ($name)</managingEditor>";
	
	}

	/**
	 * Get the webMaster for our channel.
	 * 
	 * @return string The webMaster for our channel.
	 */
	function get_channel_web_master() {

		$name  = htmlspecialchars( get_bloginfo( 'name' ) );
		$email = sanitize_email( get_bloginfo( 'admin_email' ) );
	
		return "<webMaster>$email ($name)</webMaster>";
	
	}

	/**
	 * Get the pubDate for our channel.
	 * 
	 * @return string The pubDate for our channel.
	 */
	function get_channel_pub_date() {
		
		$now = time();

		$date_format = $this -> get_date_format();

		$date = '<pubDate>' . date( $date_format, $now ) . '</pubDate>';

		return $date;

	}	

	/**
	 * Get the generator for our channel.
	 * 
	 * @return string The generator for our channel.
	 */
	function get_channel_generator() {
		
		$generator = esc_url_raw( 'http://wordpress.org/?v=' . get_bloginfo_rss( 'version' ) );
		$generator = "<generator>$generator</generator>";

		return $generator;

	}

	/**
	 * Hoarded code for getting Google Merchants products.
	 */

	/*
	function get_item_g_price( $item ) {
		return '<g:price>' . htmlspecialchars( $item['price'] ) . '</g:price>';
	}

	function get_item_g_id( $item ) {
		return '<g:id xmlns:g="http://base.google.com/ns/1.0">' . htmlspecialchars( $item['sku'] ) . '</g:id>';
	}

	function get_item_g_image_link( $item ) {
		
		if( ! isset( $item['originalImageUrl'] ) ) { return FALSE; }

		$src = esc_url( $item['originalImageUrl'] );

		$out = "<g:image_link xmlns:g='http://base.google.com/ns/1.0'>$src</g:image_link>";

		return $out;
	}

	function get_item_g_shipping_weight( $item ) {
		$weight = $item['weight'];
		$weight = SJF_Ecwid_Formatting::get_weight( $weight );
		$out =  "<g:shipping_weight xmlns:g='http://base.google.com/ns/1.0'>$weight</g:shipping_weight>";
		return $out;
	}

	function get_item_g_availability( $item ) {
		$in_stock = $item['inStock'];
		$avail = 'in stock';
		if( ! $in_stock ) {
			$avail = 'out of stock';
		}
		$out = "<g:availability xmlns:g='http://base.google.com/ns/1.0'>$avail</g:availability>";
		return $out;
	}

	function get_item_g_product_type( $item ) {
		//list the full string: Home & Garden > Kitchen & Dining > Kitchen Appliances > Refrigerators. You must use " > " as a separator, including a space before and after the symbol.
		
		$out = '';

		$category_ids = array_map( 'absint',  $item['categoryIds'] );
		
		$count = count( $category_ids );

		$i = 0;
		foreach( $category_ids as $category_id ) {
			$i++;

			// @todo Need to find a way to grab multiple categories by ID.
			$collection = new SJF_Ecwid_Collection( "categories/$category_id" );
			$cat = $collection -> get_collection();
			
			// If there is something weird about this category, bail.
			if( ! isset( $cat['name'] ) ) { continue; }

			$name = htmlspecialchars( $cat['name'] );

			$out .= "$name";

			if( $i < $count ) {
				$out .= ' &gt; ';
			}

		}

		if( ! empty( $out ) ) {
			$out = "<g:product_type>$out</g:product_type>";
		}

		return $out;
		
	}
	*/

}