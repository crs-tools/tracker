#!/bin/sh

if [ -z "$2" ] ; then
	echo "Usage:\n\n\t$0 <database name> <targetfile>\n\n" >&2
	exit 1
fi

dump=`which pg_dump`

if [ ! -x  "$dump" ] ; then
	echo "No pg_dump binary found in path!" >&2
	exit 2
fi

dbname="$1"
targetfile="$2"


# dump the schema first

$dump --create --clean --if-exists --schema-only "$dbname" > "$targetfile"

# dump data of tables in correct order, i.e. the order that is necessary for successful
# restore due to dependencies

for table in tbl_ticket_state tbl_handle tbl_user tbl_worker_group tbl_worker tbl_encoding_profile \
	tbl_encoding_profile_version tbl_encoding_profile_property tbl_project tbl_project_language \
	tbl_project_property tbl_project_ticket_state tbl_project_worker_group \
	tbl_project_worker_group_filter tbl_project_encoding_profile tbl_user_project_restrictions \
	tbl_import tbl_ticket tbl_ticket_property tbl_comment tbl_log ; do

	$dump --data-only --disable-triggers --table=$table "$dbname" >> "$targetfile"
done

