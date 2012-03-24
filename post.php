<?php

require_once('config.inc.php');
require_once('conexion.inc.php');
require_once('modelos.inc.php');
require_once('util.inc.php');

$slug = rb_slug_desde_url();
$post = rb_post_por_slug($slug);

// fake date
/*$post = array('fecha_pub' => 'fecha pub','slug' => 's-l-u-g','titulo' => 'tituuuuulllooooo','tags' => array('tag1','tag2','tag3'),'contenido'=>'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum');*/

/* El post no existe, devolvemos un error 404 */
if (is_array($post) == False) {
    header('HTTP/1.0 404 Not Found');
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" 
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Redis Blog Demo</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="/style.css" />
</head>
    <body>

        <h1><?php echo $CONF['titulo_blog'] ?></h1>

        <div class="main">

            <?php if (is_array($post)) { ?>
            <h2><?= $post['titulo']; ?></h2>
            <p><i>Tags:
            <?php foreach($post['tags'] as $tag) { ?>
            <a href="<?= $CONF['base_url'] ?>tag.php/<?= urlencode($tag) ?>"><?= $tag ?></a> 
            <?php } ?>
            <p><b>Fecha y hora de publicaci&oacute;n:</b> <?= $post['fecha_pub'] ?></p>
            <?= $post['contenido'] ?>
            <?php } else { ?>
            <p><i>El post no existe.</i</p>
            <?php } ?>

        </div>

        <div class="sidebar">
            <div class="tag-cloud">
                <h2>Nube de tags</h2>
                <?php include('nube.inc.php'); ?>
            </div>
            <div class="archive-block">
                <h2>Archivo</h2>
                <?php include('archivo.inc.php'); ?>
            </div>
        </div>


	</body>
</html>
