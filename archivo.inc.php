<?php $archivo = rb_archivo();
// fake data
/*$archivo = array(
    'mes1' => array('anio'=>'2010','mes'=>'07','num_posts'=>15,'formateado'=>'el messss'),
    'mes2' => array('anio'=>'2010','mes'=>'07','num_posts'=>15,'formateado'=>'el messss'),
    'mes3' => array('anio'=>'2010','mes'=>'07','num_posts'=>15,'formateado'=>'el messss'),
    'mes4' => array('anio'=>'2010','mes'=>'07','num_posts'=>15,'formateado'=>'el messss'),
    'mes5' => array('anio'=>'2010','mes'=>'07','num_posts'=>15,'formateado'=>'el messss')
);*/
?>
<ul>
    <?php foreach($archivo as $mes=>$metadata) { ?>
        <li>
            <a href="<?= $CONF['base_url'] ?>archivo.php/<?= $metadata['anio'] ?>/<?= $metadata['mes'] ?>/">
                <?= $metadata['formateado'] ?>
            </a>
            (<?= $metadata['num_posts'] ?>)
        </li>
    <?php } ?>
</ul>
