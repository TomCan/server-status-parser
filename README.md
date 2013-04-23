server-status-parser
====================
PHP script to parse the output of Apache server-status to allow easier interpretation. Basically, I wanted to see who is hammering my server from time to time :)

The input file should be a server-status page with ExtendedStatus turned on
If you don't have one, I've included one from http://www.apache.org/server-status
If you want some more interesting ones, just Google "Apache Server status for" and you'll probably find some that are freely available

Features already implemented
============================
- Whois on Client IP addresses

Features to be implemented
==========================
- parameterize
- sorting
- remote input files with optional auth options
- I'm pretty sure I'll come up with some more features once I get going

TODO's
======
- fix all TODO's in code (duh)
- tidy it up a bit
