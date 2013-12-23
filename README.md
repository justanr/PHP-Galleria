PHP-Galleria
============

Just a simple script I whipped up for creating simple PHP powered [galleria.js](http://galleria.io) sites.

It's only dependencies are PDO for PHP and Galleria on the client side. That's it (well, and jQuery, because galleria depends on that). No frameworks, not a lot of overhead. Just a handful of PHP functions and a tiny bit of javascript know how.

`index.php` is included as an example implementation of it.

Know How Notes
==============

There's a sample conn.json included, make sure you move that above your www directory unless you like having your database access info available to everyone.

Creating the tables for the database isn't hard, Postgres, MySQL and SQLite all support piping in a SQL file on the command line. If you can't do that, pop it into your favorite GUI (phpMyAdmin, etc).

I've tested this with MySQL and PostGres, I'm unsure about SQLite because I'm not sure on it's support of triggers. Speaking of tests, there's no formal tests laying around because I despise all of the PHP testing suites I've used (especially any that rely on Selenium). The only reassurance I can offer is that I tested it myself and have had no problems with the implementations.

The History of the Thing
========================

I wrote this code about a year ago, when my girlfriend said she needed a site for her photography. Since she said I could open source it when I was done, I felt like I should.

Not a lot of the code is good, but most of it was written under the influence. There's an upload script in Python that I wrote as well but it's too terrible to share. Maybe I'll clean it up one day and add it here, but that's so far down my todo list it's comical to suggest.

I'm also in the process of posting it Python via Flask currently.
