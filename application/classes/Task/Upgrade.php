<?php

class Task_Upgrade extends Minion_Task {

    protected function _execute(array $params) {
        $db = Database::instance();
        $prefix = $db->table_prefix();
        $tables = $db->list_tables();

        $table_name = $prefix . 'images';
        if (!in_array($table_name, $tables)) {
            Minion_CLI::write("Creating table $table_name");
            $sql = 'CREATE TABLE ' . $table_name . ' (
                  id int(15) NOT NULL AUTO_INCREMENT,
                  date_and_time datetime NOT NULL,
                  caption text NOT NULL,
                  auth_level_id int(2) NOT NULL DEFAULT 5,
                  author_id int(5) NOT NULL DEFAULT 1,
                  licence_id int(2) NOT NULL DEFAULT 1,
                  PRIMARY KEY (id),
                  KEY auth_level (auth_level_id),
                  KEY author_id (author_id),
                  KEY licence_id (licence_id)
                ) ENGINE=InnoDB;';
            $db->query(NULL, $sql);
        }
        
        


CREATE TABLE IF NOT EXISTS journal_entries (
  id int(5) NOT NULL AUTO_INCREMENT,
  date_and_time datetime NOT NULL,
  title varchar(150) DEFAULT NULL,
  auth_level_id int(2) NOT NULL,
  entry_text text NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY title (title),
  KEY auth_level (auth_level_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS emails (
  id int(10) NOT NULL AUTO_INCREMENT,
  to_id int(11) NOT NULL,
  from_id int(11) NOT NULL,
  date_and_time datetime DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  message_body text,
  PRIMARY KEY (id),
  KEY to_id (to_id),
  KEY from_id (from_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS people (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(150) NOT NULL UNIQUE KEY,
  email_address varchar(100) NOT NULL,
  notes text NOT NULL,
  auth_level_id int(2) NOT NULL DEFAULT '0',
  openid_identity varchar(250) DEFAULT NULL UNIQUE KEY,
  KEY auth_level (auth_level_id)
) ENGINE=InnoDB;

/* Supporting tables */

CREATE TABLE IF NOT EXISTS auth_levels (
  id int(3) NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB;
INSERT INTO auth_levels (id, `name`) VALUES
(1, 'Open'),
(5, 'Restricted'),
(10, 'Closed');

CREATE TABLE IF NOT EXISTS licences (
  id int(2) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  link_url varchar(300) DEFAULT NULL,
  image_url varchar(300) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY link_url (link_url),
  UNIQUE KEY image_url (image_url)
) ENGINE=InnoDB;
INSERT INTO licences (id, `name`, link_url, image_url) VALUES
(1, 'Creative Commons Attribution 3.0', 'http://creativecommons.org/licenses/by/3.0/', 'http://i.creativecommons.org/l/by/3.0/80x15.png'),
(2, 'Public Domain (CC0)', 'http://creativecommons.org/publicdomain/zero/1.0/', 'http://i.creativecommons.org/p/zero/1.0/80x15.png'),
(3, 'Copyright', NULL, NULL);

CREATE TABLE IF NOT EXISTS tags (
    id int(15) NOT NULL AUTO_INCREMENT,
    `name` varchar(200) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS image_tags (
    image_id int(15) NOT NULL,
    tag_id int(15) NOT NULL,
    PRIMARY KEY (image_id, tag_id),
    KEY image (image_id),
    KEY tag (tag_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS journal_entry_tags (
    journal_entry_id int(5) NOT NULL,
    tag_id int(15) NOT NULL,
    PRIMARY KEY (journal_entry_id, tag_id),
    KEY journal_entry (journal_entry_id),
    KEY tag (tag_id)
) ENGINE=InnoDB;

/* Join everything together */

ALTER TABLE `emails`
  ADD CONSTRAINT email_to FOREIGN KEY (to_id) REFERENCES people (id),
  ADD CONSTRAINT email_from FOREIGN KEY (from_id) REFERENCES people (id);

ALTER TABLE `images`
  ADD CONSTRAINT image_auth_level FOREIGN KEY (auth_level_id) REFERENCES auth_levels (id),
  ADD CONSTRAINT image_author FOREIGN KEY (author_id) REFERENCES people (id),
  ADD CONSTRAINT image_licence FOREIGN KEY (licence_id) REFERENCES licences (id);

ALTER TABLE `journal_entries`
  ADD CONSTRAINT journal_entry_auth_level FOREIGN KEY (auth_level_id) REFERENCES auth_levels (id);

ALTER TABLE `people`
  ADD CONSTRAINT people_auth_level FOREIGN KEY (auth_level_id) REFERENCES auth_levels (id);

ALTER TABLE `image_tags`
  ADD CONSTRAINT image_tag FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE,
  ADD CONSTRAINT image FOREIGN KEY (image_id) REFERENCES images (id) ON DELETE CASCADE;

ALTER TABLE `journal_entry_tags`
  ADD CONSTRAINT journal_entry_tag FOREIGN KEY (tag_id) REFERENCES tags (id),
  ADD CONSTRAINT journal_entry FOREIGN KEY (journal_entry_id) REFERENCES journal_entries (id);



    }

}
