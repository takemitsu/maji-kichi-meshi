CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "role" varchar check("role" in('user', 'admin', 'moderator')) not null default 'user',
  "status" varchar check("status" in('active', 'banned', 'deleted')) not null default 'active',
  "two_factor_secret" text,
  "two_factor_recovery_codes" text,
  "two_factor_confirmed_at" datetime,
  "two_factor_enabled" tinyint(1) not null default '0',
  "profile_image_filename" varchar,
  "profile_image_original_name" varchar,
  "profile_image_thumbnail_path" varchar,
  "profile_image_small_path" varchar,
  "profile_image_medium_path" varchar,
  "profile_image_large_path" varchar,
  "profile_image_file_size" integer,
  "profile_image_mime_type" varchar,
  "profile_image_uploaded_at" datetime
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "oauth_providers"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "provider" varchar not null,
  "provider_id" varchar not null,
  "provider_token" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "oauth_providers_provider_provider_id_unique" on "oauth_providers"(
  "provider",
  "provider_id"
);
CREATE INDEX "oauth_providers_user_id_provider_index" on "oauth_providers"(
  "user_id",
  "provider"
);
CREATE TABLE IF NOT EXISTS "shops"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "description" text,
  "address" varchar,
  "latitude" numeric,
  "longitude" numeric,
  "phone" varchar,
  "website" varchar,
  "google_place_id" varchar,
  "is_closed" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "status" varchar check("status" in('active', 'hidden', 'deleted')) not null default 'active',
  "moderated_by" integer,
  "moderated_at" datetime
);
CREATE INDEX "shops_latitude_longitude_index" on "shops"(
  "latitude",
  "longitude"
);
CREATE INDEX "shops_is_closed_index" on "shops"("is_closed");
CREATE UNIQUE INDEX "shops_google_place_id_unique" on "shops"(
  "google_place_id"
);
CREATE TABLE IF NOT EXISTS "categories"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "type" varchar check("type" in('basic', 'time', 'ranking')) not null default 'basic',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "categories_type_index" on "categories"("type");
CREATE UNIQUE INDEX "categories_name_unique" on "categories"("name");
CREATE UNIQUE INDEX "categories_slug_unique" on "categories"("slug");
CREATE TABLE IF NOT EXISTS "rankings"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "shop_id" integer not null,
  "category_id" integer not null,
  "rank_position" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  "is_public" tinyint(1) not null default '0',
  "title" varchar,
  "description" text,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("shop_id") references "shops"("id") on delete cascade,
  foreign key("category_id") references "categories"("id") on delete cascade
);
CREATE UNIQUE INDEX "rankings_user_id_shop_id_category_id_unique" on "rankings"(
  "user_id",
  "shop_id",
  "category_id"
);
CREATE INDEX "rankings_user_id_category_id_index" on "rankings"(
  "user_id",
  "category_id"
);
CREATE TABLE IF NOT EXISTS "review_images"(
  "id" integer primary key autoincrement not null,
  "review_id" integer not null,
  "large_path" varchar not null,
  "medium_path" varchar not null,
  "thumbnail_path" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  "filename" varchar not null,
  "original_name" varchar not null,
  "small_path" varchar not null,
  "file_size" integer not null,
  "mime_type" varchar not null,
  "moderation_status" varchar check("moderation_status" in('published', 'under_review', 'rejected')) not null default 'published',
  "moderation_notes" text,
  "moderated_by" integer,
  "moderated_at" datetime,
  foreign key("review_id") references "reviews"("id") on delete cascade
);
CREATE INDEX "review_images_review_id_index" on "review_images"("review_id");
CREATE TABLE IF NOT EXISTS "shop_categories"(
  "id" integer primary key autoincrement not null,
  "shop_id" integer not null,
  "category_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("shop_id") references "shops"("id") on delete cascade,
  foreign key("category_id") references "categories"("id") on delete cascade
);
CREATE UNIQUE INDEX "shop_categories_shop_id_category_id_unique" on "shop_categories"(
  "shop_id",
  "category_id"
);
CREATE TABLE IF NOT EXISTS "admin_login_attempts"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "ip_address" varchar not null,
  "user_agent" text not null,
  "email" varchar,
  "successful" tinyint(1) not null default '0',
  "failure_reason" varchar,
  "attempted_at" datetime not null default CURRENT_TIMESTAMP,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "admin_login_attempts_user_id_attempted_at_index" on "admin_login_attempts"(
  "user_id",
  "attempted_at"
);
CREATE INDEX "admin_login_attempts_ip_address_attempted_at_index" on "admin_login_attempts"(
  "ip_address",
  "attempted_at"
);
CREATE INDEX "admin_login_attempts_email_attempted_at_index" on "admin_login_attempts"(
  "email",
  "attempted_at"
);
CREATE INDEX "admin_login_attempts_successful_attempted_at_index" on "admin_login_attempts"(
  "successful",
  "attempted_at"
);
CREATE TABLE IF NOT EXISTS "shop_images"(
  "id" integer primary key autoincrement not null,
  "shop_id" integer not null,
  "uuid" varchar not null,
  "filename" varchar not null,
  "original_name" varchar not null,
  "mime_type" varchar not null,
  "file_size" integer not null,
  "image_sizes" text not null,
  "status" varchar not null default 'published',
  "moderated_by" integer,
  "moderated_at" datetime,
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("shop_id") references "shops"("id") on delete cascade,
  foreign key("moderated_by") references "users"("id") on delete set null
);
CREATE INDEX "shop_images_shop_id_status_index" on "shop_images"(
  "shop_id",
  "status"
);
CREATE INDEX "shop_images_status_created_at_index" on "shop_images"(
  "status",
  "created_at"
);
CREATE UNIQUE INDEX "shop_images_uuid_unique" on "shop_images"("uuid");
CREATE TABLE IF NOT EXISTS "reviews"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "shop_id" integer not null,
  "rating" integer not null,
  "repeat_intention" varchar check("repeat_intention" in('yes', 'maybe', 'no')) not null,
  "memo" text,
  "visited_at" date not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("shop_id") references "shops"("id") on delete cascade
);
CREATE INDEX "reviews_temp_user_id_shop_id_index" on "reviews"(
  "user_id",
  "shop_id"
);
CREATE INDEX "reviews_temp_visited_at_index" on "reviews"("visited_at");

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2025_07_08_070857_create_oauth_providers_table',1);
INSERT INTO migrations VALUES(5,'2025_07_08_070908_create_shops_table',1);
INSERT INTO migrations VALUES(6,'2025_07_08_070913_create_categories_table',1);
INSERT INTO migrations VALUES(7,'2025_07_08_070914_create_rankings_table',1);
INSERT INTO migrations VALUES(8,'2025_07_08_070914_create_review_images_table',1);
INSERT INTO migrations VALUES(9,'2025_07_08_070914_create_reviews_table',1);
INSERT INTO migrations VALUES(10,'2025_07_08_070914_create_shop_categories_table',1);
INSERT INTO migrations VALUES(11,'2025_07_08_082321_add_public_and_meta_fields_to_rankings_table',1);
INSERT INTO migrations VALUES(12,'2025_07_08_082635_remove_position_unique_constraint_from_rankings',1);
INSERT INTO migrations VALUES(13,'2025_07_09_023637_update_review_images_table_for_image_service',1);
INSERT INTO migrations VALUES(14,'2025_07_09_032022_add_role_to_users_table',1);
INSERT INTO migrations VALUES(15,'2025_07_09_032522_add_status_to_shops_table',1);
INSERT INTO migrations VALUES(16,'2025_07_09_032624_add_moderation_status_to_review_images_table',1);
INSERT INTO migrations VALUES(17,'2025_07_09_062429_add_two_factor_columns_to_users_table',1);
INSERT INTO migrations VALUES(18,'2025_07_09_062440_create_admin_login_attempts_table',1);
INSERT INTO migrations VALUES(19,'2025_07_09_072707_create_shop_images_table',1);
INSERT INTO migrations VALUES(20,'2025_07_10_121430_update_reviews_repeat_intention_to_english',1);
INSERT INTO migrations VALUES(21,'2025_07_11_042238_add_profile_image_to_users_table',1);
