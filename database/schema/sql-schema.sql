CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
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
CREATE TABLE IF NOT EXISTS "buildings"(
  "id" integer primary key autoincrement not null,
  "tenant_id" integer not null,
  "address" text not null,
  "total_apartments" integer not null,
  "gyvatukas_summer_average" numeric,
  "gyvatukas_last_calculated" date,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "buildings_tenant_id_index" on "buildings"("tenant_id");
CREATE TABLE IF NOT EXISTS "properties"(
  "id" integer primary key autoincrement not null,
  "tenant_id" integer not null,
  "address" text not null,
  "type" varchar check("type" in('apartment', 'house')) not null,
  "area_sqm" numeric not null,
  "building_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("building_id") references "buildings"("id") on delete set null
);
CREATE INDEX "properties_tenant_id_index" on "properties"("tenant_id");
CREATE TABLE IF NOT EXISTS "tenants"(
  "id" integer primary key autoincrement not null,
  "tenant_id" integer not null,
  "name" varchar not null,
  "email" varchar not null,
  "phone" varchar,
  "property_id" integer not null,
  "lease_start" date not null,
  "lease_end" date,
  "created_at" datetime,
  "updated_at" datetime,
  "slug" varchar not null,
  "domain" varchar,
  "is_active" tinyint(1) not null default '1',
  "suspended_at" datetime,
  "suspension_reason" text,
  "plan" varchar not null default 'basic',
  "max_properties" integer not null default '100',
  "max_users" integer not null default '10',
  "trial_ends_at" datetime,
  "subscription_ends_at" datetime,
  "settings" text,
  "features" text,
  "timezone" varchar not null default 'Europe/Vilnius',
  "locale" varchar not null default 'lt',
  "currency" varchar not null default 'EUR',
  "last_activity_at" datetime,
  "created_by" integer,
  foreign key("property_id") references "properties"("id") on delete restrict
);
CREATE INDEX "tenants_tenant_id_index" on "tenants"("tenant_id");
CREATE TABLE IF NOT EXISTS "providers"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "service_type" varchar check("service_type" in('electricity', 'water', 'heating')) not null,
  "contact_info" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "tariffs"(
  "id" integer primary key autoincrement not null,
  "provider_id" integer not null,
  "name" varchar not null,
  "configuration" text not null,
  "active_from" datetime not null,
  "active_until" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("provider_id") references "providers"("id") on delete cascade
);
CREATE INDEX "tariffs_provider_id_active_from_active_until_index" on "tariffs"(
  "provider_id",
  "active_from",
  "active_until"
);
CREATE TABLE IF NOT EXISTS "meters"(
  "id" integer primary key autoincrement not null,
  "tenant_id" integer not null,
  "serial_number" varchar not null,
  "type" varchar check("type" in('electricity', 'water_cold', 'water_hot', 'heating')) not null,
  "property_id" integer not null,
  "installation_date" date not null,
  "supports_zones" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("property_id") references "properties"("id") on delete cascade
);
CREATE INDEX "meters_tenant_id_index" on "meters"("tenant_id");
CREATE UNIQUE INDEX "meters_serial_number_unique" on "meters"("serial_number");
CREATE TABLE IF NOT EXISTS "meter_readings"(
  "id" integer primary key autoincrement not null,
  "tenant_id" integer not null,
  "meter_id" integer not null,
  "reading_date" datetime not null,
  "value" numeric not null,
  "zone" varchar,
  "entered_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("meter_id") references "meters"("id") on delete cascade,
  foreign key("entered_by") references "users"("id") on delete set null
);
CREATE INDEX "meter_readings_meter_id_reading_date_index" on "meter_readings"(
  "meter_id",
  "reading_date"
);
CREATE INDEX "meter_readings_tenant_id_index" on "meter_readings"("tenant_id");
CREATE TABLE IF NOT EXISTS "meter_reading_audits"(
  "id" integer primary key autoincrement not null,
  "meter_reading_id" integer not null,
  "changed_by_user_id" integer,
  "old_value" numeric not null,
  "new_value" numeric not null,
  "change_reason" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("meter_reading_id") references "meter_readings"("id") on delete cascade,
  foreign key("changed_by_user_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "invoices"(
  "id" integer primary key autoincrement not null,
  "tenant_id" integer not null,
  "tenant_renter_id" integer not null,
  "billing_period_start" date not null,
  "billing_period_end" date not null,
  "total_amount" numeric not null,
  "status" varchar check("status" in('draft', 'finalized', 'paid')) not null default 'draft',
  "finalized_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("tenant_renter_id") references "tenants"("id") on delete restrict
);
CREATE INDEX "invoices_tenant_id_billing_period_start_index" on "invoices"(
  "tenant_id",
  "billing_period_start"
);
CREATE INDEX "invoices_tenant_id_index" on "invoices"("tenant_id");
CREATE TABLE IF NOT EXISTS "invoice_items"(
  "id" integer primary key autoincrement not null,
  "invoice_id" integer not null,
  "description" varchar not null,
  "quantity" numeric not null,
  "unit" varchar,
  "unit_price" numeric not null,
  "total" numeric not null,
  "meter_reading_snapshot" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("invoice_id") references "invoices"("id") on delete cascade
);
CREATE INDEX "buildings_created_at_index" on "buildings"("created_at");
CREATE INDEX "buildings_gyvatukas_index" on "buildings"(
  "gyvatukas_last_calculated"
);
CREATE INDEX "properties_created_at_index" on "properties"("created_at");
CREATE INDEX "properties_tenant_created_index" on "properties"(
  "tenant_id",
  "created_at"
);
CREATE INDEX "properties_building_id_index" on "properties"("building_id");
CREATE INDEX "meters_type_index" on "meters"("type");
CREATE INDEX "meters_property_type_index" on "meters"("property_id", "type");
CREATE INDEX "meters_installation_date_index" on "meters"("installation_date");
CREATE INDEX "meters_created_at_index" on "meters"("created_at");
CREATE INDEX "meter_readings_entered_by_index" on "meter_readings"(
  "entered_by"
);
CREATE INDEX "meter_readings_tenant_date_index" on "meter_readings"(
  "tenant_id",
  "reading_date"
);
CREATE INDEX "meter_readings_created_at_index" on "meter_readings"(
  "created_at"
);
CREATE INDEX "meter_reading_audits_changed_by_index" on "meter_reading_audits"(
  "changed_by_user_id"
);
CREATE INDEX "meter_reading_audits_created_at_index" on "meter_reading_audits"(
  "created_at"
);
CREATE INDEX "meter_reading_audits_reading_created_index" on "meter_reading_audits"(
  "meter_reading_id",
  "created_at"
);
CREATE INDEX "invoices_finalized_at_index" on "invoices"("finalized_at");
CREATE INDEX "invoices_tenant_status_index" on "invoices"(
  "tenant_id",
  "status"
);
CREATE INDEX "invoices_period_index" on "invoices"(
  "billing_period_start",
  "billing_period_end"
);
CREATE INDEX "invoices_created_at_index" on "invoices"("created_at");
CREATE INDEX "invoice_items_invoice_id_index" on "invoice_items"("invoice_id");
CREATE INDEX "invoice_items_created_at_index" on "invoice_items"("created_at");
CREATE INDEX "tenants_email_index" on "tenants"("email");
CREATE INDEX "tenants_created_at_index" on "tenants"("created_at");
CREATE INDEX "providers_created_at_index" on "providers"("created_at");
CREATE INDEX "tariffs_type_index" on "tariffs"("type");
CREATE INDEX "tariffs_created_at_index" on "tariffs"("created_at");
CREATE INDEX "meter_readings_lookup_index" on "meter_readings"(
  "meter_id",
  "reading_date",
  "zone"
);
CREATE INDEX "meter_readings_date_index" on "meter_readings"("reading_date");
CREATE INDEX "tariffs_active_lookup_index" on "tariffs"(
  "provider_id",
  "active_from",
  "active_until"
);
CREATE INDEX "invoices_tenant_period_index" on "invoices"(
  "tenant_renter_id",
  "billing_period_start"
);
CREATE INDEX "invoices_status_index" on "invoices"("status");
CREATE INDEX "meters_property_index" on "meters"("property_id");
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "tenant_id" integer,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "role" varchar check("role" in('superadmin', 'admin', 'manager', 'tenant')) not null default 'tenant',
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "property_id" integer,
  "parent_user_id" integer,
  "is_active" tinyint(1) not null default '1',
  "organization_name" varchar,
  foreign key("property_id") references "properties"("id") on delete set null,
  foreign key("parent_user_id") references "users"("id") on delete set null
);
CREATE INDEX "users_created_at_index" on "users"("created_at");
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE INDEX "users_email_verified_index" on "users"("email_verified_at");
CREATE INDEX "users_tenant_id_index" on "users"("tenant_id");
CREATE INDEX "users_tenant_role_index" on "users"("tenant_id", "role");
CREATE INDEX "users_parent_user_id_index" on "users"("parent_user_id");
CREATE INDEX "users_property_id_index" on "users"("property_id");
CREATE TABLE IF NOT EXISTS "subscriptions"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "plan_type" varchar check("plan_type" in('basic', 'professional', 'enterprise')) not null default 'basic',
  "status" varchar check("status" in('active', 'expired', 'suspended', 'cancelled')) not null default 'active',
  "starts_at" datetime not null,
  "expires_at" datetime not null,
  "max_properties" integer not null default '10',
  "max_tenants" integer not null default '50',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "subscriptions_user_status_index" on "subscriptions"(
  "user_id",
  "status"
);
CREATE INDEX "subscriptions_expires_at_index" on "subscriptions"("expires_at");
CREATE TABLE IF NOT EXISTS "user_assignments_audit"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "property_id" integer,
  "previous_property_id" integer,
  "performed_by" integer not null,
  "action" varchar check("action" in('created', 'assigned', 'reassigned', 'deactivated', 'reactivated')) not null,
  "reason" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("property_id") references "properties"("id") on delete set null,
  foreign key("previous_property_id") references "properties"("id") on delete set null,
  foreign key("performed_by") references "users"("id") on delete cascade
);
CREATE INDEX "user_assignments_audit_user_created_index" on "user_assignments_audit"(
  "user_id",
  "created_at"
);
CREATE INDEX "user_assignments_audit_performed_by_index" on "user_assignments_audit"(
  "performed_by"
);
CREATE INDEX "tenants_is_active_index" on "tenants"("is_active");
CREATE INDEX "tenants_plan_index" on "tenants"("plan");
CREATE INDEX "tenants_is_active_subscription_ends_at_index" on "tenants"(
  "is_active",
  "subscription_ends_at"
);
CREATE UNIQUE INDEX "tenants_slug_unique" on "tenants"("slug");
CREATE UNIQUE INDEX "tenants_domain_unique" on "tenants"("domain");
CREATE TABLE IF NOT EXISTS "tenant_activity_log"(
  "id" integer primary key autoincrement not null,
  "tenant_id" integer not null,
  "user_id" integer,
  "action" varchar not null,
  "resource_type" varchar,
  "resource_id" integer,
  "metadata" text,
  "ip_address" varchar,
  "user_agent" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("tenant_id") references "tenants"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "tenant_activity_log_tenant_id_created_at_index" on "tenant_activity_log"(
  "tenant_id",
  "created_at"
);
CREATE INDEX "tenant_activity_log_user_id_created_at_index" on "tenant_activity_log"(
  "user_id",
  "created_at"
);
CREATE INDEX "tenant_activity_log_action_index" on "tenant_activity_log"(
  "action"
);
CREATE TABLE IF NOT EXISTS "tenant_invitations"(
  "id" integer primary key autoincrement not null,
  "tenant_id" integer not null,
  "email" varchar not null,
  "role" varchar not null,
  "token" varchar not null,
  "expires_at" datetime not null,
  "accepted_at" datetime,
  "invited_by" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("tenant_id") references "tenants"("id") on delete cascade,
  foreign key("invited_by") references "users"("id") on delete cascade
);
CREATE INDEX "tenant_invitations_tenant_id_email_index" on "tenant_invitations"(
  "tenant_id",
  "email"
);
CREATE INDEX "tenant_invitations_token_index" on "tenant_invitations"("token");
CREATE INDEX "tenant_invitations_expires_at_index" on "tenant_invitations"(
  "expires_at"
);
CREATE UNIQUE INDEX "tenant_invitations_token_unique" on "tenant_invitations"(
  "token"
);
CREATE TABLE IF NOT EXISTS "property_tenant"(
  "id" integer primary key autoincrement not null,
  "property_id" integer not null,
  "tenant_id" integer not null,
  "assigned_at" datetime,
  "vacated_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("property_id") references "properties"("id") on delete cascade,
  foreign key("tenant_id") references "tenants"("id") on delete cascade
);
CREATE UNIQUE INDEX "property_tenant_property_id_tenant_id_unique" on "property_tenant"(
  "property_id",
  "tenant_id"
);
CREATE INDEX "property_tenant_property_id_index" on "property_tenant"(
  "property_id"
);
CREATE INDEX "property_tenant_tenant_id_index" on "property_tenant"(
  "tenant_id"
);
CREATE INDEX "property_tenant_assigned_at_index" on "property_tenant"(
  "assigned_at"
);
CREATE INDEX "properties_type_index" on "properties"("type");
CREATE INDEX "properties_area_index" on "properties"("area_sqm");
CREATE INDEX "properties_building_type_index" on "properties"(
  "building_id",
  "type"
);
CREATE INDEX "properties_tenant_type_index" on "properties"(
  "tenant_id",
  "type"
);
CREATE INDEX "property_tenant_vacated_index" on "property_tenant"(
  "vacated_at"
);
CREATE INDEX "property_tenant_current_index" on "property_tenant"(
  "property_id",
  "vacated_at"
);
CREATE INDEX "property_tenant_active_index" on "property_tenant"(
  "tenant_id",
  "vacated_at"
);
CREATE TABLE IF NOT EXISTS "faqs"(
  "id" integer primary key autoincrement not null,
  "question" varchar not null,
  "answer" text not null,
  "category" varchar,
  "display_order" integer not null default '0',
  "is_published" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "faqs_is_published_display_order_index" on "faqs"(
  "is_published",
  "display_order"
);
CREATE TABLE IF NOT EXISTS "languages"(
  "id" integer primary key autoincrement not null,
  "code" varchar not null,
  "name" varchar not null,
  "native_name" varchar,
  "is_default" tinyint(1) not null default '0',
  "is_active" tinyint(1) not null default '1',
  "display_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "languages_code_unique" on "languages"("code");
CREATE TABLE IF NOT EXISTS "translations"(
  "id" integer primary key autoincrement not null,
  "group" varchar not null,
  "key" varchar not null,
  "values" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "translations_group_key_unique" on "translations"(
  "group",
  "key"
);
CREATE INDEX "translations_group_index" on "translations"("group");
CREATE TABLE IF NOT EXISTS "organizations"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "domain" varchar,
  "email" varchar not null,
  "phone" varchar,
  "is_active" tinyint(1) not null default '1',
  "suspended_at" datetime,
  "suspension_reason" text,
  "plan" varchar not null default 'basic',
  "max_properties" integer not null default '100',
  "max_users" integer not null default '10',
  "trial_ends_at" datetime,
  "subscription_ends_at" datetime,
  "settings" text,
  "features" text,
  "timezone" varchar not null default 'Europe/Vilnius',
  "locale" varchar not null default 'lt',
  "currency" varchar not null default 'EUR',
  "created_by" integer,
  "last_activity_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "organizations_status_subscription_index" on "organizations"(
  "is_active",
  "subscription_ends_at"
);
CREATE INDEX "organizations_plan_index" on "organizations"("plan");
CREATE INDEX "organizations_created_by_index" on "organizations"("created_by");
CREATE UNIQUE INDEX "organizations_slug_unique" on "organizations"("slug");
CREATE UNIQUE INDEX "organizations_domain_unique" on "organizations"("domain");
CREATE UNIQUE INDEX "organizations_email_unique" on "organizations"("email");
CREATE TABLE IF NOT EXISTS "organization_activity_log"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "user_id" integer,
  "action" varchar not null,
  "resource_type" varchar,
  "resource_id" integer,
  "metadata" text,
  "ip_address" varchar,
  "user_agent" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("organization_id") references "organizations"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "org_activity_org_created_index" on "organization_activity_log"(
  "organization_id",
  "created_at"
);
CREATE INDEX "org_activity_user_created_index" on "organization_activity_log"(
  "user_id",
  "created_at"
);
CREATE INDEX "org_activity_action_index" on "organization_activity_log"(
  "action"
);
CREATE TABLE IF NOT EXISTS "organization_invitations"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "email" varchar not null,
  "role" varchar not null,
  "token" varchar not null,
  "expires_at" datetime not null,
  "accepted_at" datetime,
  "invited_by" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("organization_id") references "organizations"("id") on delete cascade,
  foreign key("invited_by") references "users"("id") on delete cascade
);
CREATE INDEX "org_invites_org_email_index" on "organization_invitations"(
  "organization_id",
  "email"
);
CREATE INDEX "org_invites_expires_at_index" on "organization_invitations"(
  "expires_at"
);
CREATE UNIQUE INDEX "organization_invitations_token_unique" on "organization_invitations"(
  "token"
);
CREATE INDEX "meter_reading_audits_meter_index" on "meter_reading_audits"(
  "meter_reading_id"
);
CREATE INDEX "users_is_active_index" on "users"("is_active");
CREATE INDEX "users_tenant_active_index" on "users"("tenant_id", "is_active");
CREATE INDEX "subscriptions_created_at_index" on "subscriptions"("created_at");
CREATE INDEX "property_tenant_created_at_index" on "property_tenant"(
  "created_at"
);
CREATE INDEX "faqs_created_at_index" on "faqs"("created_at");
CREATE INDEX "translations_created_at_index" on "translations"("created_at");

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'0001_01_01_000003_create_buildings_table',1);
INSERT INTO migrations VALUES(5,'0001_01_01_000004_create_properties_table',1);
INSERT INTO migrations VALUES(6,'0001_01_01_000005_create_tenants_table',1);
INSERT INTO migrations VALUES(7,'0001_01_01_000006_create_providers_table',1);
INSERT INTO migrations VALUES(8,'0001_01_01_000007_create_tariffs_table',1);
INSERT INTO migrations VALUES(9,'0001_01_01_000008_create_meters_table',1);
INSERT INTO migrations VALUES(10,'0001_01_01_000009_create_meter_readings_table',1);
INSERT INTO migrations VALUES(11,'0001_01_01_000010_create_meter_reading_audits_table',1);
INSERT INTO migrations VALUES(12,'0001_01_01_000011_create_invoices_table',1);
INSERT INTO migrations VALUES(13,'0001_01_01_000012_create_invoice_items_table',1);
INSERT INTO migrations VALUES(14,'2025_01_15_000001_add_comprehensive_database_indexes',1);
INSERT INTO migrations VALUES(15,'2025_11_18_000001_add_performance_indexes',1);
INSERT INTO migrations VALUES(16,'2025_11_20_000001_add_hierarchical_columns_to_users_table',1);
INSERT INTO migrations VALUES(17,'2025_11_20_000002_create_subscriptions_table',1);
INSERT INTO migrations VALUES(18,'2025_11_20_000003_create_user_assignments_audit_table',1);
INSERT INTO migrations VALUES(19,'2025_11_23_000001_enhance_tenant_management',1);
INSERT INTO migrations VALUES(20,'2025_11_23_183413_create_property_tenant_pivot_table',1);
INSERT INTO migrations VALUES(21,'2025_11_23_184755_add_properties_performance_indexes',1);
INSERT INTO migrations VALUES(22,'2025_11_24_000001_create_faqs_table',1);
INSERT INTO migrations VALUES(23,'2025_11_24_000002_create_languages_table',1);
INSERT INTO migrations VALUES(24,'2025_11_24_000003_create_translations_table',1);
INSERT INTO migrations VALUES(25,'2025_12_01_000001_create_organizations_tables',1);
INSERT INTO migrations VALUES(26,'2025_12_02_000001_add_comprehensive_database_indexes',1);
