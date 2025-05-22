<?php

namespace RuleHook\Core\App\Endpoints;

class Find_Product_Endpoint extends Abstract_Endpoint {

	public function callback( $data ) {
		if (!isset($data['search'])) {
			$this->abort('[search] field is required');
		}

		$data['search'] = sanitize_text_field($data['search']);

		$args = [
			's'              => $data['search'],
			'post_type'      => 'product',
			'orderby'        => 'title',
			'posts_per_page' => 10
		];

		$query = new \WP_Query($args);
		$results = [];
		while ( $query->have_posts() ) {
			$query->the_post();
			$product = wc_get_product(intval($query->post->ID));
			$product_result = [
				'code'  => $product->get_id(),
				'label' => sprintf('#%d %s', $product->get_id(),$product->get_name()),

			];

			$results[] = $product_result;
		}

		return ['products' => $results];
	}

	public function action() {
		return 'str_find_product';
	}
}
