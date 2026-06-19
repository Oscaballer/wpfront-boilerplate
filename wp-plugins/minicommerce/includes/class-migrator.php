<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MiniCommerce_Migrator {

    const OPTION_IMAGE_PATH = 'mc_migration_images_path';
    const OPTION_STATUS     = 'mc_migration_status';

    /**
     * @return array<string, mixed>
     */
    public static function get_status() {
        $stored = get_option( self::OPTION_STATUS, array() );
        $counts = MiniCommerce_DB::counts();

        return array_merge(
            array(
                'db_imported_at'    => '',
                'images_uploaded'   => 0,
                'images_skipped'    => 0,
                'images_failed'     => 0,
                'images_last_batch' => '',
                'counts'            => $counts,
            ),
            is_array( $stored ) ? $stored : array()
        );
    }

    /**
     * @param string $sql_path Ruta absoluta al archivo .sql
     * @return array{success: bool, message: string, counts?: array<string, int>}
     */
    public static function import_sql_file( $sql_path ) {
        if ( ! file_exists( $sql_path ) || ! is_readable( $sql_path ) ) {
            return array(
                'success' => false,
                'message' => 'Archivo SQL no encontrado o no legible.',
            );
        }

        $sql = file_get_contents( $sql_path );
        if ( false === $sql ) {
            return array(
                'success' => false,
                'message' => 'No se pudo leer el archivo SQL.',
            );
        }

        global $wpdb;

        $wpdb->query( 'START TRANSACTION' );

        try {
            self::truncate_tables();

            $sections = self::parse_insert_rows( $sql, 'eros_seccion' );
            $subcats  = self::parse_insert_rows( $sql, 'eros_sub_categorias' );
            $products = self::parse_insert_rows( $sql, 'eros_articulo' );
            $images   = self::parse_insert_rows( $sql, 'eros_imagen_articulo' );

            $section_count = self::import_sections( $sections );
            $subcat_count  = self::import_subcategories( $subcats );
            $product_count = self::import_products( $products );
            $image_count   = self::import_extra_images( $images );

            $wpdb->query( 'COMMIT' );

            $status = self::get_status();
            $status['db_imported_at'] = current_time( 'mysql' );
            $status['counts']         = MiniCommerce_DB::counts();
            update_option( self::OPTION_STATUS, $status );

            MiniCommerce_Revalidate::trigger( array( 'minicommerce', 'minicommerce-products' ) );

            return array(
                'success' => true,
                'message' => sprintf(
                    'Importación completada: %d secciones, %d subcategorías, %d artículos, %d imágenes extra.',
                    $section_count,
                    $subcat_count,
                    $product_count,
                    $image_count
                ),
                'counts'  => array(
                    'secciones'      => $section_count,
                    'subcategorias'  => $subcat_count,
                    'articulos'      => $product_count,
                    'imagenes_extra' => $image_count,
                ),
            );
        } catch ( Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            return array(
                'success' => false,
                'message' => $e->getMessage(),
            );
        }
    }

    /**
     * @return array{success: bool, message: string, processed?: int, uploaded?: int, skipped?: int, failed?: int, done?: bool}
     */
    public static function upload_images_batch( $directory, $offset = 0, $batch_size = 25 ) {
        $directory = rtrim( $directory, '/\\' );
        if ( ! is_dir( $directory ) ) {
            return array(
                'success' => false,
                'message' => 'Directorio de imágenes no encontrado.',
            );
        }

        if ( ! mc_gcp()->is_configured() ) {
            return array(
                'success' => false,
                'message' => 'GCP no configurado.',
            );
        }

        $files = self::list_image_files( $directory );
        $total = count( $files );
        $slice = array_slice( $files, $offset, $batch_size );

        $uploaded = 0;
        $skipped  = 0;
        $failed   = 0;

        foreach ( $slice as $filepath ) {
            $filename = basename( $filepath );

            if ( mc_gcp()->object_exists( $filename ) ) {
                ++$skipped;
                continue;
            }

            $result = mc_gcp()->upload_file( $filepath, $filename );
            if ( is_wp_error( $result ) ) {
                ++$failed;
            } else {
                ++$uploaded;
            }
        }

        $status = self::get_status();
        $status['images_uploaded']  = (int) ( $status['images_uploaded'] ?? 0 ) + $uploaded;
        $status['images_skipped']   = (int) ( $status['images_skipped'] ?? 0 ) + $skipped;
        $status['images_failed']    = (int) ( $status['images_failed'] ?? 0 ) + $failed;
        $status['images_last_batch'] = current_time( 'mysql' );
        update_option( self::OPTION_STATUS, $status );

        $next_offset = $offset + $batch_size;
        $done        = $next_offset >= $total;

        if ( $done ) {
            MiniCommerce_Revalidate::trigger( array( 'minicommerce', 'minicommerce-products' ) );
        }

        return array(
            'success'   => true,
            'message'   => sprintf(
                'Lote procesado: %d subidos, %d omitidos, %d fallidos.',
                $uploaded,
                $skipped,
                $failed
            ),
            'processed' => count( $slice ),
            'uploaded'  => $uploaded,
            'skipped'   => $skipped,
            'failed'    => $failed,
            'offset'    => $next_offset,
            'total'     => $total,
            'done'      => $done,
        );
    }

    private static function truncate_tables() {
        global $wpdb;
        $wpdb->query( 'TRUNCATE TABLE ' . MiniCommerce_DB::table( 'imagen_articulo' ) );
        $wpdb->query( 'TRUNCATE TABLE ' . MiniCommerce_DB::table( 'articulo' ) );
        $wpdb->query( 'TRUNCATE TABLE ' . MiniCommerce_DB::table( 'sub_categoria' ) );
        $wpdb->query( 'TRUNCATE TABLE ' . MiniCommerce_DB::table( 'seccion' ) );
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private static function parse_insert_rows( $sql, $table ) {
        $pattern = '/INSERT INTO `' . preg_quote( $table, '/' ) . '` VALUES\s*(.+?);\s*(?:\/\*|\n|$)/s';
        if ( ! preg_match( $pattern, $sql, $matches ) ) {
            return array();
        }

        return self::parse_value_tuples( $matches[1] );
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private static function parse_value_tuples( $block ) {
        $rows   = array();
        $length = strlen( $block );
        $i      = 0;

        while ( $i < $length ) {
            while ( $i < $length && $block[ $i ] !== '(' ) {
                ++$i;
            }
            if ( $i >= $length ) {
                break;
            }

            ++$i;
            $values = array();
            $current = '';
            $in_string = false;
            $escape = false;

            while ( $i < $length ) {
                $char = $block[ $i ];

                if ( $in_string ) {
                    if ( $escape ) {
                        $current .= $char;
                        $escape = false;
                    } elseif ( '\\' === $char ) {
                        $escape = true;
                    } elseif ( "'" === $char ) {
                        $in_string = false;
                    } else {
                        $current .= $char;
                    }
                    ++$i;
                    continue;
                }

                if ( 'N' === $char && substr( $block, $i, 4 ) === 'NULL' ) {
                    $values[] = null;
                    $i       += 4;
                    $current  = '';
                    while ( $i < $length && in_array( $block[ $i ], array( ' ', ',', "\n", "\r", "\t" ), true ) ) {
                        ++$i;
                    }
                    continue;
                }

                if ( "'" === $char ) {
                    $in_string = true;
                    ++$i;
                    continue;
                }

                if ( ',' === $char ) {
                    $values[] = trim( $current );
                    $current  = '';
                    ++$i;
                    continue;
                }

                if ( ')' === $char ) {
                    if ( '' !== $current || ! empty( $values ) ) {
                        $values[] = trim( $current );
                    }
                    $rows[] = $values;
                    ++$i;
                    break;
                }

                $current .= $char;
                ++$i;
            }
        }

        return $rows;
    }

    private static function import_sections( array $rows ) {
        global $wpdb;
        $table = MiniCommerce_DB::table( 'seccion' );
        $count = 0;

        foreach ( $rows as $row ) {
            if ( count( $row ) < 5 ) {
                continue;
            }

            $wpdb->insert(
                $table,
                array(
                    'id'          => (int) $row[0],
                    'nombre'      => self::to_utf8( $row[1] ),
                    'descripcion' => self::nullable_utf8( $row[2] ?? null ),
                    'publicado'   => (int) $row[3],
                    'orden'       => (int) ( $row[4] ?? 0 ),
                    'slug'        => self::slugify( self::to_utf8( $row[1] ) ),
                ),
                array( '%d', '%s', '%s', '%d', '%d', '%s' )
            );
            ++$count;
        }

        return $count;
    }

    private static function import_subcategories( array $rows ) {
        global $wpdb;
        $table = MiniCommerce_DB::table( 'sub_categoria' );
        $count = 0;

        foreach ( $rows as $row ) {
            if ( count( $row ) < 3 ) {
                continue;
            }

            $name = self::to_utf8( $row[1] );
            $wpdb->insert(
                $table,
                array(
                    'id'         => (int) $row[0],
                    'nombre'     => $name,
                    'id_seccion' => (int) $row[2],
                    'slug'       => self::slugify( $name ),
                ),
                array( '%d', '%s', '%d', '%s' )
            );
            ++$count;
        }

        return $count;
    }

    private static function import_products( array $rows ) {
        global $wpdb;
        $table = MiniCommerce_DB::table( 'articulo' );
        $count = 0;

        foreach ( $rows as $row ) {
            if ( count( $row ) < 8 ) {
                continue;
            }

            $subcat = ( null === $row[7] || '' === $row[7] || 'NULL' === $row[7] ) ? 0 : (int) $row[7];

            $wpdb->insert(
                $table,
                array(
                    'id'               => (int) $row[0],
                    'nombre'           => self::to_utf8( $row[1] ),
                    'descripcion'      => self::nullable_utf8( $row[2] ?? null ),
                    'precio'           => self::nullable_utf8( $row[3] ?? null ),
                    'imagen'           => self::nullable_utf8( $row[4] ?? null ),
                    'id_seccion'       => (int) $row[5],
                    'novedad'          => (int) ( $row[6] ?? 0 ),
                    'id_sub_categoria' => $subcat ?: null,
                    'orden'            => (int) ( $row[8] ?? 0 ),
                    'codigo'           => self::nullable_utf8( $row[9] ?? null ),
                    'publicado'        => 1,
                ),
                array( '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%d' )
            );
            ++$count;
        }

        return $count;
    }

    private static function import_extra_images( array $rows ) {
        global $wpdb;
        $table = MiniCommerce_DB::table( 'imagen_articulo' );
        $count = 0;

        foreach ( $rows as $row ) {
            if ( count( $row ) < 3 ) {
                continue;
            }

            $wpdb->insert(
                $table,
                array(
                    'id'          => (int) $row[0],
                    'id_articulo' => (int) $row[1],
                    'imagen'      => self::to_utf8( $row[2] ),
                    'descripcion' => self::nullable_utf8( $row[3] ?? null ),
                ),
                array( '%d', '%d', '%s', '%s' )
            );
            ++$count;
        }

        return $count;
    }

    private static function to_utf8( $value ) {
        if ( null === $value ) {
            return '';
        }
        $value = (string) $value;
        if ( function_exists( 'mb_convert_encoding' ) ) {
            return mb_convert_encoding( $value, 'UTF-8', 'ISO-8859-1' );
        }
        return utf8_encode( $value );
    }

    private static function nullable_utf8( $value ) {
        if ( null === $value || 'NULL' === $value ) {
            return null;
        }
        $converted = self::to_utf8( $value );
        return '' === $converted ? null : $converted;
    }

    private static function slugify( $text ) {
        $text = remove_accents( $text );
        $text = strtolower( $text );
        $text = preg_replace( '/[^a-z0-9]+/', '-', $text );
        return trim( $text, '-' );
    }

    /**
     * @return string[]
     */
    private static function list_image_files( $directory ) {
        $extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );
        $files      = array();

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $directory, RecursiveDirectoryIterator::SKIP_DOTS )
        );

        foreach ( $iterator as $file ) {
            if ( ! $file->isFile() ) {
                continue;
            }
            $ext = strtolower( $file->getExtension() );
            if ( in_array( $ext, $extensions, true ) ) {
                $files[] = $file->getPathname();
            }
        }

        sort( $files );
        return $files;
    }
}
