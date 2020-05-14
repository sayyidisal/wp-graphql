<?php
namespace WPGraphQL\Data\Connection;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class EnqueuedScriptsConnectionResolver
 *
 * @package WPGraphQL\Data\Connection
 */
class EnqueuedScriptsConnectionResolver extends AbstractConnectionResolver {

	/**
	 * EnqueuedScriptsConnectionResolver constructor.
	 *
	 * @param $source
	 * @param $args
	 * @param $context
	 * @param $info
	 *
	 * @throws \Exception
	 */
	public function __construct( $source, $args, $context, $info ) {

		/**
		 * Filter the query amount to be 1000 for
		 */
		add_filter( 'graphql_connection_max_query_amount', function( $max, $source, $args, $context, ResolveInfo $info ) {
			if ( $info->fieldName === 'enqueuedScripts' || $info->fieldName === 'registeredScripts' ) {
				return 1000;
			}
			return $max;
		}, 10, 5 );

		parent::__construct( $source, $args, $context, $info );
	}

	public function get_offset() {
		$offset = null;
		if ( ! empty( $this->args['after'] ) ) {
			$offset = substr(base64_decode( $this->args['after'] ), strlen('arrayconnection:' ));
		} elseif ( ! empty( $this->args['before'] ) ) {
			$offset = substr(base64_decode( $this->args['before'] ), strlen('arrayconnection:' ));
		}
		return $offset;
	}

	/**
	 * Get the IDs from the source
	 *
	 * @return array|mixed|null
	 */
	public function get_ids() {
		$ids = [];
		$queried = $this->get_query();

		if ( empty( $queried ) ) {
			return $ids;
		}

		foreach ( $queried as $key => $item ) {
			$ids[ $key ] = $item;
		}

		return $ids;

	}

	/**
	 * @return array|void
	 */
	public function get_query_args() {
		// If any args are added to filter/sort the connection
	}


	/**
	 * Get the items from the source
	 *
	 * @return array|mixed|null
	 */
	public function get_query() {
		return $this->source->enqueuedScriptsQueue ?? [];
	}

	/**
	 * Load an individual node by ID
	 *
	 * @param $id
	 *
	 * @return mixed|null|\WPGraphQL\Model\Model
	 * @throws \Exception
	 */
	public function get_node_by_id( $id ) {
		return $this->loader->load( $id );
	}

	/**
	 * get_nodes
	 *
	 * Get the nodes from the query.
	 *
	 * We slice the array to match the amount of items that was asked for, as we over-fetched
	 * by 1 item to calculate pageInfo.
	 *
	 * For backward pagination, we reverse the order of nodes.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function get_nodes() {

		$nodes = parent::get_nodes();

		if ( isset( $this->args['after'] ) ) {
			$key = array_search( $this->get_offset(), array_keys( $nodes ), true );
			$nodes = array_slice( $nodes, $key + 1, null, true );
		}

		if ( isset( $this->args['before'] ) ) {
			$nodes = array_reverse( $nodes );
			$key = array_search( $this->get_offset(), array_keys( $nodes ), true );
			$nodes = array_slice( $nodes, $key + 1, null, true );
			$nodes = array_reverse( $nodes );
		}

		$nodes = array_slice( $nodes, 0, $this->query_amount, true );

		return ! empty( $this->args['last'] ) ? array_filter( array_reverse( $nodes, true ) ) : $nodes;
	}

	/**
	 * The name of the loader to load the data
	 *
	 * @return string
	 */
	public function get_loader_name() {
		return 'enqueued_script';
	}

	/**
	 * Determine if the model is valid
	 *
	 * @param array $model
	 *
	 * @return bool
	 */
	protected function is_valid_model( $model ) {
		return isset( $model->handle ) ?? false;
	}

	/**
	 * Determine if the offset used for pagination is valid
	 *
	 * @param $offset
	 *
	 * @return bool
	 */
	public function is_valid_offset( $offset ) {
		return true;
	}

	/**
	 * Determine if the query should execute
	 *
	 * @return bool
	 */
	public function should_execute() {
		return true;
	}

}
