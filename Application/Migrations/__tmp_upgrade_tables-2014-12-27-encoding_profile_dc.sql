BEGIN;

ALTER TABLE "public"."tbl_encoding_profile" DROP COLUMN "extension",
	DROP COLUMN "mirror_folder";

COMMIT;