<?php
namespace elasticsearch;

class Faceting{
	static function all($minFont = 12, $maxFont = 24){
		$options = array();

		foreach(Api::taxonomies() as $tax){
			$options[$tax] = self::taxonomy($tax);
		}

		$numeric = Api::option('numeric');

		foreach(Api::fields() as $field){
			if($numeric[$field]){
				$options[$field] = self::range($field);
			}
		}

		foreach($options as $name => &$field){
			foreach($field['available'] as &$available){
				$available['font'] = self::cloud($field['available'], $available, $minFont, $maxFont);
			}
		}

		return $options;
	}

	static function range($field){
		global $wp_query;

		$facets = $wp_query->facets;

		$result = array(
			'selected' => array(),
			'available' => array(),
			'total' => 0
		);

		$ranges = Api::ranges($field);

		if($ranges){
			foreach($ranges as $slug => $range){
				$split = split('-', $slug);

				$item = array(
					'slug' => $slug,
					'count' => $facets[$field][$slug],
					'to' => $split[1],
					'from' => $split[0]
				);

				if(in_array($slug, $_GET[$field]['and'])){
					$result['selected'][$slug] = $item;
				}else{
					$result['available'][$slug] = $item;
					$result['total'] += $item['count'];
				}
			}
		}

		return $result;
	}

	static function taxonomy($tax){
		global $wp_query;

		$facets = $wp_query->facets;

		$taxonomy = array(
			'selected' => array(),
			'available' => array(),
			'total' => 0
		);

		if(isset($facets[$tax])){
			foreach(get_terms($tax) as $term){
				$item = array(
					'name' => $term->name ?: $term->slug,
					'slug' => $term->slug
				);

				if(in_array($term->slug, $_GET[$tax]['and'])){
					$taxonomy['selected'][$term->slug] = $item;
				}else if($item['count'] = $facets[$tax][$term->slug]){
					$taxonomy['available'][$term->slug] = $item;
					$taxonomy['total'] += $item['count'];
				}
			}
		}

		return $taxonomy;
	}

	static function cloud($items, $item, $min = 12, $max = 24){
		$maxTotal = 0;

		foreach($items as $itm){
			if(log($itm['count']) > $maxTotal){
				$maxTotal = log($itm['count']);
			}
		}

		return floor((log($item['count']) / $maxTotal) * ($max - $min) + $min);
	}

	static function urlAdd($url, $type, $value, $operation = 'and'){
		$filter = $_GET;
		$filter[$type][$operation][] = $value;

		$newurl = http_build_url($url, array(
			'query' => http_build_str($filter)
		));

		return $newurl;
	}

	static function urlRemove($url, $type, $value, $operation = 'and'){
		$filter = $_GET;
		$index = array_search($value, $filter[$type][$operation]);

		unset($filter[$type][$operation][$index]);

		if(count($filter[$type][$operation]) == 0){
			unset($filter[$type][$operation]);
		}

		if(count($filter[$type]) == 0){
			unset($filter[$type]);
		}

		$newurl = http_build_url($url, array(
			'query' => http_build_str($filter) . '#'
		));

		return $newurl;
	}
}

?>