<?php 
class ModelSettingLucidCurrency extends Model {
	public function getSetting() {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "currency_order ORDER BY currency_id ASC");

		return $query->rows;
	}
	
	public function editSetting($data) {
		foreach ($data as $currency) {
			foreach ($currency as $currency_id => $sort_order) {
				$this->db->query("UPDATE " . DB_PREFIX . "currency_order SET sort_order = '" . (int)$sort_order . "' WHERE currency_id = '" . (int)$currency_id . "'");
			}
		}
		$query = $this->db->query("SELECT pp.product_id, pp.price, c.currency_id, c.value, co.sort_order FROM " . DB_PREFIX . "product_price pp INNER JOIN " . DB_PREFIX . "currency c ON pp.currency_id = c.currency_id INNER JOIN " . DB_PREFIX . "currency_order co ON c.currency_id = co.currency_id WHERE pp.price > 0 ORDER BY co.sort_order DESC, c.currency_id DESC");

		foreach ($query->rows as $product) {
			$price = (float)$product['price'] / (float)$product['value'];
			$this->db->query("UPDATE " . DB_PREFIX . "product SET price = '" . (float)$price . "' WHERE product_id = '" . (int)$product['product_id'] . "'");
		}

		$this->cache->delete('product');
	}

	public function checkLucidCurrency() {
		$this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "product_price (product_id int(11) NOT NULL, currency_id int(11) NOT NULL, price decimal(15,4) NOT NULL, PRIMARY KEY (product_id, currency_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8");
		$this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "currency_order (currency_id int(11) NOT NULL, title varchar(32) NOT NULL, sort_order int(3) NOT NULL, PRIMARY KEY (currency_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8");
		$result = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `key` LIKE 'config_currency'");
		$skip = ' ';
		if (empty($result->row['value'])){
			$skip = $result->row['value'];
		}
		$result = $this->db->query("SELECT * FROM " . DB_PREFIX . "currency WHERE code != '" . $this->db->escape($skip) . "'");
              if (!empty($result->rows)) {
			foreach ($result->rows as $currency) {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "currency_order WHERE currency_id = '" . (int)$currency['currency_id'] . "'");
				if (empty($query->row)) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "currency_order SET currency_id = '" . (int)$currency['currency_id'] . "', title = '" . $this->db->escape($currency['title']) . "'");
				}
			}
		}

	}

}