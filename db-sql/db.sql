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
	type VARCHAR(100),
	picture VARCHAR(100),
	story TEXT,
	link TEXT,
	link_name TEXT,
	link_description TEXT,
	link_caption TEXT,
	icon VARCHAR(60),
	created_time TIMESTAMP WITH TIME ZONE,
	updated_time TIMESTAMP WITH TIME ZONE,
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
	can_remove BOOL,
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