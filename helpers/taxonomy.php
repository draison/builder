<?php 

PL_Taxonomy_Helper::init();
class PL_Taxonomy_Helper {

	// List of taxonomies used to build a search UI
	public static $location_taxonomies = array('state' => 'State', 'zip' => 'Zip', 'city' => 'City', 'neighborhood' => 'Neighborhood');
	
	// List of taxonomies used to bulid URLs, etc.
	public static $all_loc_taxonomies = array('state', 'zip', 'city', 'neighborhood', 'street');

	public static function init () {
		add_action('init', array(__CLASS__, 'register_taxonomies'));
		add_filter('post_type_link', array(__CLASS__, 'get_property_permalink'), 10, 3);
		add_action('wp_ajax_save_polygon', array(__CLASS__, 'save_polygon'));
		add_action('wp_ajax_update_polygon', array(__CLASS__, 'update_polygon'));
		add_action('wp_ajax_delete_polygon', array(__CLASS__, 'delete_polygon'));
		add_action('wp_ajax_get_polygons_datatable', array(__CLASS__, 'get_polygons_datatable'));
		add_action('wp_ajax_get_polygon', array(__CLASS__, 'get_polygon'));
		
		add_action('wp_ajax_get_polygons_by_type', array(__CLASS__, 'ajax_get_polygons_by_type'));
		add_action('wp_ajax_nopriv_get_polygons_by_type', array(__CLASS__, 'ajax_get_polygons_by_type'));

		add_action('wp_ajax_get_polygons_by_slug', array(__CLASS__, 'ajax_get_polygons_by_slug'));
		add_action('wp_ajax_nopriv_get_polygons_by_slug', array(__CLASS__, 'ajax_get_polygons_by_slug'));

		add_action('wp_ajax_nopriv_lifestyle_polygon', array(__CLASS__, 'lifestyle_polygon'));
		add_action('wp_ajax_lifestyle_polygon', array(__CLASS__, 'lifestyle_polygon'));
		add_action('wp_ajax_polygon_listings', array(__CLASS__, 'ajax_polygon_listings'));
		add_action('wp_ajax_nopriv_polygon_listings', array(__CLASS__, 'ajax_polygon_listings'));
	}

	public static function register_taxonomies () {
		register_taxonomy('state', array('property'), array('hierarchical' => TRUE,'label' => __('States'), 'public' => TRUE,'show_ui' => TRUE,'query_var' => true,'rewrite' => array('with_front' => false, 'hierarchical' => false) ) );
		register_taxonomy('zip', array('property'), array('hierarchical' => TRUE,'label' => __('Zip Codes'), 'public' => TRUE,'show_ui' => TRUE,'query_var' => true,'rewrite' => array('with_front' => false, 'hierarchical' => false) ) );
		register_taxonomy('city', array('property'), array('hierarchical' => TRUE,'label' => __('Cities'), 'public' => TRUE,'show_ui' => TRUE,'query_var' => true,'rewrite' => array('with_front' => false, 'hierarchical' => false) ) );
		register_taxonomy('neighborhood', array('property'), array('hierarchical' => TRUE,'label' => __('Neighborhoods'), 'public' => TRUE,'show_ui' => TRUE,'query_var' => true,'rewrite' => array('with_front' => false, 'hierarchical' => false) ) );
		register_taxonomy('street', array('property'), array('hierarchical' => TRUE,'label' => __('Streets'), 'public' => TRUE,'show_ui' => TRUE,'query_var' => true,'rewrite' => array('with_front' => false, 'hierarchical' => false) ) );
		
		register_taxonomy('beds', 'property', array('hierarchical' => TRUE,'label' => __('Beds'), 'public' => TRUE,'show_ui' => TRUE,'query_var' => true,'rewrite' => true ) );
		register_taxonomy('baths', 'property', array('hierarchical' => TRUE,'label' => __('Baths'), 'public' => TRUE,'show_ui' => TRUE,'query_var' => true,'rewrite' => true ) );
		register_taxonomy('half-baths', 'property', array('hierarchical' => TRUE,'label' => __('Half-baths'), 'public' => TRUE,'show_ui' => TRUE,'query_var' => true,'rewrite' => true ) );
		register_taxonomy('mlsid', 'property', array('hierarchical' => TRUE,'label' => __('MLS ID'), 'public' => TRUE,'show_ui' => TRUE,'query_var' => true,'rewrite' => true ) );
	}

	public static function ajax_polygon_listings () {
		if (isset($_POST['polygon'])) {
			$polygon = $_POST['polygon'];
			if (!empty($polygon)) {
				$api_listings = self::polygon_listings($polygon, $_POST);
				$response = $api_listings['listings'];
				echo json_encode($response);
			}
		}
		die();
	}

	public static function get_polygon_links () {
		$polygons = PL_Option_Helper::get_polygons();
		foreach ($polygons as $key => $value) {
			$polygons[$key]['url'] = get_term_link($value['slug'], $value['tax']);
		}
		return $polygons;
	}

	public static function get_listings_polygon_name ($params) {
		$polygons = PL_Option_Helper::get_polygons();
		$neighborhood_polygons = preg_replace("/[\\\\]+'/", "\\\\'", $params['neighborhood_polygons']);
		foreach ($polygons as $polygon) {
			if ($polygon['name'] == $neighborhood_polygons) {
				return self::polygon_listings($polygon['vertices'], $params);
			}
		}
	}

	public static function get_preset_polygon_styles () {
		$preset_styes = array(
				'Red' => array(
					'border-weight' => 3,
					'border-opacity' => 1,
					'fill-opacity' => 0.3,
					'polygon_border' => '#FF0000',
					'polygon_fill' => '#FF0000'
					),
				'Blue' => array(
					'border-weight' => 3,
					'border-opacity' => 1,
					'fill-opacity' => 0.3,
					'polygon_border' => '#0d2f94',
					'polygon_fill' => '#0d2f94'
					),
				'Green' => array(
					'border-weight' => 3,
					'border-opacity' => 1,
					'fill-opacity' => 0.3,
					'polygon_border' => '#34940d',
					'polygon_fill' => '#34940d'
					),
				'Yellow' => array(
					'border-weight' => 3,
					'border-opacity' => 1,
					'fill-opacity' => 0.3,
					'polygon_border' => '#d9ff00',
					'polygon_fill' => '#d9ff00'
					),
				'Orange' => array(
					'border-weight' => 3,
					'border-opacity' => 1,
					'fill-opacity' => 0.3,
					'polygon_border' => '#d97b09',
					'polygon_fill' => '#d97b09'
					),
				'Pink' => array(
					'border-weight' => 3,
					'border-opacity' => 1,
					'fill-opacity' => 0.3,
					'polygon_border' => '#c809d9',
					'polygon_fill' => '#c809d9'
					),
				'Teal' => array(
					'border-weight' => 3,
					'border-opacity' => 1,
					'fill-opacity' => 0.3,
					'polygon_border' => '#09d9c8',
					'polygon_fill' => '#09d9c8'
					)
			);
		$options = '';
		foreach ($preset_styes as $style_name => $presets) {
			$options .= '<option ';
			foreach ($presets as $style => $value) {
				$options .= ' data-' . $style . '="'.$value.'"';
			}
			$options .= ' >' . $style_name . '</option>';
		}
		$options .= '<option value="custom">Custom</option>';
		return $options;
	}

	public static function polygon_listings ($polygon, $additional_params = array()) {
		$request = '';
		foreach ($polygon as $key => $point) {
			$request .= 'polygon['.$key. '][lat]=' . $point['lat'] .'&';
			$request .= 'polygon['.$key .'][lng]=' . $point['lng'] .'&';
		}
		$request = wp_parse_args($request, $additional_params);
		return PL_Listing_Helper::results($request);
	}

	public static function lifestyle_polygon () {
		$request = wp_parse_args(wp_kses_post($_POST), array('location' => '', 'radius' => '', 'types' => ''));
		$places_response = PL_Google_Places_Helper::search($request);
		$points = array();
		foreach ($places_response as $place) {
			$points[] = array($place['geometry']['location']['lat'], $place['geometry']['location']['lng']);
		}
		if (!empty($points)) {
			$hull_response = self::find_hull($points, array('include_listings' => true));
		} else {
			$hull_response = array();
		}
		$response = array_merge($hull_response, array('places' => $places_response));
		echo json_encode($response);
		die();
	}

	public static function find_hull ($points = array(), $settings = array()) {
		extract(wp_parse_args($settings, array('include_listings' => false)));
		$response = array();
		if (!empty($points)) {
			$hull = new ConvexHull( $points );
			$response['polygon'] = $hull->getHullPoints();
		} else {
			$response['polygon'] = array();
		}
		if ($include_listings) {
			if (!empty($response['polygon'])) {
				$request = '';
				foreach ($response['polygon'] as $key => $point) {
					$request .= 'polygon['.$key. '][0]=' . $point[0] .'&';
					$request .= 'polygon['.$key .'][1]=' . $point[1] .'&';
				}
			}
			$api_listings = PL_Listing_Helper::results($request);
			$response['listings'] = $api_listings['listings'];
		}
		return $response;
	}

	public static function update_polygon () {
		PL_Option_Helper::set_polygons(array(), (int)$_POST['id']);
		self::save_polygon();
	}

	public static function save_polygon () {
		$polygon = array();
		$polygon['name'] = $_POST['name'];
		$polygon['tax'] = $_POST['tax'];
		$polygon['slug'] = $_POST['slug'];
		$polygon['settings'] = $_POST['settings'];
		$polygon['vertices'] = $_POST['vertices'];
		if (isset($_POST['create_taxonomy'])) {
			$id = wp_insert_term($_POST['create_taxonomy'], $polygon['tax']);
			if (is_array($id)) {
				$term = get_term($id['term_id'], $polygon['tax']);
				$polygon['slug'] = $term->slug;
			} else if ( is_wp_error($id) ) {
				$existing_term_id = $id->get_error_data();
				$term = get_term($existing_term_id, $polygon['tax']);
				$polygon['slug'] = $term->slug;
			}
		}
		$response = PL_Option_Helper::set_polygons($polygon);
		if ($response) {
			echo json_encode(array('response' => true, 'message' => 'Polygon successfully saved. Updating list...'));	
		} else {
			echo json_encode(array('response' => false, 'message' => 'There was an error. Please try again.'));	
		}
		die();	
	}

	public static function delete_polygon () {
		$response = PL_Option_Helper::set_polygons(array(), (int)$_POST['id']);
		echo json_encode($response);
		die();		
	}

	public static function get_polygon () {
		$polygons = PL_Option_Helper::get_polygons();
		if ($polygons[$_POST['id']]) {
			$polygons[$_POST['id']]['id'] = $_POST['id'];
			echo json_encode(array('result' => true, 'polygon' => $polygons[$_POST['id']]));
		} else {
			echo json_encode(array('result' => false, 'message' => 'There was an error. Please try again.'));
		}
		die();
	}

	public static function ajax_polygons_as_items () {
		echo json_encode(self::polygons_as_items());
		die();
	}

	public static function get_polygons_datatable () {
		$raw_polygons = PL_Option_Helper::get_polygons();
		// pls_dump($raw_polygons);
		$polygons = array();
		$start = 0;
		foreach ($raw_polygons as $key => $polygon) {
			$polygons[$start][] = $polygon['name'];
			$polygons[$start][] = $polygon['tax'];
			$polygons[$start][] = $polygon['slug'];
			$polygons[$start][] = '<a id="edit_item" class="' . $key . '" href="#">(Edit)</a><input type="hidden" name="id" value="' . $key . '" id="id">';
			$polygons[$start][] = '<a id="remove_item" class="' . $key . '" href="#">(Remove)</a><input type="hidden" name="id" value="' . $key . '" id="id">';
			$start++;
		}

		// Required for datatables.js to function properly.
		// $response['sEcho'] = $_POST['sEcho'];
		$response['aaData'] = $polygons;
		$response['iTotalRecords'] = count($polygons);
		$response['iTotalDisplayRecords'] = count($polygons);
		echo json_encode($response);
		die();
	}

	public static function get_polygons_by_type ($type = false) {
		if (!$type) {
			$type = $_POST['type'];
		}
		$response = array();
		$polygons = PL_Option_Helper::get_polygons();
		foreach ($polygons as $key => $polygon) {
			$polygon['permalink'] = get_term_link( $polygon['slug'], $polygon['tax'] );
			if ($polygon['tax'] == $type) {
				$response[] = $polygon;
			}
		}
		return $response;
	}

	public static function ajax_get_polygons_by_slug () {
		echo json_encode(self::get_polygons_by_slug($_POST['slug']));
		die();
	}

	public static function get_polygons_by_slug ($slug = false) {
		$response = array();
		$polygons = PL_Option_Helper::get_polygons();
		foreach ($polygons as $key => $polygon) {
			$polygon['permalink'] = get_term_link( $polygon['slug'], $polygon['tax'] );
			if ($polygon['slug'] == $slug) {
				$response[] = $polygon;
			}
		}
		return $response;
	}

	public static function get_polygon_detail ($args = array()) {
		extract(wp_parse_args($args, array('tax' => false, 'slug' => false)));
		$response = array();
		$polygons = PL_Option_Helper::get_polygons();
		if ($slug && $tax) {
			foreach ($polygons as $key => $polygon) {
				if ($polygon['slug'] == $slug && $polygon['tax'] == $tax) {
					$polygon['permalink'] = get_term_link( $polygon['slug'], $polygon['tax'] );
					return $polygon;
				}
			}
		}
		return array();
	}

	public static function ajax_get_polygons_by_type ($type = false) {
		echo json_encode(self::get_polygons_by_type($type));
		die();
	}

	public static function types_as_selects () {
		$taxonomies = self::get_taxonomies();
		ob_start();
		?>
		<select id="poly_taxonomies" name="poly_taxonomies" >
			<?php foreach ($taxonomies as $slug => $label): ?>
				<option value="<?php echo $slug ?>"><?php echo $label ?></option>
			<?php endforeach ?>
		</select>
		<?php
		return ob_get_clean();
	}

	public static function taxonomies_as_selects () {
		$taxonomies = self::get_taxonomies();
		ob_start();
		?>
		<?php foreach ($taxonomies as $slug => $label): ?>
			<select class="poly_taxonmy_values" name="<?php echo $slug ?>" style="display: none;" id="<?php echo $slug ?>">
				<?php foreach (self::get_taxonomy_items($slug, array('hide_empty' => false)) as $item): ?>
					<option value="<?php echo $item['slug'] ?>"><?php echo $item['name'] ?></option>
				<?php endforeach ?>
				<option value="custom">Create New Area</option>
			</select>
		<?php endforeach ?>
		<?php
		return ob_get_clean();
	}

	public static function taxonomies_as_checkboxes () {
		$taxonomies = self::get_taxonomies();
		ob_start();
		?>
			<form>
				<?php foreach ($taxonomies as $slug => $label): ?>
					<section>
						<input type="radio" id="<?php echo $slug ?>" name="type" value="<?php echo $slug ?>">
						<label for="<?php echo $slug ?>"><?php echo $label ?></label>
					</section>
				<?php endforeach ?>	
			</form>		
		<?php
		return ob_get_clean();
	}

	public static function get_taxonomies () {
		return self::$location_taxonomies;
	}

	public static function get_taxonomy_items ($tax, $args = array()) {
		$terms = get_terms( $tax, $args );
		$response = array();
		foreach ($terms as $key => $term) {
			foreach ($term as $term_key => $term_value) {
				$response[$key][$term_key] = $term_value;
			}
			$response[$key]['permalink'] = get_term_link($response[$key]['slug'], $tax);	
		}
		return $response;
		// pls_dump($response);
	}

	/**
	 * Retrieves all terms for an object & caches them for subsequent requests.
	 * @param int $obj_id Object ID (post ID)
	 * @param string $taxonomy name of the taxonomy to return
	 * @param string $default Value returned if no matching taxonomy found
	 * @return string
	 */
	private static function get_obj_term ($obj_id, $taxonomy, $default) {
		
		static $cache = array();

		if(isset($cache[$obj_id])) {
			$terms_for_obj = $cache[$obj_id];
		}
		else {
			$terms_for_obj = wp_get_object_terms($obj_id, self::$all_loc_taxonomies);
			$cache[$obj_id] = $terms_for_obj;
		}

		foreach($terms_for_obj as $term) {
			if($term->taxonomy == $taxonomy) {
				return $term->slug;
			}
		}

		// Not found
		return $default;
	}

	public static function get_property_permalink ($permalink, $post_id, $leavename) {
		$post = get_post($post_id);
		$state = '';
		$zip = '';
		$city = '';
		$street = '';
		$neighborhood = '';
        $rewritecode = array('%state%','%city%','%zip%','%neighborhood%','%street%', $leavename ? '' : '%postname%', $leavename ? '' : '%pagename%', $leavename ? '' : '%pagename%');
        
        if ( !empty($permalink) && $post->post_type == 'property' && !in_array($post->post_status, array('draft', 'pending', 'auto-draft')) ) {
            if (strpos($permalink, '%state%')) {
            	$state = self::get_obj_term($post->ID, 'state', 'state');
            }

            if (strpos($permalink, '%zip%')) {
            	$zip = self::get_obj_term($post->ID, 'zip', 'zip');
            }

	        if (strpos($permalink, '%city%')){
            	$city = self::get_obj_term($post->ID, 'city', 'city');
	        } 

	        if (strpos($permalink, '%neighborhood%')){
	        	$neighborhood = self::get_obj_term($post->ID, 'neighborhood', 'neighborhood');
	        } 

	        if (strpos($permalink, '%street%')){
	        	$street = self::get_obj_term($post->ID, 'street', 'street');
	        }           

	        $rewritereplace = array( $state, $city, $zip, $neighborhood, $street, $post->post_name, $post->post_name, $post->post_name, $post->post_name, $post->post_name);
	        $permalink = str_replace($rewritecode, $rewritereplace, $permalink);
        }
        return $permalink;
	}

	public static function create ($taxonomies) {
      	foreach ($taxonomies as $taxonomy) {
      		if ( !taxonomy_exists( $taxonomy['taxonomy_name'] ) ) {
      			return false;
      		}

            // create terms in taxonomy
      		foreach ($taxonomy['terms'] as $term) {
                wp_insert_term( $term['term_name'], $taxonomy['taxonomy_name'], $term['args'] );
      		}
      	}
    }
}