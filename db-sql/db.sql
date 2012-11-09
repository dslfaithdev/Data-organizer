DROP TABLE tag CASCADE;
DROP TABLE post CASCADE;
DROP TABLE comment CASCADE;
DROP TABLE likedby CASCADE;
DROP TABLE message CASCADE;
DROP TABLE fb_user CASCADE;
DROP TABLE page CASCADE;

CREATE TABLE page (
	id BIGINT NOT NULL,
	name TEXT,
	category TEXT,
	PRIMARY KEY (id)
);

CREATE TABLE fb_user (
	id BIGINT NOT NULL,
	name TEXT,
	category VARCHAR(100),
	PRIMARY KEY (id)
);

CREATE TABLE post (
	id BIGINT,
	page_id BIGINT,
	fb_id BIGINT NOT NULL,
	message TEXT,
	type VARCHAR(256),
	picture VARCHAR(256),
	story TEXT,
	link TEXT,
	link_name TEXT,
	link_description TEXT,
	link_caption TEXT,
	icon VARCHAR(256),
	created_time TIMESTAMP WITH TIME ZONE,
	updated_time TIMESTAMP WITH TIME ZONE,
	can_remove BOOL,
	shares_count INT,
	likes_count INT,
	comments_count INT,
	extracted boolean default false,
	entr_pg FLOAT DEFAULT -1;
	entr_ug FLOAT DEFAULT -1;
	PRIMARY KEY (page_id, id),
	FOREIGN KEY (fb_id) REFERENCES fb_user(id) ON UPDATE CASCADE ON DELETE RESTRICT,
	FOREIGN KEY (page_id) REFERENCES page(id) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE comment (
	id BIGINT,
	post_id BIGINT,
	page_id BIGINT,
	fb_id BIGINT,
	message TEXT,
	can_remove BOOL,
	extracted boolean default false,
	created_time TIMESTAMP WITH TIME ZONE,
	PRIMARY KEY (page_id, post_id, id),
	FOREIGN KEY (fb_id) REFERENCES fb_user(id) ON UPDATE CASCADE ON DELETE RESTRICT,
	FOREIGN KEY (post_id, page_id) REFERENCES post(id, page_id) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE likedby (
	page_id BIGINT NOT NULL,
	post_id BIGINT NOT NULL,
	comment_id BIGINT,
	fb_id BIGINT NOT NULL,
	created_time TIMESTAMP WITH TIME ZONE,
	PRIMARY KEY (page_id, post_id, comment_id, fb_id),
	FOREIGN KEY (fb_id) REFERENCES fb_user(id) ON UPDATE CASCADE ON DELETE RESTRICT,
	FOREIGN KEY (post_id, page_id) REFERENCES post(id, page_id) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE tag (
	page_id BIGINT NOT NULL,
	post_id BIGINT NOT NULL,
	comment_id BIGINT,
	fb_id BIGINT NOT NULL,
	type VARCHAR(30),
	starting_offset INT,
	length INT,	
	PRIMARY KEY (page_id, post_id, comment_id),
	FOREIGN KEY (fb_id) REFERENCES fb_user(id) ON UPDATE CASCADE ON DELETE RESTRICT,
	FOREIGN KEY (post_id, page_id) REFERENCES post(id, page_id) ON UPDATE CASCADE ON DELETE RESTRICT
);


CREATE TABLE keyword (
	hash_id VARCHAR(32),
	page_id BIGINT NOT NULL,
	post_id BIGINT NOT NULL,
	comment_id BIGINT,
	PRIMARY KEY (hash_id, page_id, post_id, comment_id),
	FOREIGN KEY (post_id, page_id) REFERENCES post(id, page_id) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE INDEX ON keyword (hash_id);

/* Views */
CREATE VIEW status AS SELECT 
	(SELECT to_char(count(*),'999 999 999 999') FROM likedby) AS likes,
	(SELECT to_char(count(*),'999 999 999 999') FROM fb_user) AS users,
	(SELECT to_char(count(*),'999 999 999 999') FROM post) AS posts,
	(SELECT to_char(count(*),'999 999 999 999') FROM comment) AS comments;

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
