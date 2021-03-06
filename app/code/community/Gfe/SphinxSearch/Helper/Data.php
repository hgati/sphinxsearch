<?php

class Gfe_SphinxSearch_Helper_Data extends Mage_Core_Helper_Abstract {

	public function getSphinxAdapter() {

		$host = Mage::getStoreConfig('sphinxsearch/server/host', Mage::app()->getStore()->getStoreId());
		$port = Mage::getStoreConfig('sphinxsearch/server/port', Mage::app()->getStore()->getStoreId());

		if (empty($host)) return false;
		if (empty($port)) $port = 9312;

		$sphinx = new SphinxClient();
		$sphinx->setServer($host, $port);
		$sphinx->setMatchMode(SphinxClient::SPH_MATCH_EXTENDED2);

		$name_field_weight = (int)Mage::getStoreConfig('sphinxsearch/active/name_field_weight', Mage::app()->getStore()->getStoreId());
		$category_field_weight = (int)Mage::getStoreConfig('sphinxsearch/active/category_field_weight', Mage::app()->getStore()->getStoreId());
		$nameattributes_field_weight = (int)Mage::getStoreConfig('sphinxsearch/active/nameattributes_field_weight', Mage::app()->getStore()->getStoreId());
		$dataindex_field_weight = (int)Mage::getStoreConfig('sphinxsearch/active/dataindex_field_weight', Mage::app()->getStore()->getStoreId());

		$sphinx->setFieldWeights([
			'name'            => $name_field_weight,
			'category'        => $category_field_weight,
			'name_attributes' => $nameattributes_field_weight,
			'data_index'      => $dataindex_field_weight,
		]);
		$sphinx->setLimits(0, 200, 1000, 5000);
		// ranking mode description: http://sphinxsearch.com/docs/current/builtin-rankers.html
		// $sphinx->setRankingMode(SphinxClient::SPH_RANK_EXPR, '@count asc');
		$sphinx->setRankingMode(SphinxClient::SPH_RANK_SPH04, '');

		return $sphinx;
	}

	/**
	 * taken from https://gist.github.com/2727341
	 */
	public function prepareIndexdata($index, $separator = ' ', $entity_id = null) {
		$_attributes = [];

		$_index = [];
		foreach ($index as $key => $value) {

			// As long as this isn't a standard attribute use it in our
			// concatenated column.
			if (!in_array($key, ['sku', 'name', 'description', 'short_description', 'meta_keywords', 'meta_title'])) {
				$_attributes[ $key ] = $value;
			}

			if (!is_array($value)) {
				$_index[] = $value;
			} else {
				$_index = array_merge($_index, $value);
			}
		}

		// Get the product name.
		$name = '';
		if (isset($index['name'])) {
			if (is_array($index['name'])) {
				$name = $index['name'][ $entity_id ]; // Use the configurable product's name
			} else {
				$name = $index['name']; // Use the simple product's name
			}
		}

		// Combine the name with each non-standard attribute
		$name_attributes = [];
		foreach ($_attributes as $code => $value) {
			if (!is_array($value)) {
				$value = [$value];
			}

			// Loop through each simple product's attribute values and assign to
			// product name.
			foreach ($value as $key => $item_value) {
				if (isset($name_attributes[ $key ])) {
					$name_attributes[ $key ] .= ' ' . $item_value;
				} else {
					// The first time we see this add the name to start.
					$name_attributes[ $key ] = $name . ' ' . $item_value;
				}
			}
		}


		$data = [
			'name'            => $name,
			'name_attributes' => implode('. ', $name_attributes),
			'data_index'      => implode($separator, $_index),
			'category'        => implode('|', $index['categories']),
		];

		return $data;
	}

	public function getEngine() {
		return Mage::getResourceSingleton('sphinxsearch/fulltext_engine');
	}

}

?>
