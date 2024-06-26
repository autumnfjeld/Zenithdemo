<?php
/**
 * Start REST route
 *
 * @package automattic/jetpack-import
 */

namespace Automattic\Jetpack\Import\Endpoints;

use Automattic\Jetpack\Import\Main;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class Start
 *
 * This class is used to start the import process.
 */
class Start extends \WP_REST_Controller {

	/**
	 * Base class
	 */
	use Import;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->rest_base = 'start';
	}

	/**
	 * Get the register route options.
	 *
	 * @see register_rest_route()
	 *
	 * @return array The options.
	 */
	protected function get_route_options() {
		return array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'import_permissions_callback' ),
				'args'                => array(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		);
	}

	/**
	 * Retrieves main informations.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$items = array(
			'max_batch_items'    => apply_filters( 'rest_get_max_batch_size', 25 ),
			'max_execution_time' => (int) ini_get( 'max_execution_time' ),
			'max_input_time'     => (int) ini_get( 'max_input_time' ),
			'mime_types'         => get_allowed_mime_types(),
			'posts_max_id'       => (int) $this->get_posts_max_id(),
			'version'            => Main::PACKAGE_VERSION,
		);

		$response = array();

		foreach ( $items as $name => $value ) {
			/**
			 * Filters the value of a item recognized by the REST API.
			 */
			$response[ $name ] = apply_filters( 'jetpack_import_rest_get_start', $value, $name, $request );
		}

		return $response;
	}

	/**
	 * Retrieves the start values schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'import-start',
			'type'       => 'object',
			'properties' => array(
				'max_batch_items'    => array(
					'description' => __( 'Max batch size.', 'jetpack-import' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'max_execution_time' => array(
					'description' => __( 'Max execution time.', 'jetpack-import' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'max_input_time'     => array(
					'description' => __( 'Max execution input time.', 'jetpack-import' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'mime_types'         => array(
					'description' => __( 'Upload accepted mime types.', 'jetpack-import' ),
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'posts_max_id'       => array(
					'description' => __( 'Last posts autogenerated ID.', 'jetpack-import' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'version'            => array(
					'description' => __( 'Version of the import package.', 'jetpack-import' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Get the last posts autogenerated ID.
	 */
	private function get_posts_max_id() {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$max_id = $wpdb->get_var( "SELECT MAX(ID) FROM $wpdb->posts" );

		return $max_id;
	}
}
