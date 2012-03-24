<?php

/* Artículo (Post) */

function rb_generar_slug($cadena, $separador="-") {
    $mapa = array(
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
        'ñ' => 'nh'
    );
    $cadena = strtolower(trim($cadena));
    foreach($mapa as $caracter=>$reemplazo) {
        $cadena = str_replace($caracter, $reemplazo, $cadena);
    }
    $cadena = preg_replace('/[^a-z0-9-]/', $separador, $cadena);
    $partes = explode($separador, $cadena);
    foreach($partes as $k=>$v) {
        if (strlen($v) == 0) {
            unset($partes[$k]);
        }
    }
    $cadena = implode($separador, $partes);
    return $cadena;
}

function rb_max_timestamp() {
    return strtotime('2038-01-18 00:00:00');
}

function rb_post_nuevo_id() {
   return intval($GLOBALS['redis']->incr('post:id:contador')); 
}

function rb_post_verificar($datos) {
    $campos_obligatorios = array(
        'titulo',
        'contenido',
        'tags'
    );
    foreach($campos_obligatorios as $campo) {
        if (array_key_exists($campo, $datos) === False) {
            die("El campo $campo es obligatorio al crear un post.");
        }
    }
    return $datos;
}

function rb_post_guardar($post_id, $datos) {
    $llave = sprintf("post:id:%d", $post_id);
    foreach($datos as $campo=>$valor) {
        $GLOBALS['redis']->hset($llave, $campo, serialize($valor));
    }
    return True;
}

function rb_post_crear($datos) {

    /* Validamos campos obligatorios */
    rb_post_verificar($datos);

    /* Colocamos campos por defecto */
    if (array_key_exists('slug', $datos) === False) {
        $datos['slug'] = rb_generar_slug($datos['titulo']);
    }
    if (array_key_exists('fecha_pub', $datos) == False) {
        $datos['fecha_pub'] = date("Y-m-d H:i:s");
    }

    /* Colocamos campos automáticos */
    $datos['fecha_cre'] = date("Y-m-d H:i:s");

    if (rb_post_existe_slug($datos['slug'])) {
        die("Ya existe un post con el slug '".$datos['slug']."'");
    }

    /* El post es nuevo, generamos un nuevo identificador */
    $post_id = rb_post_nuevo_id();
    
    /* Las tags se guardan en un conjunto aparte */
    $tags = $datos['tags'];
    unset($datos['tags']);

    rb_post_guardar($post_id, $datos);

    rb_post_asociar_tags($post_id, $tags, $datos['fecha_pub'], False);
    rb_post_modificar_indices($post_id, $datos);

    return $post_id;
}

function rb_post_modificar($post_id, $datos) {
    /* Validamos campos obligatorios */
    rb_post_verificar($datos);

    /* Colocamos campos por defecto */
    if (array_key_exists('slug', $datos) === False) {
        $datos['slug'] = rb_generar_slug($datos['titulo']);
    }

    /* Colocamos campos automáticos */
    $datos['fecha_mod'] = date("Y-m-d H:i:s");

    /* Recuperamos el id de algún post con el mismo slug */
    $post_id_con_slug = rb_post_id_por_slug($datos['slug']);

    /* Si existe un post con ese slug, debe ser distinto */
    if ($post_id_con_slug) {
        if ($post_id != $post_id_con_slug) {
            die("Ya existe un post con el slug '$slug'");
        }
    }

    /* Eliminamos los índices de la versión anterior */
    $datos_anteriores = rb_post_recuperar_datos($post_id);
    rb_post_eliminar_indices($post_id, $datos_anteriores);

    /* Las tags se guardan en un conjunto aparte */
    $tags = $datos['tags'];
    unset($datos['tags']);

    rb_post_guardar($post_id, $datos);

    /* Volvemos a asociar tags*/
    rb_post_asociar_tags($post_id, $tags, $datos['fecha_pub']);

    /* Volvemos a crear los indices */
    rb_post_modificar_indices($post_id, $datos);
}

function rb_post_eliminar($post_id) {

    /* Eliminamos los índices  */
    $datos = rb_post_recuperar_datos($post_id);
    rb_post_eliminar_indices($post_id, $datos);

    /* Desasociamos tags */
    rb_post_desasociar_tags($post_id);

    $llave = sprintf("post:id:%d", $post_id);
    $GLOBALS['redis']->delete($llave);
    
}

function rb_post_recuperar_tags($post_id) {
    $resultado = array();
    $llave = sprintf("post:id:%d:tags", $post_id);
    $elementos = $GLOBALS['redis']->sMembers($llave);
    foreach($elementos as $e) {
        $resultado[] = $e;
    }
    return $resultado;
}

function rb_post_recuperar_slug($post_id) {
    $llave = sprintf("post:id:%d", $post_id);
    $resultado = $GLOBALS['redis']->hGet($llave, 'slug');
    if ($resultado) {
        $resultado = unserialize($resultado);
    }
    return $resultado;
}

function rb_post_existe_slug($slug) {
    $llave = sprintf("post:slug:%s", sha1($slug));
    return $GLOBALS['redis']->get($llave) !== False;
}

function rb_post_asociar_tag($post_id, $tag) {
    $llave = sprintf("post:id:%d:tags", $post_id);
    $GLOBALS['redis']->sAdd($llave, $tag);
}

function rb_post_desasociar_tag($post_id, $tag) {
    $llave = sprintf("post:id:%d:tags", $post_id);
    return $GLOBALS['redis']->sRemove($llave, $tag);
}

function rb_post_asociar_tags($post_id, $tags, $fecha, $nuevo=False) {

    /* Convertimos la lista de tags separados por comas 
       en un array */
    $tags = explode(",", $tags);

    /* Retiramos espacios en blanco al inicio y al final
       de cada tag */
    foreach($tags as $k=>$v) {
        $tags[$k] = trim($v);
    }

    if ($nuevo) {
        $por_agregar = $tags;
        $por_retirar = array();
    } else {
        /* Calculamos los tags por retirar utilizando 
           una operación de diferencia de conjuntos */
        $tags_anteriores = rb_post_recuperar_tags($post_id);
        $por_retirar = array_diff($tags_anteriores, $tags); 
        $por_agregar = array_diff($tags, $tags_anteriores);
    }

    foreach($por_agregar as $tag) {
        /* Asociamos el tag al post */
        rb_post_asociar_tag($post_id, $tag);
        /* Asociamos el post al tag */
        rb_tag_asociar_post($tag, $post_id, $fecha);
    }
    foreach($por_retirar as $tag) {
        /* Desasociamos el tag al post */
        rb_post_desasociar_tag($post_id, $tag);
        /* Desasociamos el post al tag */
        rb_tag_desasociar_post($tag, $post_id);
    }
}

function rb_post_desasociar_tags($post_id) {
    $tags = rb_post_recuperar_tags($post_id);
    if (is_array($tags)) {
        foreach($tags as $tag) {
            rb_post_desasociar_tag($post_id, $tag);
        }
    }
}   

function rb_post_modificar_indices($post_id, $datos) {
    /* Indice por slug */
    rb_post_indexar_slug($post_id, $datos['slug']);
    /* Indice por fecha */
    rb_post_indexar_fecha($post_id, $datos['fecha_pub']);
    /* Indice por mes y año */
    rb_post_indexar_mes($post_id, $datos['fecha_pub']);
}

function rb_post_eliminar_indices($post_id, $datos) {
    /* Indice por slug */
    rb_post_desindexar_slug($post_id, $datos['slug']);
    /* Indice por fecha */
    rb_post_desindexar_fecha($post_id, $datos['fecha_pub']);
    /* Indice por mes y año */
    rb_post_desindexar_mes($post_id, $datos['fecha_pub']);
}

function rb_post_indexar_slug($post_id, $slug) {
    $llave = sprintf("post:slug:%s", sha1($slug));
    $GLOBALS['redis']->set($llave, $post_id);
}

function rb_post_desindexar_slug($slug) {
    $llave = sprintf("post:slug:%s", sha1($slug));
    $GLOBALS['redis']->delete($llave);
}

function rb_fecha_calcular_score($fecha) {
    return strtotime($fecha);
}

function rb_post_indexar_mes($post_id, $fecha) {
    $mes = date("m", strtotime($fecha));
    $anio = date("Y", strtotime($fecha));
    $llave = sprintf("posts:anio:%04d:mes:%02d", $anio, $mes);
    $score = rb_fecha_calcular_score($fecha);
    $GLOBALS['redis']->zAdd($llave, $score, $post_id);
    if (rb_mes_existe($mes, $anio) == False) {
        rb_mes_crear($mes, $anio);
    }
}

function rb_post_desindexar_mes($post_id, $fecha) {
    $mes = date("m", strtotime($fecha));
    $anio = date("Y", strtotime($fecha));
    $llave = sprintf("posts:anio:%04d:mes:%02d", $anio, $mes);
    $GLOBALS['redis']->zRemove($llave, $post_id);
    if (rb_mes_existen_posts($mes, $anio) == False) {
        rb_mes_eliminar($mes, $anio);
    }
}

function rb_post_indexar_fecha($post_id, $fecha) {
    $llave = 'posts';
    $score = rb_fecha_calcular_score($fecha);
    return $GLOBALS['redis']->zAdd($llave, $score, $post_id);
}

function rb_post_desindexar_fecha($post_id) {
    $llave = 'posts';
    return $GLOBALS['redis']->zRemove($llave, $post_id);
}

function rb_post_todos($offset = NULL, $count = NULL, $reverse = True) {
    $resultado = array();
    $opciones = rb_opciones_limit($offset, $count);
    $max = rb_max_timestamp();
    $llave = 'posts';
    if ($reverse) {
        if (is_null($offset)) {
            $offset = 0;
        }
        if (is_null($count)) {
            if (array_key_exists('max_posts_por_omision', $GLOBALS['CONF'])) {
                $count = intval($GLOBALS['CONF']['max_posts_por_omision']);
            } else {
                $count = 5;
            }
        }
        $min = $offset;
        $max = $offset + $count;
        $resultado = $GLOBALS['redis']->zReverseRange($llave, $min, $max); 
    } else {
        $resultado = $GLOBALS['redis']->zRangeByScore($llave, 0, $max_epoch, $opciones);
    }
    if (is_array($resultado)) {
        foreach($resultado as $k=>$post_id) {
            $resultado[$k] = rb_post_por_id($post_id);
        }
    }
    return $resultado;
}

function rb_post_recuperar_datos($post_id) {
    $llave = sprintf("post:id:%d", $post_id);
    $resultado = $GLOBALS['redis']->hGetAll($llave);
    if (is_array($resultado)) {
        foreach($resultado as $k=>$v) {
            $resultado[$k] = unserialize($v);
        }
    }
    return $resultado;
}

function rb_post_por_id($post_id) {
    $resultado = rb_post_recuperar_datos($post_id);
    if (is_array($resultado)) {
        $resultado = array_merge(array("id"=>$post_id), $resultado);
        $resultado['tags'] = rb_post_recuperar_tags($post_id);
    }
    return $resultado;
}

function rb_post_id_por_slug($slug) {
    $llave = sprintf("post:slug:%s", sha1($slug));
    return $GLOBALS['redis']->get($llave);
}

function rb_post_por_slug($slug) {
    $resultado = rb_post_id_por_slug($slug);
    /* Si el resultado no es False, la llave existe */
    if ($resultado) {
        return rb_post_por_id($resultado);
    }
    return $resultado;
}

function rb_tag_existe($tag) {
    $llave = 'tags';
    return $GLOBALS['redis']->sContains($llave, $tag);
}

function rb_tag_crear($tag) {
    $llave = 'tags';
    return $GLOBALS['redis']->sAdd($llave, $tag);
}

function rb_tag_eliminar($tag) {
    $llave = 'tags';
    return $GLOBALS['redis']->sRemove($llave, $tag);
}

function rb_tag_listado() {
    $llave = 'tags';
    /* Recuperamos todos los elementos del conjunto */
    $resultado = $GLOBALS['redis']->sMembers($llave);
    return $resultado;
}

function rb_tag_contar_posts($tag) {
    $llave = sprintf("tag:%s:posts", $tag);
    return $GLOBALS['redis']->zSize($llave);
}

function rb_tag_existen_posts($tag) {
    return rb_tag_contar_posts($tag) > 0;
}

function rb_tag_asociar_post($tag, $post_id, $fecha) {
    /* Asociamos el post al tag */
    rb_tag_crear($tag);
    $llave = sprintf("tag:%s:posts", $tag);
    $score = rb_fecha_calcular_score($fecha);
    $GLOBALS['redis']->zAdd($llave, $score, $post_id);
}

function rb_tag_desasociar_post($tag, $post_id) {
    /* Desasociamos el post al tag */
    $llave = sprintf("tag:%s:posts", $tag);
    $GLOBALS['redis']->zRemove($llave, $post_id);
    /* Si el tag se queda sin posts, lo eliminamos */
    if (rb_tag_existen_posts($tag) == False) {
        rb_tag_eliminar($tag);
    }
}

function rb_opciones_limit($offset = NULL, $count = NULL) {
    if (is_integer($offset) && is_integer($count)) {
        $resultado = array('limit' => array($offset, $count));
    } else {
        $resultado = array();
    }
    return $resultado;
}

function rb_tag_posts($tag, $offset = NULL, $count = NULL, $reverse = True) {
    $resultado = array();
    $opciones = rb_opciones_limit($offset, $count);
    $max_epoch = rb_max_timestamp();
    $llave = sprintf("tag:%s:posts", $tag);
    if ($reverse) {
        if (is_null($offset)) {
            $offset = 0;
        }
        if (is_null($count)) {
            if (array_key_exists('max_posts_por_omision', $GLOBALS['CONF'])) {
                $count = intval($GLOBALS['CONF']['max_posts_por_omision']);
            } else {
                $count = 5;
            }
        }
        $min = $offset;
        $max = $offset + $count;
        $resultado = $GLOBALS['redis']->zReverseRange($llave, $min, $max); 
    } else {
        $resultado = $GLOBALS['redis']->zRangeByScore($llave, 0, $max_epoch, $opciones);
    }
    if (is_array($resultado)) {
        foreach($resultado as $k=>$post_id) {
            $resultado[$k] = rb_post_por_id($post_id);
        }
    }
    return $resultado;
}

function rb_mes_calcular_score($mes, $anio) {
    /* Calculamos la fecha del inicio del mes */
    $fecha = sprintf("%s-%s-01 00:00:00", $anio, $mes);
    return rb_fecha_calcular_score($fecha);
}

function rb_mes_crear($mes, $anio) {
    $llave = 'meses';
    $valor = sprintf("%d-%02d", $anio, $mes);
    $score = rb_mes_calcular_score($mes, $anio);
    $GLOBALS['redis']->zAdd($llave, $score, $valor);
}

function rb_mes_eliminar($mes, $anio) {
    $llave = 'meses';
    $valor = sprintf("%d-%02d", $anio, $mes);
    $score = rb_mes_calcular_score($mes, $anio);
    $GLOBALS['redis']->zRemove($llave, $valor);
}

function rb_mes_existe($mes, $anio) {
    $llave = 'meses';
    $valor = sprintf("%d-%02d", $anio, $mes);
    return $GLOBALS['redis']->zScore($llave, $valor) !== False;
}

function rb_mes_contar_posts($mes, $anio) {
    $llave = sprintf("posts:anio:%04d:mes:%02d", $anio, $mes);
    return $GLOBALS['redis']->zSize($llave);
}

function rb_mes_existen_posts($mes, $anio) {
    return rb_mes_contar_posts($mes, $anio) > 0;
}

function rb_mes_posts($mes, $anio, $offset = NULL, $count = NULL, $reverse = True) {
    $resultado = array();
    $opciones = rb_opciones_limit($offset, $count);
    $max = rb_max_timestamp();
    $llave = sprintf("posts:anio:%04d:mes:%02d", $anio, $mes);
    if ($reverse) {
        if (is_null($offset)) {
            $offset = 0;
        }
        if (is_null($count)) {
            if (array_key_exists('max_posts_por_omision', $GLOBALS['CONF'])) {
                $count = intval($GLOBALS['CONF']['max_posts_por_omision']);
            } else {
                $count = 5;
            }
        }
        $min = $offset;
        $max = $offset + $count;
        $resultado = $GLOBALS['redis']->zReverseRange($llave, $min, $max); 
    } else {
        $resultado = $GLOBALS['redis']->zRangeByScore($llave, 0, $max_epoch, $opciones);
    }
    if (is_array($resultado)) {
        foreach($resultado as $k=>$post_id) {
            $resultado[$k] = rb_post_por_id($post_id);
        }
    }
    return $resultado;
}

function rb_mes_listado($formateado = True) {
    $llave = 'meses';
    /* Recuperamos todos los elementos del conjunto ordenado */
    $resultado = $GLOBALS['redis']->zRange($llave, 0, -1);
    if (is_array($resultado)) {
        if ($formateado) {
            foreach($resultado as $k=>$v) {
                $resultado[$k] = rb_mes_formatear($v);
            }
        }
    }
    return $resultado;
}

function rb_nube_tags_tamanho($num_posts) {
    $mapa = array(
        5 => 2,
        10 => 3,
        50 => 4,
        100 => 5,
        500 => 6,
        1000 => 7
    );
    $resultado = 1;
    foreach($mapa as $limite_inferior => $tamanho) {
        if ($num_posts > $limite_inferior) {
            /* Si el número de post es mayor que el límite
               inferior pasamos el siguiente tamaño */
            $resultado = $tamanho;
            continue;
        } else {
            /* Si el número de post es menor o igual al 
               límete inferior y a hemos encontrado el
               tamaño adecuado */
            break;
        }
    }
    return $resultado;
}

function rb_nube_tags_estilo($tamanho) {
    return sprintf("tag_tamanho_%d", $tamanho);
}

function rb_nube_tags() {
    $resultado = array();
    $tags = rb_tag_listado();
    foreach($tags as $tag) {
        $resultado[$tag] = array();
        $resultado[$tag]['num_posts'] = rb_tag_contar_posts($tag);
        $resultado[$tag]['tamanho'] = rb_nube_tags_tamanho($resultado[$tag]['num_posts']);
        $resultado[$tag]['estilo'] = rb_nube_tags_estilo($resultado[$tag]['tamanho']);
    }
    return $resultado;
}

function rb_archivo() {
    $resultado = array();
    $nombres = rb_mes_nombres();
    /* Recuperamos el listado de meses sin formatear */
    $meses = rb_mes_listado(False);
    if (is_array($meses)) {
        foreach($meses as $valor) {
            $formateado = rb_mes_formatear($valor);
            list($anio, $mes) = explode("-", $valor);
            $num_posts = rb_mes_contar_posts($mes, $anio); 
            $resultado[$valor] = array(
                'mes' => $mes,
                'anio' => $anio,
                'nombre_mes' => $nombres[$mes],
                'formateado' => $formateado,
                'num_posts' => $num_posts
            );
        }
    }
    return $resultado;
}

?>
