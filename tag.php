<?php

require_once('config.inc.php');
require_once('conexion.inc.php');
require_once('modelos.inc.php');
require_once('util.inc.php');

$tag = rb_tag_desde_url();

$posts = rb_tag_posts($tag);

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
            <h2>Post con tag '<?= $tag ?>'</h2>
            <?php if (count($posts) > 0) { ?>
            <?php foreach($posts as $post) { ?>
            <p><?= date("Y-m-d", strtotime($post['fecha_pub'])) ?> <a href="<?= $CONF['base_url'] ?>post.php/<?= $post['slug'] ?>"><?= $post['titulo']; ?></a></p>
            <p><i>Tags:
            <?php foreach($post['tags'] as $tag) { ?>
            <a href="<?= $CONF['base_url'] ?>tag.php/<?= urlencode($tag) ?>"><?= $tag ?></a> 
            <?php } ?>
            <?php } ?>
            <?php } else { ?>
            <p><i>No hay posts en en blog para esta etiqueta por el momento.</i</p>
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
