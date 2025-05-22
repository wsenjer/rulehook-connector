<?php

namespace RuleHook\Core\App\Endpoints\Services;

class Categories {

	public function get_product_categories()
	{
		$categories_query = get_terms( [
			'orderby' => 'name',
			'order'   => 'ASC',
			'hide_empty' => false,
			'taxonomy' => 'product_cat',
		]);

		$categories = [];
		foreach ($categories_query as $category) {
			$categories[] = [
				'id' => $category->term_id,
				'name' => $category->name,
			];
		}

		return $categories;

	}

	public function get_product_tags()
	{
		$categories_query = get_terms( [
			'orderby' => 'name',
			'order'   => 'ASC',
			'hide_empty' => false,
			'taxonomy' => 'product_tag',
		]);

		$categories = [];
		foreach ($categories_query as $category) {
			$categories[] = [
				'id' => $category->term_id,
				'name' => $category->name,
			];
		}

		return $categories;

	}

	public function get_product_attributes()
	{
		$product_attributes   = [];
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		foreach ( $attribute_taxonomies as $attribute ) {
			$product_attributes[] = [
				'id' => $attribute->attribute_id,
				'name' => $attribute->attribute_name,
			];
		}
		return $product_attributes;

	}


	public function get_shipping_classes()
	{
		$categories_query = get_terms( [
			'orderby' => 'name',
			'order'   => 'ASC',
			'hide_empty' => false,
			'taxonomy' => 'product_shipping_class',
		]);

		$categories = [];
		foreach ($categories_query as $category) {
			$categories[] = [
				'id' => $category->term_id,
				'name' => $category->name,
			];
		}

		return $categories;

	}

	public function get_coupons()
	{
		$coupons_query = get_posts( [
			'orderby' => 'name',
			'order'   => 'ASC',
			'post_type' => 'shop_coupon',
		]);

		$coupons = [];
		foreach ($coupons_query as $coupon) {
			$coupons[] = [
				'id' => $coupon->ID,
				'name' => $coupon->post_title,
			];
		}

		return $coupons;

	}

	public function get_product_stock_status() :array
	{
		$options = wc_get_product_stock_status_options();
		$status = [];
		foreach ($options as $key => $option) {
			$status[] = [
				'key' => $key,
				'name' => $option
			];
		}

		return $status;
	}

	private function get_attribute_terms( string $attribute_name ): array
	{
		$terms_query = get_terms( [
			'orderby' => 'name',
			'order'   => 'ASC',
			'hide_empty' => false,
			'taxonomy' => sprintf('pa_%s', $attribute_name),
		]);

		$terms = [];
		foreach ($terms_query as $term) {
			$terms[] = [
				'id' => $term->term_id,
				'name' => $term->slug,
			];
		}

		return $terms;
	}

	public function get_product_attributes_terms( array $product_attributes ) {
		$terms = [];
		foreach ($product_attributes as $product_attribute) {
			$terms[$product_attribute['name']] = $this->get_attribute_terms($product_attribute['name']);
		}

		return $terms;
	}

}
