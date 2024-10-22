DO
$do$
BEGIN
   IF NOT EXISTS (
      SELECT FROM pg_catalog.pg_roles
      WHERE  rolname = 'superuser') THEN

      CREATE ROLE superuser WITH SUPERUSER LOGIN PASSWORD '${POSTGRES_ROOT_PASSWORD}';
   END IF;
END
$do$;