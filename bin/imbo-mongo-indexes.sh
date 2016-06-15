#!/bin/bash

printf "Usage: $0 [mongo-host[:port]] [database-name] [non-interactive]\n\n"

# Get mongodb host from first arg
if [ -z ${1+x} ]; then
    echo "Mongo host is undefined, assuming localhost.";
    MONGOHOST="127.0.0.1:27017"
else
    echo "Mongo host is set to '$1'";
    MONGOHOST="$1"
fi

# Get mongodb database name from second arg
if [ -z ${2+x} ]; then
    echo "Database name is undefined, assuming imbo.";
    DBNAME="imbo"
else
    echo "Database name is set to '$2'";
    DBNAME="$2"
fi

# confirm values if interactive
if [ "$#" -gt "2" ]; then
    if [ $3 == "non-interactive" ]; then
        echo "Not interactive, moving on."
    fi
else
    echo "Are the above values correct?"
    read -p "(yes/no): " CONFIRM
    if [ ! ${CONFIRM} == "yes" ]; then
        exit 2
    fi
fi

mongo ${MONGOHOST}/${DBNAME} <<EOF
 db.image.ensureIndex({"user": 1, "imageIdentifier": 1}, { background: true })
 db.image.ensureIndex({"user": 1, "added": -1}, { background: true })
 db.image.ensureIndex({"user": 1, "updated": -1}, { background: true })
EOF
