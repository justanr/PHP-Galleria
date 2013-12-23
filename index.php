<?php

    include "functions.php";

    $dbh = get_dbh();

    $menu = get_all_galleries($dbh);

    $gallery = (isset($_GET['url']) ? identify_gallery($dbh, $_GET['url']) : get_most_recent_gallery($dbh));

    $pictures = get_pictures_from_gallery($dbh, $gallery['g_id']);

    $domain = 'http://yoursite.com';

    # I like storing some of my patterns in an array.
    $patterns = array(
        "menu" => sprintf('<li><a href="%s/gallery/%%s">%%s</a></li>', $domain ),
        "image" => sprintf("%s/galleries/%%s/%%s", $domain)
    );
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Example PHP-Galleria Site</title>
        <link rel="stylesheet" href="http://yui.yahooapis.com/pure/0.2.0/pure-min.css">        
        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.js"></script>
        <script src="http://<?php echo $domain; ?>/galleria/galleria-1.2.9.min.js"></script>

        <script>
        <?php
            /*
                Too lazy to set up a proper AJAX call, so I did this instead.
            */
            echo "var data = " . encode_gallery($pictures, $domain, $gallery['g_dir']) . "\r\n"; 
        ?>
        </script>

        <style>
            #galleria {
                width: 700px;
                height: 600px; 
                margin: 0 5em; 
            }
    
        </style>
    </head>

    <body>
        <div class="pure-g-r">
            <div class="pure-u-1-1">
                <h1>Example PHP-Galleria Site</h1>
            </div>

            <div class="pure-u-1-6">
                <div id="menu" class="pure-menu pure-menu-open" style="margin-right: 5px;">
                    <ul>
                    <?php
                        foreach ($menu AS $item) {
                            printf($patterns['menu'], $item['g_url'], $item['g_name']);
                        }
                    ?>
                    </ul>
                </div>
            </div>
            
            <div class="pure-u-5-6">
                <div id="galleria"></div>
            </div>

            <script>
                <?php
                    printf('Galleria.loadTheme("%s/galleria/themes/classic/galleria.classic.min.js");', $domain);
                ?>
                Galleria.run('#galleria', {
                    dataSource: data,
                });
            </script>
        </div>
    </body>
</html>
