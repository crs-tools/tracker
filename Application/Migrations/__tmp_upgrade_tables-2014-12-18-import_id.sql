BEGIN;

ALTER TABLE "public"."tbl_ticket" ADD COLUMN "import_id" int8;
ALTER TABLE "public"."tbl_ticket" ADD CONSTRAINT "tbl_ticket_import_fk" FOREIGN KEY ("import_id") REFERENCES "public"."tbl_import" ("id") ON UPDATE CASCADE ON DELETE SET NULL;

COMMIT;