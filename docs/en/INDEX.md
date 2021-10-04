## what it solves
https://github.com/silverstripe/silverstripe-framework/issues/3092

## How this works:

 1. when you run a `dev/build` on the command line, it creates a record in the database.
 2. it also sets a "open a special link on the front-end" to flush from the front-end. This special link can verify it comes from the back-end using a secret code.
 3. this front-end request then runs a flush of sorts.

## how to use it

run `sake dev/build` from the command line and the front-end will be flushed.
