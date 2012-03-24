<?php

require_once('config.inc.php');
require_once('conexion.inc.php');
require_once('modelos.inc.php');
require_once('util.inc.php');

$posts = rb_post_todos();
// fake data
/*$posts = array(
array('fecha_pub' => 'fecha pub','slug' => 's-l-u-g','titulo' => 'tituuuuulllooooo','tags' => array('tag1','tag2','tag3')),
array('fecha_pub' => 'fecha pub','slug' => 's-l-u-g','titulo' => 'tituuuuulllooooo','tags' => array('tag1','tag2','tag3')),
);*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" 
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Redis Blog Demo</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="style.css" />
</head>
    <body>
        <h1><?php echo $CONF['titulo_blog'] ?></h1>
        <div class="main">
        <h2>Ultimos posts</h2>
        <?php if (count($posts) > 0) { ?>
            <?php foreach($posts as $post) { ?>
            <div class="post">
                <p class="title">
                    <?= date("Y-m-d", strtotime($post['fecha_pub'])) ?>
                    <a href="<?= $CONF['base_url'] ?>post.php/<?= $post['slug'] ?>">
                        <?= $post['titulo']; ?>
                    </a>
                </p>
                <p class="tagline">
                    <i>Tags:
                        <?php foreach($post['tags'] as $tag) { ?>
                            <a href="<?= $CONF['base_url'] ?>tag.php/<?= urlencode($tag) ?>"><?= $tag ?></a> 
                        <?php } ?>
                    </i>
                </p>
            </div>
            <?php } ?>
        <?php } else { ?>
            <p><i>No hay posts en en blog por el momento.</i></p>
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
