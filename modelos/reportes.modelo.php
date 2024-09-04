<?php

require_once "conexion.php";

class ReportesModelo
{


    static public function mdlReporteKardex($post)
    {

        $column = ["codigo_producto", "producto", "entradas", "salidas", "existencias", "costo_existencias"];

        $query = " SELECT 	k.codigo_producto as codigo_producto, 
                            p.descripcion as producto,
                            sum(ifnull(k.in_unidades,0)) as entradas, 
                            sum(ifnull(k.out_unidades,0)) as salidas,
                            (select k1.ex_unidades from kardex k1 where k1.codigo_producto = k.codigo_producto order by id desc limit 1) as existencias,
                            (select k2.ex_costo_total from kardex k2 where k2.codigo_producto = k.codigo_producto order by id desc limit 1) as costo_existencias
                    FROM kardex k inner join productos p on k.codigo_producto = p.codigo_producto";

        if (isset($post["search"]["value"])) {
            $query .= ' WHERE p.descripcion like "%' . $post["search"]["value"] . '%" 
                        or k.codigo_producto like "%' . $post["search"]["value"] . '%" GROUP BY k.codigo_producto';
        }

        if (isset($post["order"])) {
            $query .= ' ORDER BY ' . $column[$post['order']['0']['column']] . ' ' . $post['order']['0']['dir'] . ' ';
        } else {
            $query .= ' ORDER BY id asc ';
        }

        //SE AGREGA PAGINACION
        if ($post["length"] != -1) {
            $query1 = " LIMIT " . $post["start"] . ", " . $post["length"];
        }

        $stmt = Conexion::conectar()->prepare($query);

        $stmt->execute();

        $number_filter_row = $stmt->rowCount();

        $stmt =  Conexion::conectar()->prepare($query . $query1);

        $stmt->execute();

        $results = $stmt->fetchAll();

        $data = array();

        foreach ($results as $row) {
            $sub_array = array();
            $sub_array[] = $row['codigo_producto'];
            $sub_array[] = $row['producto'];
            $sub_array[] = $row['entradas'];
            $sub_array[] = $row['salidas'];
            $sub_array[] = $row['existencias'];
            $sub_array[] = $row['costo_existencias'];
            $data[] = $sub_array;
        }

        $stmt = Conexion::conectar()->prepare("SELECT 	k.codigo_producto as codigo_producto, 
                                                        p.descripcion as producto,
                                                        sum(ifnull(k.in_unidades,0)) as entradas, 
                                                        sum(ifnull(k.out_unidades,0)) as salidas,
                                                        (select k1.ex_unidades from kardex k1 where k1.codigo_producto = k.codigo_producto order by id desc limit 1) as existencias,
                                                        (select k2.ex_costo_total from kardex k2 where k2.codigo_producto = k.codigo_producto order by id desc limit 1) as costo_existencias
                                                FROM kardex k inner join productos p on k.codigo_producto = p.codigo_producto
                                                GROUP BY k.codigo_producto");

        $stmt->execute();

        $count_all_data = $stmt->rowCount();

        $output = array(
            'draw' => $post['draw'],
            "recordsTotal" => $count_all_data,
            "recordsFiltered" => $number_filter_row,
            "data" => $data
        );

        return $output;
    }

    static public function mdlReporteKardexPorProducto($post)
    {

        $column = ["codigo_producto", "producto", "fecha", "tipo_movimiento", "cantidad", "stock"];

        $query = "SELECT 
                        k.codigo_producto, 
                        p.descripcion as producto,
                    date(fecha) as fecha, 
                    case when (upper(k.concepto) LIKE '%COMPRA%' ) then 'COMPRA'
                            when (upper(k.concepto) LIKE '%INVENTARIO INICIAL%') then 'INVENTARIO INICIAL'
                            when (upper(k.concepto) LIKE '%VENTA%') THEN 'VENTA'
                            end
                    as tipo_movimiento, 
                    (select case when (upper(k1.concepto) LIKE '%COMPRA%' OR upper(k1.concepto) LIKE '%INVENTARIO INICIAL%')
                                        THEN k1.in_unidades 
                                    when (upper(concepto) LIKE '%VENTA%')
                                        THEN k1.out_unidades * -1
                                end 
                            from kardex k1 
                        where k1.id = k.id ) 
                        as cantidad,
                    ex_unidades as stock
                from kardex k inner join productos p on k.codigo_producto = p.codigo_producto";

        if (isset($post["search"]["value"])) {
            $query .= ' WHERE    p.descripcion like "%' . $post["search"]["value"] . '%" 
                                or k.codigo_producto like "%' . $post["search"]["value"] . '%" 
                        GROUP BY 
                                k.codigo_producto, 
                                p.descripcion,
                                k.fecha, 
                                k.concepto, 
                                in_unidades,        
                                out_unidades,        
                                ex_unidades';
        }

        if (isset($post["order"])) {
            $query .= ' order by ' . $column[$post['order']['0']['column']] . ' ' . $post['order']['0']['dir'] . ' ';
        } else {
            $query .= ' order by k.id ';
        }

        //SE AGREGA PAGINACION
        if ($post["length"] != -1) {
            $query1 = " LIMIT " . $post["start"] . ", " . $post["length"];
        }

        $stmt = Conexion::conectar()->prepare($query);

        $stmt->execute();

        $number_filter_row = $stmt->rowCount();

        $stmt =  Conexion::conectar()->prepare($query . $query1);

        $stmt->execute();

        $results = $stmt->fetchAll();

        $data = array();

        foreach ($results as $row) {
            $sub_array = array();
            $sub_array[] = $row['codigo_producto'];
            $sub_array[] = $row['producto'];
            $sub_array[] = $row['fecha'];
            $sub_array[] = $row['tipo_movimiento'];
            $sub_array[] = $row['cantidad'];
            $sub_array[] = $row['stock'];
            $data[] = $sub_array;
        }

        $stmt = Conexion::conectar()->prepare("SELECT 	
                                                    *
                                                from 
                                                    kardex k inner join productos p on k.codigo_producto = p.codigo_producto
                                                GROUP BY 
                                                    k.codigo_producto, 
                                                    p.descripcion,
                                                    k.fecha, 
                                                    k.concepto, 
                                                    in_unidades,        
                                                    out_unidades,        
                                                    ex_unidades");

        $stmt->execute();

        $count_all_data = $stmt->rowCount();

        $output = array(
            'draw' => $post['draw'],
            "recordsTotal" => $count_all_data,
            "recordsFiltered" => $number_filter_row,
            "data" => $data
        );

        return $output;
    }

    static public function mdlReporteVentasPorCategoria($post)
    {

        $column = ["categoria", "cantidad", "precio_venta", "total_venta", "ganancias"];

        $query = " SELECT c.descripcion as categoria, 
                            round(sum(ifnull(dv.cantidad,0)),2) as cantidad,
                            round(sum(ifnull(dv.valor_unitario,0)),2) as precio_venta,
                            round(sum(ifnull(dv.importe_total,0)),2) as total_venta,
                            round(sum(ifnull(dv.importe_total,0)) - sum(ifnull(dv.costo_unitario,0)*ifnull(dv.cantidad,0)),2) as ganancias
                    FROM venta v inner join usuarios u on u.id_usuario = v.id_usuario
                                inner join detalle_venta dv on dv.id_venta = v.id
                                inner join productos p on p.codigo_producto = dv.codigo_producto
                                inner join categorias c on c.id = p.id_categoria";

        if (isset($post["search"]["value"])) {
            $query .= ' WHERE c.descripcion like "%' . $post["search"]["value"] . '%" GROUP BY c.descripcion ';
        }

        if (isset($post["order"])) {
            $query .= ' ORDER BY ' . $column[$post['order']['0']['column']] . ' ' . $post['order']['0']['dir'] . ' ';
        } else {
            $query .= ' ORDER BY c.id asc ';
        }

        //SE AGREGA PAGINACION
        if ($post["length"] != -1) {
            $query1 = " LIMIT " . $post["start"] . ", " . $post["length"];
        }

        $stmt = Conexion::conectar()->prepare($query);

        $stmt->execute();

        $number_filter_row = $stmt->rowCount();

        $stmt =  Conexion::conectar()->prepare($query . $query1);

        $stmt->execute();

        $results = $stmt->fetchAll();

        $data = array();

        foreach ($results as $row) {
            $sub_array = array();
            $sub_array[] = $row['categoria'];
            $sub_array[] = $row['cantidad'];
            $sub_array[] = $row['precio_venta'];
            $sub_array[] = $row['total_venta'];
            $sub_array[] = $row['ganancias'];
            $data[] = $sub_array;
        }

        $stmt = Conexion::conectar()->prepare("SELECT c.descripcion as categoria, 
                                                    round(sum(ifnull(dv.cantidad,0)),2) as cantidad,
                                                    round(sum(ifnull(dv.valor_unitario,0)),2) as precio_venta,
                                                    round(sum(ifnull(dv.importe_total,0)),2) as total_venta,
                                                    round(sum(ifnull(dv.importe_total,0)) - sum(ifnull(dv.costo_unitario,0)*ifnull(dv.cantidad,0)),2) as ganancias
                                            FROM venta v inner join usuarios u on u.id_usuario = v.id_usuario
                                                        inner join detalle_venta dv on dv.id_venta = v.id
                                                        inner join productos p on p.codigo_producto = dv.codigo_producto
                                                        inner join categorias c on c.id = p.id_categoria
                                                GROUP BY c.descripcion");

        $stmt->execute();

        $count_all_data = $stmt->rowCount();

        $output = array(
            'draw' => $post['draw'],
            "recordsTotal" => $count_all_data,
            "recordsFiltered" => $number_filter_row,
            "data" => $data
        );

        return $output;
    }

    static public function mdlReporteVentasPorProducto($post)
    {

        $column = ["codigo_producto", "producto", "cantidad", "precio_venta", "total_venta", "ganancias"];

        $query = " SELECT p.codigo_producto as codigo_producto, 
                        p.descripcion as producto,
                        round(sum(ifnull(dv.cantidad,0)),2) as cantidad,
                        round(sum(ifnull(dv.valor_unitario,0)),2) as precio_venta,
                        round(sum(ifnull(dv.importe_total,0)),2) as total_venta,
                        round(sum(ifnull(dv.importe_total,0)) - sum(ifnull(dv.costo_unitario,0)*ifnull(dv.cantidad,0)),2) as ganancias
                    FROM venta v inner join usuarios u on u.id_usuario = v.id_usuario
                                inner join detalle_venta dv on dv.id_venta = v.id
                                inner join productos p on p.codigo_producto = dv.codigo_producto
                                inner join categorias c on c.id = p.id_categoria";

        if (isset($post["search"]["value"])) {
            $query .= ' WHERE p.codigo_producto like "%' . $post["search"]["value"] . '%" 
                    or p.descripcion like "%' . $post["search"]["value"] . '%" GROUP BY p.codigo_producto ';
        }

        if (isset($post["order"])) {
            $query .= ' ORDER BY ' . $column[$post['order']['0']['column']] . ' ' . $post['order']['0']['dir'] . ' ';
        } else {
            $query .= ' ORDER BY p.codigo_producto asc ';
        }

        //SE AGREGA PAGINACION
        if ($post["length"] != -1) {
            $query1 = " LIMIT " . $post["start"] . ", " . $post["length"];
        }

        $stmt = Conexion::conectar()->prepare($query);

        $stmt->execute();

        $number_filter_row = $stmt->rowCount();

        $stmt =  Conexion::conectar()->prepare($query . $query1);

        $stmt->execute();

        $results = $stmt->fetchAll();

        $data = array();

        foreach ($results as $row) {
            $sub_array = array();
            $sub_array[] = $row['codigo_producto'];
            $sub_array[] = $row['producto'];
            $sub_array[] = $row['cantidad'];
            $sub_array[] = $row['precio_venta'];
            $sub_array[] = $row['total_venta'];
            $sub_array[] = $row['ganancias'];
            $data[] = $sub_array;
        }

        $stmt = Conexion::conectar()->prepare(" SELECT p.codigo_producto as codigo_producto, 
                                                        p.descripcion as producto,
                                                        round(sum(ifnull(dv.cantidad,0)),2) as cantidad,
                                                        round(sum(ifnull(dv.valor_unitario,0)),2) as precio_venta,
                                                        round(sum(ifnull(dv.importe_total,0)),2) as total_venta,
                                                        round(sum(ifnull(dv.importe_total,0)) - sum(ifnull(dv.costo_unitario,0)*ifnull(dv.cantidad,0)),2) as ganancias
                                                    FROM venta v inner join usuarios u on u.id_usuario = v.id_usuario
                                                                inner join detalle_venta dv on dv.id_venta = v.id
                                                                inner join productos p on p.codigo_producto = dv.codigo_producto
                                                                inner join categorias c on c.id = p.id_categoria
                                                                                                GROUP BY p.codigo_producto");

        $stmt->execute();

        $count_all_data = $stmt->rowCount();

        $output = array(
            'draw' => $post['draw'],
            "recordsTotal" => $count_all_data,
            "recordsFiltered" => $number_filter_row,
            "data" => $data
        );

        return $output;
    }

    static public function mdlReporteRegistroVentas($comprobantes, $cliente, $fecha_desde, $fecha_hasta)
    {
        $stmt = Conexion::conectar()->prepare("SELECT v.id, 
                                                        v.fecha_emision,
                                                        '' as fecha_vencimiento,
                                                        tc.codigo as tipo_comprobante,
                                                        v.serie,
                                                        v.correlativo,
                                                        cli.id_tipo_documento,
                                                        cli.nro_documento,
                                                        cli.nombres_apellidos_razon_social,
                                                        0 as valor_facturado_exportacion,
                                                        v.total_operaciones_gravadas as base_imponible_ope_gravada,
                                                        v.total_operaciones_exoneradas as importe_total_ope_exo,
                                                        v.total_operaciones_inafectas as importe_total_ope_ina,
                                                        0 as isc,
                                                        v.total_igv as igv,
                                                        0 as otros_trib_cargos_no_parte_base_imp,
                                                        v.importe_total as imp_total_comprobante_pago,
                                                        1 as tipo_cambio,
                                                        '' as fecha_comprobante_modificado,
                                                        ifnull(ser_mod.id_tipo_comprobante,'') as tipo_comprobante_modificado,
                                                        ifnull(ser_mod.serie,'') as serie_modificado,
                                                        ifnull(v.correlativo_modificado,'') as correlativo_modificado
                                                FROM venta v inner join serie ser on v.id_serie = ser.id
                                                             inner join tipo_comprobante tc on tc.codigo = ser.id_tipo_comprobante
                                                             inner join clientes cli on cli.id = v.id_cliente
                                                             left join serie ser_mod on v.id_serie_modificado = ser_mod.id
                                                WHERE (tc.codigo = :comprobantes OR :comprobantes = '')
                                                AND (cli.nombres_apellidos_razon_social like concat('%',:cliente,'%') OR :cliente = '')
                                                AND (v.fecha_emision >= :fecha_desde OR :fecha_desde = '')
                                                AND (v.fecha_emision <= :fecha_hasta OR :fecha_hasta = '')
                                                ORDER BY v.id desc
                                                LIMIT 10000");

        $stmt->bindParam(":comprobantes", $comprobantes, PDO::PARAM_STR);
        $stmt->bindParam(":cliente", $cliente, PDO::PARAM_STR);
        $stmt->bindParam(":fecha_desde", $fecha_desde, PDO::PARAM_STR);
        $stmt->bindParam(":fecha_hasta", $fecha_hasta, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
