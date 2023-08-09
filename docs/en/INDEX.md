## easy way

`SS_FLUSH_ON_DEPLOY` and set this to a file that changes on deploy

## what it solves
https://github.com/silverstripe/silverstripe-framework/issues/3092

## How this works:

 1. when you run a `sake dev/build flush=all` on the command line, it creates a record in the database.
 2. at the end of the run, it opens a link on the front-end to flush from the front-end. 
 3. the front-end can run this savely, because it contains a secret hash
 4. this front-end request then runs a flush of sorts.

## how to use it

run `sake dev flush=all` from the command line and the front-end will be flushed.
