<?php $nube = rb_nube_tags();
// fake data
/* $nube = array(
    'tag1' => array('estilo'=>'tag_tamanho_1','base_url'=>'baseurl','num_posts'=>14),
    'tag2' => array('estilo'=>'tag_tamanho_2','base_url'=>'baseurl','num_posts'=>12),
    'tag3' => array('estilo'=>'tag_tamanho_3','base_url'=>'baseurl','num_posts'=>18),
    'tag4' => array('estilo'=>'tag_tamanho_4','base_url'=>'baseurl','num_posts'=>12),
    'tag5' => array('estilo'=>'tag_tamanho_5','base_url'=>'baseurl','num_posts'=>176)
);*/
?>
<p>
    <?php foreach($nube as $tag => $metadata) { ?>
        <span class="<?= $metadata['estilo'] ?>"><a href="<?= $CONF['base_url'] ?>tag.php/<?= urlencode($tag) ?>"><?= $tag ?></a> (<?= $metadata['num_posts'] ?>)</span> 
    <?php } ?>
</p>
