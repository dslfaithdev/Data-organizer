DROP TABLE IF EXISTS message_tags CASCADE;
DROP TABLE IF EXISTS story_tags CASCADE;
DROP TABLE IF EXISTS with_tag CASCADE;
DROP TABLE IF EXISTS post CASCADE;
DROP TABLE IF EXISTS `comment` CASCADE;
DROP TABLE IF EXISTS likedby CASCADE;
DROP TABLE IF EXISTS application CASCADE;
DROP TABLE IF EXISTS fb_user CASCADE;
DROP TABLE IF EXISTS place CASCADE;
DROP TABLE IF EXISTS page CASCADE;

CREATE TABLE `page` (
  `id` bigint(20) NOT NULL,
  `name` text,
  `category` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `fb_user` (
  `id` bigint(20) NOT NULL,
  `name` text,
  `category` bit(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `post` (
  `id` bigint(20) NOT NULL DEFAULT '0',
  `page_id` bigint(20) NOT NULL DEFAULT '0',
  `from_id` bigint(20) NOT NULL,
  `message` text,
  `type` varchar(256) DEFAULT NULL,
  `picture` varchar(256) DEFAULT NULL,
  `story` text,
  `link` text,
  `link_name` text,
  `link_description` text,
  `link_caption` text,
  `icon` varchar(256) DEFAULT NULL,
  `created_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `can_remove` tinyint(1) DEFAULT NULL,
  `shares_count` int(11) DEFAULT NULL,
  `likes_count` int(11) DEFAULT NULL,
  `comments_count` int(11) DEFAULT NULL,
  `entr_pg` float DEFAULT NULL,
  `entr_ug` float DEFAULT NULL,
  `object_id` bigint(20) DEFAULT NULL,
  `status_type` varchar(256) DEFAULT NULL,
  `source` varchar(256) DEFAULT NULL,
  `is_hidden` tinyint(1) DEFAULT NULL,
  `application_id` bigint(20) DEFAULT NULL,
  `place_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`page_id`,`id`),
  KEY `post_id` (`id`),
  CONSTRAINT `post_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `application` (
  `id` bigint(20) NOT NULL DEFAULT '0',
  `name` varchar(256) DEFAULT NULL,
  `namespace` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `story_tags` (
  `id` bigint(20) DEFAULT NULL,
  `page_id` bigint(20) DEFAULT NULL,
  `post_id` bigint(20) DEFAULT NULL,
  `comment_id` bigint(20) DEFAULT NULL,
  `offset` int(11) DEFAULT NULL,
  `length` int(11) DEFAULT NULL,
  `type` varchar(256) DEFAULT NULL,
  `name` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `message_tags` (
  `id` bigint(20) DEFAULT NULL,
  `page_id` bigint(20) DEFAULT NULL,
  `post_id` bigint(20) DEFAULT NULL,
  `comment_id` bigint(20) DEFAULT NULL,
  `offset` int(11) DEFAULT NULL,
  `length` int(11) DEFAULT NULL,
  `type` varchar(256) DEFAULT NULL,
  `name` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `place` (
  `id` bigint(20) DEFAULT NULL,
  `name` varchar(256) DEFAULT NULL,
  `loc_latitude` float DEFAULT NULL,
  `loc_longitude` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `with_tag` (
  `page_id` bigint(20) DEFAULT NULL,
  `post_id` bigint(20) DEFAULT NULL,
  `fb_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`page_id`, `post_id`, `fb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `comment` (
  `id` bigint(20) NOT NULL DEFAULT '0',
  `post_id` bigint(20) NOT NULL DEFAULT '0',
  `page_id` bigint(20) NOT NULL DEFAULT '0',
  `fb_id` bigint(20) DEFAULT NULL,
  `message` text,
  `can_remove` tinyint(1) DEFAULT NULL,
  `created_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`page_id`,`post_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `likedby` (
  `page_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `comment_id` bigint(20) NOT NULL DEFAULT '0',
  `fb_id` bigint(20) NOT NULL,
  `created_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`page_id`,`post_id`,`comment_id`,`fb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `shares` (
  `id` bigint(20) unsigned NOT NULL,
  `post_id` bigint(20) unsigned NOT NULL,
  `who` bigint(20) unsigned NOT NULL,
  `created_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`,`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* Useful */
/* \dt+ -> gives size of tables */
/* \d+ <table> -> gives table info */

/* Update the comment count on post */
UPDATE post SET comments_count = (SELECT count(*) FROM comment WHERE post_id=post.id);
/* Update the likes count on post */
UPDATE post SET likes_count = (SELECT count(*) FROM likedby WHERE post_id=post.id);

/* Insert ignore */
CREATE OR REPLACE RULE "insert_ignore" AS ON INSERT TO 
	fb_user WHERE EXISTS(SELECT true FROM fb_user WHERE id = NEW.id) DO INSTEAD NOTHING;

CREATE OR REPLACE RULE "insert_ignore" AS ON INSERT TO 
	likedby WHERE (new.page_id, new.post_id, new.comment_id, new.fb_id) IN 
	( SELECT page_id, post_id, comment_id, fb_id FROM likedby WHERE 
	page_id=new.page_id AND post_id=new.post_id AND comment_id=new.comment_id AND fb_id=new.fb_id) 
	DO INSTEAD NOTHING;

/* insert on duplicate update */
CREATE OR REPLACE RULE "insert_on_duplicate_update" AS ON INSERT TO 
	likedby WHERE (new.page_id, new.post_id, new.comment_id, new.fb_id) IN 
	( SELECT page_id, post_id, comment_id, fb_id FROM likedby WHERE 
	page_id=new.page_id AND post_id=new.post_id AND comment_id=new.comment_id AND fb_id=new.fb_id) 
	DO INSTEAD UPDATE likedby SET created_time=new.created_time
	WHERE page_id=new.page_id AND post_id=new.post_id AND comment_id=new.comment_id AND fb_id=new.fb_id;

CREATE OR REPLACE RULE "insert_on_duplicate_update" AS ON INSERT TO 
	fb_user WHERE EXISTS(SELECT true FROM fb_user WHERE id = NEW.id) DO INSTEAD 
	UPDATE fb_uder SET name = new.name, category = new.category WHERE fb_user = new.fb_user;
