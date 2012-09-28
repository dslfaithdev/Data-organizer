DROP TABLE tag;
DROP TABLE post;
DROP TABLE comment;
DROP TABLE likedby;
DROP TABLE message;
DROP TABLE fb_user;
DROP TABLE fb_page;

DROP SCHEMA fb_wallpost cascade;
create schema fb_wallpost;
set search_path to fb_wallpost;

CREATE TABLE fb_page (
	id BIGINT NOT NULL,
	name TEXT,
	category TEXT,
	PRIMARY KEY (id)
};

CREATE TABLE fb_user (
	id BIGINT NOT NULL,
	name TEXT,
	category VARCHAR(100),
	PRIMARY KEY (id)
);

CREATE TABLE post (
	id BIGINT,
	page_id BIGINT,
	name TEXT,
	text TEXT,
	type VARCHAR(20),
	story TEXT,
	link TEXT,
	link_description TEXT,
	link_caption TEXT,
	created_time TIMESTAMP WITH TIME ZONE,
	updated_time TIMESTAMP WITH TIME ZONE,
	fb_id BIGINT NOT NULL,
	can_remove BOOL,
	shares_count INT,
	likes_count INT,
	comments_count INT,
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
	created_time TIMESTAMP WITH TIME ZONE,
	PRIMARY KEY (page_id, post_id, id),
	FOREIGN KEY (fb_id) REFERENCES fb_user(id) ON UPDATE CASCADE ON DELETE RESTRICT,
	FOREIGN KEY (page_id) REFERENCES page(id) ON UPDATE CASCADE ON DELETE RESTRICT,
	FOREIGN KEY (post_id) REFERENCES post(id) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE likedby (
	page_id BIGINT NOT NULL,
	post_id BIGINT NOT NULL,
	comment_id BIGINT,
	fb_id INT NOT NULL,
	PRIMARY KEY (page_id, post_id, comment_id, fb_id),
	FOREIGN KEY (fb_id) REFERENCES fb_user(id) ON UPDATE CASCADE ON DELETE RESTRICT,
	FOREIGN KEY (page_id) REFERENCES page(id) ON UPDATE CASCADE ON DELETE RESTRICT,
	FOREIGN KEY (post_id) REFERENCES post(id) ON UPDATE CASCADE ON DELETE RESTRICT,
	FOREIGN KEY (comment_id) REFERENCES comment(id) ON UPDATE CASCADE ON DELETE RESTRICT
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
	FOREIGN KEY (page_id) REFERENCES page(id) ON UPDATE CASCADE ON DELETE RESTRICT,
	FOREIGN KEY (post_id) REFERENCES post(id) ON UPDATE CASCADE ON DELETE RESTRICT,
	FOREIGN KEY (comment_id) REFERENCES comment(id) ON UPDATE CASCADE ON DELETE RESTRICT
);


CREATE TABLE keyword (
	hash_id BIGINT,
	page_id BIGINT NOT NULL,
	post_id BIGINT NOT NULL,
	comment_id BIGINT,
	PRIMARY KEY (hash_id, page_id, post_id, comment_id),
	FOREIGN KEY (page_id) REFERENCES page(id) ON UPDATE CASCADE ON DELETE RESTRICT,
	FOREIGN KEY (post_id) REFERENCES post(id) ON UPDATE CASCADE ON DELETE RESTRICT,
	FOREIGN KEY (comment_id) REFERENCES comment(id) ON UPDATE CASCADE ON DELETE RESTRICT
);