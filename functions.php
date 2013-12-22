<?php

    /*
    Frameworkless functions.

    Simple grab a database connection, parse a config file and set up the database connection uri.

    I was temporarily into Hungarian notation when writing this, hence the typed prefixes on the variables.
    I later decided it was stupid but didn't change it. rj stands for Raw JSON
    */

    function get_dbh($conn = '../conn.json') {
        $data = build_conn_string(get_conn_details($conn));

        return new PDO($data['dsn'], $data['user'], $data['passwd']);
    }

    function get_conn_details($conn = '../conn.json') {
        if (file_exists($conn)) {
            $rjConnData = file_get_contents($conn);

            return json_decode($rjConnData, True);
        } else {
            return False;
        }
    }

    function build_conn_string($aData) {
        /*
            Behold array checking.
            I don't know why I didn't just let this fail silently.
        */
        if ( is_array($aData) && isset($aData['dsn']) && isset($aData['user']) && isset($aData['passwd']) && isset($aData['db']) && isset($aData['host']) ) {
            return array('dsn' => sprintf('%s:host=%s;dbname=%s;%s', $aData['dsn'], $aData['host'], $aData['db'], (isset($aData['port']) ? sprintf('port=%s', $aData['port']) : '')),
                    'user' => $aData['user'], 'passwd' => $aData['passwd']);
        } else {
            return False;
        }
    }


    /*
    Models as functions.

    Simple database calls modeled as functions.
    */

    function get_all_galleries(PDO $dbh) {
        /*
            This ugly bit of SQL retrieves all the galleries' info, most recent upload and the
            count of all images in them. Subqueries are pretty awesome sometimes.
        */
        $sql = 'SELECT g.g_id, g.g_url, g.g_name,
                (SELECT p.thumb FROM pictures p WHERE p.g_id = g.g_id ORDER BY p.p_id DESC LIMIT 1) AS cover,
                (SELECT count(c.p_id) AS count FROM pictures c WHERE c.g_id = g.g_id) AS count
                FROM galleries g
                ORDER BY g.g_name ASC;';
        return $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }


    function get_most_recent_gallery($dbh) {
        $sql = 'SELECT g_id, g_name, g_url, g_dir FROM galleries ORDER BY updated DESC LIMIT 1';
        return $dbh->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    /*
    Should really find a way to combine all of these.

    ######################################################################################
    #                                     UNTESTED                                       #
    #                            That's why it's commented out.                          #
    ######################################################################################

    get_gallery_by_thing(PDO $dbh, $thing=array()) {
        
        $valid_things = array('g_id', 'g_dir', 'g_name', 'g_url');

        if in_array($thing['field'], $valid_things) {
            $sql = 'SELECT g_id, g_url, g_dir, g_name FROM galleries WHERE :%s = :field;';
            $sql = sprintf($sql, $thing);
            $sth = $dbh->prepare(sql);
            $sth->execute(thing);

            return $sth->fetch(PDO::FETCH_ASSOC);

            
        } else {
            return get_most_recent_gallery($dbh);
        }

    }
    */


    # Literally
    function get_gallery_by_id(PDO $dbh, $id) {
        $sql = 'SELECT g_id, g_url, g_name, g_dir FROM galleries WHERE g_id = ?;';
        $sth = $dbh->prepare($sql);
        $sth->execute(array($id));

        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    # the
    function get_gallery_by_name(PDO $dbh, $name) {
        $sql = 'SELECT g_id, g_url, g_name, g_dir FROM galleries WHERE g_name = ?;';
        $sth = $dbh->prepare($sql);
        $sth->execute(array($name));

        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    # same
    function get_gallery_by_url(PDO $dbh, $url) {
        $sql = 'SELECT g_id, g_url, g_name, g_dir FROM galleries WHERE g_url = ?;';
        $sth = $dbh->prepare($sql);
        $sth->execute(array($url));

        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    # thing.
    function get_gallery_by_dir(PDO $dbh, $dir) {
        $sql = 'SELECT g_id, g_url, g_name, g_dir FROM galleries WHERE g_dir = ?;';
        $sth = $dbh->prepare($sql);
        $sth->execute(array($dir));

        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    /*
    Look at this wonderful piece of hackery. It came to me in a drunken haze.

    PHP (and some other C like languages) allows you to do assignments in
    conditional statements and then checks to see if your assignment was
    true or false.

    Here, I abuse this with a switch. It's sad that this was my first
    attempt at unifying the get_gallery_by_x functions and probably
    sadder that it works just like you'd expect.
    */

    function identify_gallery(PDO $dbh, $thing) {
        switch(True) {
            case $data = get_gallery_by_id($dbh, $thing):
                break;
            case $data = get_gallery_by_name($dbh, $thing):
                break;
            case $data = get_gallery_by_url($dbh, $thing):
                break;
            case $data = get_gallery_by_dir($dbh, $thing):
                break;
            default:
                $data = get_most_recent_gallery($dbh);
        }

        return $data;
    }

    function get_pictures_from_gallery(PDO $dbh, $g_id) {
        if ($data = identify_gallery($dbh, $g_id)) {
            $sql = 'SELECT thumb, image, big, title, description FROM pictures WHERE g_id = ? ORDER BY p_id ASC;';
            $sth = $dbh->prepare($sql);
            $sth->execute(array($data['g_id']));

            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } else {
            /*
                Or else what? I never filled this in.
            */
        }
    }


    /*
        Encode the associative array returned by one of the get_gallery_by_x functions and turns it into
        valid JSON.
   */
    function encode_gallery($data, $domain, $g_dir) {
        if(!is_array($data)) {
            return False;
        } else {
            foreach ( $data AS &$row ) {
                foreach( $row AS $k=>$v ) {
                    /*
                        There's a chance we'll run into NULL data fields from the database.
                        So those need to be unset. Note that Null is processed as a string
                        and not as an actual Null value. This took a while to debug.

                        $row is passed as a reference instead of just a copy of the data key-value store
                        to accomplish this.
                    */
                    if ($v == "Null") {
                        unset($row[$k]);
                    }

                    if (in_array($k, array("big", "image", "thumb"))) {
                        /*
                            Some of the fields are actually image files that need to be mapped
                            to the correct directory.
                        */
                        $row[$k] = sprintf("%s/galleries/%s/%s", $domain, $g_dir, $v);
                    }
                }
            }

            $data = json_encode($data);

            /*
                Strip out escaping slashes. I can't really fault PHP for playing it safe here.
                But again, this took quite a while to debug.

                I wrote this running PHP 5.3, but in PHP 5.4 json_encode accepts an options
                parameter JSON_UNESCAPED_SLASHES which avoids this.
            */
            return str_replace("\/", "/", $data);
        }
    }
