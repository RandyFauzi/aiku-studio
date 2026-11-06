-- Generated from Laravel migrations in SQLite :memory:. Structure only; no rows.
-- SQLite representation, not a MySQL restore script. Logical joins: docs/AI_CONTEXT.md.

CREATE TABLE "attendances" ("id" integer primary key autoincrement not null, "user_id" integer not null, "contributor_id" integer not null, "contributor_name" varchar not null, "work_date" date not null, "checked_in_at" datetime not null, "latitude" numeric, "longitude" numeric, "accuracy_m" double, "gps_captured_at" datetime, "created_at" datetime, "updated_at" datetime, "checkin_method" varchar not null default ('gps'), "client_ip" varchar, "wifi_verified" tinyint(1) not null default ('0'));

CREATE TABLE "cache" ("key" varchar not null, "value" text not null, "expiration" integer not null, primary key ("key"));

CREATE TABLE "cache_locks" ("key" varchar not null, "owner" varchar not null, "expiration" integer not null, primary key ("key"));

CREATE TABLE "contributors" ("id" integer primary key autoincrement not null, "nama" varchar, "no_wa" varchar, "domisili" varchar, "no_rek" varchar, "shift" varchar, "hp" varchar, "email" varchar, "role" varchar not null default 'contributor');

CREATE TABLE "daily_work_reports" ("id" integer primary key autoincrement not null, "user_id" integer not null, "contributor_id" integer not null, "contributor_name" varchar not null, "work_date" date not null, "total_duration_minutes" integer not null, "phones" text not null, "revision" integer not null default '1', "created_at" datetime, "updated_at" datetime, "phone_durations" text, "phone_emails" text, "phone_tasks" text, "shift_label" varchar, "notes" varchar);

CREATE TABLE "device_accounts" ("id" integer primary key autoincrement not null, "phone_key" varchar not null, "email" varchar not null, "account_name" varchar, "is_active" tinyint(1) not null default '0', "activated_at" datetime, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "failed_jobs" ("id" integer primary key autoincrement not null, "uuid" varchar not null, "connection" text not null, "queue" text not null, "payload" text not null, "exception" text not null, "failed_at" datetime not null default CURRENT_TIMESTAMP);

CREATE TABLE "job_batches" ("id" varchar not null, "name" varchar not null, "total_jobs" integer not null, "pending_jobs" integer not null, "failed_jobs" integer not null, "failed_job_ids" text not null, "options" text, "cancelled_at" integer, "created_at" integer not null, "finished_at" integer, primary key ("id"));

CREATE TABLE "jobs" ("id" integer primary key autoincrement not null, "queue" varchar not null, "payload" text not null, "attempts" integer not null, "reserved_at" integer, "available_at" integer not null, "created_at" integer not null);

CREATE TABLE "migrations" ("id" integer primary key autoincrement not null, "migration" varchar not null, "batch" integer not null);

CREATE TABLE "password_reset_tokens" ("email" varchar not null, "token" varchar not null, "created_at" datetime, primary key ("email"));

CREATE TABLE "payroll_statuses" ("id" integer primary key autoincrement not null, "worker_name" varchar not null, "period" varchar not null, "status" varchar check ("status" in ('belum', 'sudah')) not null default 'belum', "notes" text, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "payslips" ("id" integer primary key autoincrement not null, "employee_name" varchar not null, "period" varchar not null, "total_logged_hours" double not null default '0', "total_approved_hours" double not null default '0', "total_thp" integer not null default '0', "bonus" integer not null default '0', "status" varchar check ("status" in ('belum', 'sudah')) not null default 'belum', "file_path" varchar, "evidence_data" text, "created_at" datetime, "pending_carry_over_amount" integer not null default '0', "pending_carry_over_hours" double not null default '0', "pending_carry_over_data" text, "is_locked" tinyint(1) not null default '0');

CREATE TABLE "record_streak_days" ("id" integer primary key autoincrement not null, "contributor_id" integer not null, "work_date" date not null, "schedule_snapshot" text not null, "deadline_at" datetime not null, "target_minutes" integer not null, "rule_version" integer not null default '1', "status" varchar not null default 'open', "reason" varchar, "attendance_id" integer, "verified_minutes" numeric not null default '0', "evidence_ids" text, "manual_report_id" integer, "manual_report_revision" integer, "verified_by" integer, "verified_at" datetime, "celebrated_at" datetime, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "recording_owner_confirmations" ("id" integer primary key autoincrement not null, "record_id" varchar not null, "contributor_id" integer not null, "admin_id" integer not null, "report_id" integer not null, "work_date" date not null, "evidence" text not null, "created_at" datetime not null);

CREATE TABLE "report_task_options" ("id" integer primary key autoincrement not null, "title" varchar not null, "normalized_key" varchar not null, "created_at" datetime not null);

CREATE TABLE "report_task_translations" ("normalized_key" varchar not null, "source_title" varchar not null, "label" varchar not null, "keywords" text not null, "updated_by" integer not null, "created_at" datetime, "updated_at" datetime, primary key ("normalized_key"));

CREATE TABLE "sessions" ("id" varchar not null, "user_id" integer, "ip_address" varchar, "user_agent" text, "payload" text not null, "last_activity" integer not null, primary key ("id"));

CREATE TABLE "shift_days" ("id" integer primary key autoincrement not null, "work_date" date not null, "config" text not null, "revision" integer not null default '1', "created_at" datetime, "updated_at" datetime);

CREATE TABLE "shift_settings" ("id" integer primary key autoincrement not null, "setting_key" varchar not null, "setting_value" text not null, "updated_at" datetime);

CREATE TABLE "studio_accounts" ("id" integer primary key autoincrement not null, "email" varchar not null, "name" varchar not null, "active" tinyint(1) not null default '1', "participant_id" varchar, "created_at" datetime, "updated_at" datetime);

CREATE TABLE "studio_recordings" ("record_id" varchar not null, "participant_email" varchar not null, "task_title" varchar, "recorded_utc" varchar, "recorded_at" datetime, "duration_sec" double not null default '0', "duration_min" double not null default '0', "billable_min" double not null default '0', "rejected_min" double not null default '0', "pending_min" double not null default '0', "deferred_min" double not null default '0', "processing_status" varchar, "billable_status" varchar, "reviewed_utc" varchar, "reconciliation" varchar, "assigned_contributor_id" integer, "assigned_worker_name" varchar, "manual_payment_status" varchar not null default 'Belum Rekap', "notes" text, "assigned_at" datetime, "last_synced_at" datetime, "createdAt" datetime, "updatedAt" datetime, primary key ("record_id"));

CREATE TABLE "users" ("id" integer primary key autoincrement not null, "name" varchar not null, "email" varchar not null, "email_verified_at" datetime, "password" varchar not null, "remember_token" varchar, "created_at" datetime, "updated_at" datetime, "role" varchar not null default 'contributor', "contributor_id" integer, "is_active" tinyint(1) not null default '1');

CREATE INDEX "attendances_contributor_id_index" on "attendances" ("contributor_id");

CREATE INDEX "attendances_user_id_index" on "attendances" ("user_id");

CREATE UNIQUE INDEX "attendances_user_id_work_date_unique" on "attendances" ("user_id", "work_date");

CREATE INDEX "attendances_work_date_index" on "attendances" ("work_date");

CREATE INDEX "cache_expiration_index" on "cache" ("expiration");

CREATE INDEX "cache_locks_expiration_index" on "cache_locks" ("expiration");

CREATE INDEX "contributors_email_index" on "contributors" ("email");

CREATE INDEX "contributors_nama_index" on "contributors" ("nama");

CREATE INDEX "daily_work_reports_contributor_id_index" on "daily_work_reports" ("contributor_id");

CREATE INDEX "daily_work_reports_user_id_index" on "daily_work_reports" ("user_id");

CREATE INDEX "daily_work_reports_user_id_work_date_index" on "daily_work_reports" ("user_id", "work_date");

CREATE INDEX "daily_work_reports_work_date_index" on "daily_work_reports" ("work_date");

CREATE INDEX "device_accounts_email_index" on "device_accounts" ("email");

CREATE INDEX "device_accounts_is_active_index" on "device_accounts" ("is_active");

CREATE UNIQUE INDEX "device_accounts_phone_key_email_unique" on "device_accounts" ("phone_key", "email");

CREATE INDEX "device_accounts_phone_key_index" on "device_accounts" ("phone_key");

CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs" ("uuid");

CREATE INDEX "jobs_queue_index" on "jobs" ("queue");

CREATE UNIQUE INDEX "payroll_statuses_worker_name_period_unique" on "payroll_statuses" ("worker_name", "period");

CREATE INDEX "payslips_employee_name_period_index" on "payslips" ("employee_name", "period");

CREATE INDEX "record_streak_days_contributor_id_index" on "record_streak_days" ("contributor_id");

CREATE UNIQUE INDEX "record_streak_days_contributor_id_work_date_unique" on "record_streak_days" ("contributor_id", "work_date");

CREATE INDEX "recording_owner_confirmations_contributor_id_index" on "recording_owner_confirmations" ("contributor_id");

CREATE INDEX "recording_owner_confirmations_record_id_index" on "recording_owner_confirmations" ("record_id");

CREATE INDEX "recording_owner_confirmations_report_id_index" on "recording_owner_confirmations" ("report_id");

CREATE UNIQUE INDEX "report_task_options_normalized_key_unique" on "report_task_options" ("normalized_key");

CREATE INDEX "sessions_last_activity_index" on "sessions" ("last_activity");

CREATE INDEX "sessions_user_id_index" on "sessions" ("user_id");

CREATE UNIQUE INDEX "shift_days_work_date_unique" on "shift_days" ("work_date");

CREATE UNIQUE INDEX "shift_settings_setting_key_unique" on "shift_settings" ("setting_key");

CREATE UNIQUE INDEX "studio_accounts_email_unique" on "studio_accounts" ("email");

CREATE INDEX "studio_recordings_assigned_contributor_id_index" on "studio_recordings" ("assigned_contributor_id");

CREATE INDEX "studio_recordings_assigned_worker_name_index" on "studio_recordings" ("assigned_worker_name");

CREATE INDEX "studio_recordings_manual_payment_status_index" on "studio_recordings" ("manual_payment_status");

CREATE INDEX "studio_recordings_participant_email_index" on "studio_recordings" ("participant_email");

CREATE INDEX "studio_recordings_recorded_at_index" on "studio_recordings" ("recorded_at");

CREATE UNIQUE INDEX "users_contributor_id_unique" on "users" ("contributor_id");

CREATE UNIQUE INDEX "users_email_unique" on "users" ("email");
