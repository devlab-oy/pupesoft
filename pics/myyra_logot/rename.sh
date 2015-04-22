#!/bin/bash
for file in *;
do
mv "$file" `echo "$file" | sed 's/[^A-Za-z0-9_.]/_/g'`;
done;
