<?php

class Task_Upgrade extends Minion_Task {

	protected function _execute(array $params) {
		File::check_directory(DATAPATH.'images/IN');

		$db = Database::instance();
		$prefix = $db->table_prefix();
		$tables = array();
		foreach ($db->query(Database::SELECT, 'SHOW TABLES')->as_array() as $t)
		{
			$tables[] = $t['Tables_in_twyne'];
		}

		$auth_levels_tbl = $prefix . 'auth_levels';
		if (!in_array($auth_levels_tbl, $tables)) {
			Minion_CLI::write("Creating table $auth_levels_tbl");
			$sql = 'CREATE TABLE '.$auth_levels_tbl.' (
				id int(3) NOT NULL,
				`name` varchar(50) NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY `name` (`name`)
				) ENGINE=InnoDB';
			$db->query(NULL, $sql);
			$sql = "INSERT INTO $auth_levels_tbl (id, `name`) VALUES
				(1, 'Open'),
				(5, 'Restricted'),
				(10, 'Closed')";
			$db->query(NULL, $sql);
		}

		$people_tbl = $prefix . 'people';
		if (!in_array($people_tbl, $tables)) {
			Minion_CLI::write("Creating table $people_tbl");
			$sql = 'CREATE TABLE ' . $people_tbl . ' (
				id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`name` varchar(150) NOT NULL UNIQUE KEY,
				email_address varchar(100) NOT NULL,
				notes text NOT NULL,
				auth_level_id int(3) NOT NULL DEFAULT 0,
				openid_identity varchar(250) DEFAULT NULL UNIQUE KEY,
				CONSTRAINT people_auth_level FOREIGN KEY (auth_level_id) REFERENCES '.$auth_levels_tbl.' (id)
				) ENGINE=InnoDB';
			$db->query(NULL, $sql);
		}

		$licences_tbl = $prefix . 'licences';
		if (!in_array($licences_tbl, $tables)) {
			Minion_CLI::write("Creating table $licences_tbl");
			$sql = 'CREATE TABLE '.$licences_tbl.' (
				id int(2) NOT NULL AUTO_INCREMENT,
				`name` varchar(60) NOT NULL,
				link_url varchar(300) DEFAULT NULL,
				image_url varchar(300) DEFAULT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY `name` (`name`),
				UNIQUE KEY link_url (link_url),
				UNIQUE KEY image_url (image_url)
			) ENGINE=InnoDB';
			$db->query(NULL, $sql);
			$sql = "INSERT INTO $licences_tbl (id, `name`, link_url, image_url) VALUES
				(1, 'Creative Commons Attribution 3.0', 'http://creativecommons.org/licenses/by/3.0/', 'http://i.creativecommons.org/l/by/3.0/80x15.png'),
				(2, 'Public Domain (CC0)', 'http://creativecommons.org/publicdomain/zero/1.0/', 'http://i.creativecommons.org/p/zero/1.0/80x15.png'),
				(3, 'Copyright', NULL, NULL);";
			$db->query(NULL, $sql);
		}

		$images_tbl = $prefix . 'images';
		if (!in_array($images_tbl, $tables)) {
			Minion_CLI::write("Creating table $images_tbl");
			$sql = 'CREATE TABLE ' . $images_tbl . ' (
					id int(15) NOT NULL AUTO_INCREMENT,
					date_and_time datetime NOT NULL,
					caption text NOT NULL,
					auth_level_id int(2) NOT NULL DEFAULT 5,
					author_id int(5) NOT NULL DEFAULT 1,
					licence_id int(2) NOT NULL DEFAULT 1,
					PRIMARY KEY (id),
					KEY auth_level (auth_level_id),
					KEY author_id (author_id),
					KEY licence_id (licence_id),
					CONSTRAINT image_auth_level FOREIGN KEY (auth_level_id) REFERENCES '.$auth_levels_tbl.' (id),
					CONSTRAINT image_author FOREIGN KEY (author_id) REFERENCES '.$people_tbl.' (id),
					CONSTRAINT image_licence FOREIGN KEY (licence_id) REFERENCES '.$licences_tbl.' (id)
					) ENGINE=InnoDB;';
			$db->query(NULL, $sql);
		}

		$tags_tbl = $prefix . 'tags';
		if (!in_array($tags_tbl, $tables)) {
			Minion_CLI::write("Creating table $tags_tbl");
			$sql = 'CREATE TABLE '.$tags_tbl.' (
				id int(15) NOT NULL AUTO_INCREMENT,
				`name` varchar(200) NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY `name` (`name`)
				) ENGINE = InnoDB;';
			$db->query(NULL, $sql);
		}

		$image_tags_tbl = $prefix . 'image_tags';
		if (!in_array($image_tags_tbl, $tables)) {
			Minion_CLI::write("Creating table $image_tags_tbl");
			$sql = 'CREATE TABLE '.$image_tags_tbl.' (
				image_id int(15) NOT NULL,
				tag_id int(15) NOT NULL,
				PRIMARY KEY (image_id, tag_id),
				KEY image (image_id),
				KEY tag (tag_id),
				CONSTRAINT image_tag FOREIGN KEY (tag_id) REFERENCES '.$tags_tbl.' (id) ON DELETE CASCADE,
				CONSTRAINT image FOREIGN KEY (image_id) REFERENCES '.$images_tbl.' (id) ON DELETE CASCADE
			) ENGINE=InnoDB';
			$db->query(NULL, $sql);
		}

		/**
		 * Add mime_type column to the images table
		 */
		$cols = $db->query(Database::SELECT, "DESCRIBE ".$db->quote_table($images_tbl))->as_array();
		$columns = array();
		foreach ($cols as $c) {
			$columns[] = $c['Field'];
		}
		if ( !in_array('mime_type', $columns))
		{
			Minion_CLI::write("Adding mime_type column to $images_tbl table");
			$sql = 'ALTER TABLE '.$images_tbl.' ADD COLUMN mime_type VARCHAR(65) NULL DEFAULT NULL';
			$db->query(NULL, $sql);
		}
		$fulls = scandir(DATAPATH.'images'.DIRECTORY_SEPARATOR.'full');
		foreach ($fulls as $f)
		{
			Minion_CLI::write("file: $f");
			if ($f[0]=='.')
			{
				continue;
			}
			$mime = File::mime(DATAPATH.'images/full/'.$f);
			var_dump($mime);
		}

	}

}
