<?php

function rb_mes_nombres() {
    $mapa = array(
        "01" => 'Enero',
        "02" => 'Febrero',
        "03" => 'Marzo',
        "04" => 'Abril',
        "05" => 'Mayo',
        "06" => 'Junio',
        "07" => 'Julio',
        "08" => 'Agosto',
        "09" => 'Setiembre',
        "10" => 'Octubre',
        "11" => 'Noviembre',
        "12" => 'Diciembre',
    );
    return $mapa;
}

function rb_mes_formatear($valor) {
    list($anio, $mes) = explode("-", $valor);
    $nombres = rb_mes_nombres();
    return sprintf("%s %d", $nombres[$mes], $anio);
}

function rb_url_path() {
    return substr($_SERVER['PATH_INFO'], 1);
}

function rb_tag_desde_url($url = NULL) {
    if (is_null($url)) {
        $url = rb_url_path();
    }
    return urldecode(str_replace('/', '', $url));
}

function rb_slug_desde_url($url = NULL) {
    if (is_null($url)) {
        $url = rb_url_path();
    }
    return urldecode(str_replace('/', '', $url));
}

function rb_anio_desde_url($url = NULL) {
    if (is_null($url)) {
        $url = rb_url_path();
    }
    $partes = explode("/", $url);
    foreach($partes as $k=>$v) {
        if (strlen($v) == 0) {
            unset($partes[$k]);
        }
    }
    $partes = array_values($partes);
    list($anio, $mes) = $partes;
    return $anio;
}

function rb_mes_desde_url($url = NULL) {
    if (is_null($url)) {
        $url = rb_url_path();
    }
    $partes = explode("/", $url);
    foreach($partes as $k=>$v) {
        if (strlen($v) == 0) {
            unset($partes[$k]);
        }
    }
    $partes = array_values($partes);
    list($anio, $mes) = $partes;
    return $mes;
}

?>
