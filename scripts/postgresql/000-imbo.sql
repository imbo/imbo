CREATE TABLE IF NOT EXISTS public.imageinfo (
  "id" serial NOT NULL,
  "user" character varying COLLATE pg_catalog."default" NOT NULL,
  "imageIdentifier" character varying COLLATE pg_catalog."default" NOT NULL,
  "size" integer NOT NULL,
  "extension" character varying COLLATE pg_catalog."default" NOT NULL,
  "mime" character varying COLLATE pg_catalog."default" NOT NULL,
  "added" bigint NOT NULL,
  "updated" bigint NOT NULL,
  "width" integer NOT NULL,
  "height" integer NOT NULL,
  "checksum" character varying(32) COLLATE pg_catalog."default" NOT NULL,
  "originalChecksum" character varying(32) COLLATE pg_catalog."default" NOT NULL,
  "metadata" json,
  CONSTRAINT "imageinfo_pkey" PRIMARY KEY ("id"),
  CONSTRAINT "imageinfo_user_imageIdentifier_key" UNIQUE ("user", "imageIdentifier")
) TABLESPACE pg_default;

CREATE TABLE IF NOT EXISTS public.imagevariations (
  "user" character varying COLLATE pg_catalog."default" NOT NULL,
  "imageIdentifier" character varying COLLATE pg_catalog."default" NOT NULL,
  "width" integer NOT NULL,
  "height" integer NOT NULL,
  "added" bigint NOT NULL,
  CONSTRAINT "imagevariations_pkey" PRIMARY KEY ("user", "imageIdentifier", "width")
) TABLESPACE pg_default;

CREATE TABLE IF NOT EXISTS public.shorturl (
  "shortUrlId" character varying(7) COLLATE pg_catalog."default" NOT NULL,
  "user" character varying COLLATE pg_catalog."default" NOT NULL,
  "imageIdentifier" character varying COLLATE pg_catalog."default" NOT NULL,
  "extension" character varying COLLATE pg_catalog."default",
  "query" text COLLATE pg_catalog."default" NOT NULL,
  CONSTRAINT "shorturl_pkey" PRIMARY KEY ("shortUrlId")
) TABLESPACE pg_default;

CREATE INDEX IF NOT EXISTS "params" ON public.shorturl USING btree (
  "user" COLLATE pg_catalog."default" ASC NULLS LAST,
  "imageIdentifier" COLLATE pg_catalog."default" ASC NULLS LAST,
  "extension" COLLATE pg_catalog."default" ASC NULLS LAST,
  "query" COLLATE pg_catalog."default" ASC NULLS LAST
) TABLESPACE pg_default;
