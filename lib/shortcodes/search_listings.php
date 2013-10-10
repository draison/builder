<?php
/**
 * Post type/Shortcode to generate a list of listings
 *
 */
class PL_Search_Listing_CPT extends PL_SC_Base {

	protected $shortcode = 'search_listings';

	protected $title = 'Search Listings';

	protected $options = array(
		'context'			=> array( 'type' => 'select', 'label' => 'Template', 'default' => '' ),
		'width'				=> array( 'type' => 'int', 'label' => 'Width', 'default' => 250, 'description' => '(px)' ),
		'height'			=> array( 'type' => 'int', 'label' => 'Height', 'default' => 250, 'description' => '(px)' ),
		'widget_class'		=> array( 'type' => 'text', 'label' => 'CSS Class', 'default' => '', 'description' => '(optional)' ),
		'sort_by_options'	=> array( 'type' => 'multiselect', 'label' => 'Items in "Sort By" list',
			'options'	=> array(	// options we always want to show even if they are not part of the filter set
				'location.address'	=> 'Address',
				'cur_data.price'	=> 'Price',
				'cur_data.sqft'		=> 'Square Feet',
				'cur_data.lt_sz'	=> 'Lot Size',
				'compound_type'		=> 'Listing Type',
				'cur_data.avail_on'	=> 'Available On',
			),
			'default'	=> array('cur_data.price','cur_data.beds','cur_data.baths','cur_data.sqft','location.locality','location.postal'),
		),
		'sort_by'			=> array( 'type' => 'select', 'label' => 'Default sort by', 'options' => array(), 'default' => 'cur_data.price' ),
		'sort_type'			=> array( 'type' => 'select', 'label' => 'Default sort direction', 'options' => array('asc'=>'Ascending', 'desc'=>'Descending'), 'default' => 'desc' ),
		// TODO: sync up with js list
		'query_limit'		=> array( 'type' => 'select', 'label' => 'Default number of results', 'options' => array('10'=>'10', '25'=>'25', '25'=>'25', '50'=>'50', '100'=>'100', '200'=>'200', '-1'=>'All'), 'default' => '10' ),
	);

	protected $subcodes = array(
		'price'			=> array('help' => 'Property price'),
		'sqft'			=> array('help' => 'Total square feet'),
		'beds'			=> array('help' => 'Number of bedrooms'),
		'baths'			=> array('help' => 'Number of bathrooms'),
		'half_baths'	=> array('help' => 'Number of half bathrooms'),
		'avail_on'		=> array('help' => 'Date the property will be available'),
		'url'			=> array('help' => 'Link to page for the listing'),
		'address'		=> array('help' => 'Street address'),
		'locality'		=> array('help' => 'Locality'),
		'region'		=> array('help' => 'Region'),
		'postal'		=> array('help' => 'Zip/postal code'),
		'neighborhood'	=> array('help' => 'Neighborhood'),
		'county'		=> array('help' => 'County'),
		'country'		=> array('help' => 'Country'),
		//'coords'		=> array('help' => ''),
		'unit'			=> array('help' => 'Unit'),
		'full_address'	=> array('help' => 'Full address'),
		'email'			=> array('help' => 'Email address for this listing'),
		'phone'			=> array('help' => 'Contact phone'),
		'desc'			=> array('help' => 'Property description'),
		'image'			=> array('help' => 'Property thumbnail image. You can use <code>width</code> and <code>height</code> to set the dimensions of the thumbnail in pixels, for example: <code>[image width=\'180\' height=\'120\']</code>'),
		'image_url'		=> array('help' => 'Image URL for the listing if one exists. You can use the optional <code>index</code> attribute (defaults to 0, the first image) to specify the index of the listing image and <code>placeholder</code> to specify the URL of an image to use if the listing does not have an image, for example: <code>[image_url index=\'1\' placeholder=\'http://www.domain.com/path/to/image\']</code>'),
		'mls_id'		=> array('help' => 'MLS #'),
		'listing_type'	=> array('help' => 'Type of listing'),
		//'gallery'		=> array('help' => 'Image gallery'),
		//'amenities'	=> array('help' => 'List of amenties'),
		'price_unit'	=> array('help' => 'Unit price'),
		'compliance'	=> array('help' => 'MLS compliance statement'),
		'favorite_link_toggle' => array('help' => 'Link to add/remove from favorites'),
		'aname'			=> array('help' => 'Agent name'),
		'oname'			=> array('help' => 'Office name'),
		'custom'		=> array('help' => 'Use to display a custom listing attribute.<br />
Format is as follows:<br />
<code>[custom group=\'group_name\' attribute=\'some_attribute_name\' type=\'text\' value=\'some_value\']</code><br />
where:<br />
<code>group</code> - The group identifier if the listing attribute is part of a group. Possible values are <code>location</code>, <code>rets</code>, <code>metadata</code>, <code>uncur_data</code>.<br />
<code>attribute</code> - (required) The unique identifier of the listing attribute.<br />
<code>type</code> - (optional, default is \'text\') Can be <code>text</code>, <code>currency</code>, <code>list</code>. Used to indicate how the attribute should be formatted.<br />
<code>value</code> - (optional) Indicates text to be displayed if the listing attribute is empty.
'),
		'if'			=> array('help' => 'Use to conditionally display some content depending on the value of a listing\'s attribute.<br />
Format is as follows:<br />
<code>[if group=\'group_name\' attribute=\'some_attribute_name\' value=\'some_value\'] some HTML and/or some [template tag] that will be displayed if the condition is true [/if]</code><br />
where:<br />
<code>group</code> - The group identifier if the listing attribute is part of a group. Possible values are <code>location</code>, <code>rets</code>, <code>metadata</code>, <code>uncur_data</code>.<br />
<code>attribute</code> - (required) The unique identifier of the listing attribute.<br />
<code>value</code> - (optional) By default the condition is true if the attribute has any value other than being empty. If you wish to test if the attribute matches a specific value, then set that value in this parameter.<br />
For example, to only display bedroom and bathroom details if the property is residential:<br />
<code>[if attribute=\'compound_type\' value=\'res_sale\']Beds: [beds] Baths: [baths][/if]</code><br />
To add some text to your listings:<br />
<code>[if group=\'rets\' attribute=\'aid\' value=\'MY_MLS_AGENT_ID\']&lt;span&gt;Featured Listing&lt;/span&gt;[/if]</code>'),
);

	protected $template = array(
		'snippet_body'=> array(
			'type'			=> 'textarea',
			'label'			=> 'HTML to format each individual listing',
			'description'	=> 'You can use the template tags with any valid HTML in this field to lay out each listing. Leave this field empty to use the built in template.',
			'help'			=> '',
			'css'			=> 'mime_html',
		),

		'css' => array(
			'type'			=> 'textarea',
			'label'			=> 'CSS',
			'description'	=> 'You can use any valid CSS in this field to style the listings, which will also inherit the CSS from the theme.',
			'help'			=> '',
			'css'			=> 'mime_css',
		),

		'before_widget' => array(
			'type'			=> 'textarea',
			'label'			=> 'Add content before the listings',
			'description'	=> 'You can use any valid HTML in this field and it will appear before the listings. For example, you can wrap the whole list with a <div> element to apply borders, etc, by placing the opening <div> tag in this field and the closing </div> tag in the following field.',
			'help'			=> '',
			'css'			=> 'mime_html',
		),

		'after_widget' => array(
			'type'			=> 'textarea',
			'label'			=> 'Add content after the listings',
			'description'	=> 'You can use any valid HTML in this field and it will appear after the listings.
For example, you might want to include the [compliance] shortcode.',
			'help'			=> '',
			'css'			=> 'mime_html',
		),
	);

	// stores fetched listing attributes value
	protected static $sl_listing_attributes = array();

	// stores sort list
	protected static $sl_sort_list = array();

	// stores fetched filter value
	protected static $sl_filter_options = array();




	public static function init() {
		parent::_init(__CLASS__);
	}

	public function get_args($with_choices = false, $with_help = false) {
		$ret = parent::get_args($with_choices, $with_help);

		if ($with_help && !empty($this->subcodes) && !empty($this->template['snippet_body']) ) {
			$template_tags = '<h4>Listing Template Tags</h4>';
			$template_tags .= '<p>Use the following template tags to customize the way each listing is displayed.
				When the template is rendered in a web page, the tag will be replaced with the corresponding attribute
				of the property listing:<br /></p>';
			foreach($this->subcodes as $template_tag=>$atts) {
				$template_tags .= '<h4 class="subcode"><a href="#">['.$template_tag.']</a></h4>';
				if (!empty($atts['help'])) {
					$template_tags .= '<div class="description subcode-help">'. $atts['help'];
					if ($template_tag=='custom' || $template_tag=='if') {
						$template_tags = $template_tags . '<br />Click <a href="#" class="show_listing_attributes">here</a> to see a list of available listing attributes.';
					}
					$template_tags .= '</div>';
				}
			}
			$ret['template']['snippet_body']['help'] .=  $template_tags;
		}
		return $ret;
	}

	/**
	 * Get list of options with sort by values from the api.
	 */
	public function get_options_list($with_choices = false) {
		if (empty(self::$sl_listing_attributes)) {
			self::$sl_listing_attributes = PL_Shortcode_CPT::get_listing_attributes(true);
			self::$sl_sort_list = array();
			foreach(self::$sl_listing_attributes as $args) {
				$group = $args['group'];
				switch($group) {
					case 'metadata':
						$group = 'cur_data';
						break;
					case 'custom':
						$group = 'uncur_data';
						break;
					case 'rets':
						continue;
						break;

				}
				$key = (empty($group) ? '' : $group.'.').$args['attribute'];
				self::$sl_sort_list[$key] = $args['label'];
			}
		}
		$this->options['sort_by_options']['options'] = self::$sl_sort_list;
		$this->options['sort_by']['options'] = self::$sl_sort_list;
		return $this->options;
	}

	/**
	 * Get list of filter options from the api.
	 */
	public function get_filters_list($with_choices = false) {
		if (empty(self::$sl_filter_options)) {
			self::$sl_filter_options = PL_Shortcode_CPT::get_listing_filters(false, $with_choices);
		}
		return self::$sl_filter_options;
	}

	/**
	 * Called when a shortcode is found in a post.
	 * @param array $atts
	 * @param string $content
	 */
	public function shortcode_handler($atts, $content) {
		add_filter('pl_filter_wrap_filter', array(__CLASS__, 'js_filter_str'));
		$filters = '';

		// call do_shortcode for all pl_filter shortcodes
		// Note: don't leave whitespace or other non-valuable symbols
		if (!empty($content)) {
			$filters = do_shortcode(strip_tags($content));
		}

		$filters = str_replace('&nbsp;', '', $filters);

		// Handle attributes using shortcode_atts...
		// These attributes will hand the look and feel of the listing form container, as
		// the context func applies to each individual listing.
		$content = PL_Component_Entity::search_listings_entity($atts, $filters);

		return self::wrap('search_listings', $content);
	}

	public function do_templatetags($content, $listing_data) {
		PL_Component_Entity::$listing = $listing_data;
		return $this->_do_templatetags(array($this, 'templatetag_callback'), array_keys($this->subcodes), $content);
	}

	public function templatetag_callback($m) {
		if ($m[1]=='[' && $m[6]==']') {
			return substr($m[0], 1, -1);
		}

		$tag = $m[2];
		$atts = shortcode_parse_atts($m[3]);
		$content = $m[5];

		if ($tag == 'if') {
			$val = isset($atts['value']) ? $atts['value'] : null;
			if (empty($atts['group'])) {
				if ((!isset(PL_Component_Entity::$listing[$atts['attribute']]) && $val==='') ||
					(isset(PL_Component_Entity::$listing[$atts['attribute']]) && (PL_Component_Entity::$listing[$atts['attribute']]===$val || (is_null($val) && PL_Component_Entity::$listing[$atts['attribute']])))) {
					return self::_do_templatetags(array($this, 'templatetag_callback'), array_keys($this->subcodes), $content);
				}
			}
			elseif ((!isset(PL_Component_Entity::$listing[$atts['group']][$atts['attribute']]) && $val==='') ||
				(isset(PL_Component_Entity::$listing[$atts['group']][$atts['attribute']]) && (PL_Component_Entity::$listing[$atts['group']][$atts['attribute']]===$val || (is_null($val) && PL_Component_Entity::$listing[$atts['group']][$atts['attribute']])))) {
				return self::_do_templatetags(array($this, 'templatetag_callback'), array_keys($this->subcodes), $content);
			}
			return '';
		}
		$content = PL_Component_Entity::listing_sub_entity($atts, $content, $tag);
		return self::wrap('listing_sub', $content);
	}
}

PL_Search_Listing_CPT::init();
